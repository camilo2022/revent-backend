<?php

namespace App\Services\Effi;

use App\Jobs\EffiInventorySyncJob;
use App\Mail\EffiSyncManualRequiredMail;
use App\Mail\EffiSyncResultMail;
use App\Support\Integration\ReceptionMailCopy;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sincronización automática de la bodega WEB de Siigo con Effi, después de una nota de traslado que la toca
 * (salida de la bodega WEB o ingreso a ella).
 *
 *  - Si hay algo que decidir (categorías que no existen, referencias repetidas, códigos sin datos...) NO sincroniza:
 *    avisa por correo al correo de la bodega WEB para que la haga manual.
 *  - Si no hay inconsistencias, sincroniza sola y manda el correo con lo que se movió.
 *
 * Solo funciona con EFFI_SYNC_ENABLED y EFFI_AUTO_SYNC en true (apagados por defecto). Nunca lanza excepciones:
 * un fallo aquí no debe afectar al traslado que la disparó.
 */
class EffiAutoSync
{
    /** Mientras las decisiones pendientes sean las mismas, el aviso de «hacerla manual» se manda una vez cada tantas horas. */
    private const REMINDER_HOURS = 6;

    public function __construct(
        private EffiInventorySyncPlanner $planner,
        private EffiInventorySyncRunner $runner,
        private EffiClient $effi
    ) {
    }

    public function enabled(): bool
    {
        return (bool) config('effi.sync_enabled') && (bool) config('effi.auto_sync');
    }

    /** Notas de traslado recién creadas (formato de ImportMasiveTransferSiigoJob) que salen de la bodega WEB o entran a ella. */
    public function webDocuments(array $transfers): array
    {
        $web = (int) config('effi.siigo_warehouse');

        return collect($transfers)
            ->filter(fn ($t) => (int) ($t['data']['bodega_salida'] ?? 0) === $web || (int) ($t['data']['bodega_ingreso'] ?? 0) === $web)
            ->map(fn ($t) => [
                'documento' => $t['data']['document'] ?? '',
                'sentido' => (int) ($t['data']['bodega_salida'] ?? 0) === $web ? 'Salida de la bodega WEB' : 'Ingreso a la bodega WEB',
            ])
            ->values()
            ->all();
    }

    /** Punto de entrada desde los jobs de traslados. */
    public function afterTransfers(array $transfers): void
    {
        try {
            $documents = $this->webDocuments($transfers);

            if ($documents && $this->enabled()) {
                $this->run($documents);
            }
        } catch (\Throwable $e) {
            Log::error('Sincronización automática Effi falló', ['error' => $e->getMessage()]);
        }
    }

    /** @return string 'ocupado' | 'manual' | 'sin_cambios' | 'sincronizado' | 'con_errores' */
    public function run(array $documents): string
    {
        $lock = Cache::lock(EffiInventorySyncJob::LOCK_KEY, 3600);

        try {
            $lock->block(900);
        } catch (LockTimeoutException) {
            Log::warning('Sincronización automática Effi: hay otra sincronización en curso, no se esperó más');

            return 'ocupado';
        }

        try {
            $plan = $this->planner->plan($this->effi);

            if (!$plan['ok']) {
                $this->notifyManual($documents, ['otros' => [['codigo' => '—', 'descripcion' => '', 'motivo' => $plan['message']]]]);

                return 'manual';
            }

            if ($pending = $this->pendingDecisions($plan)) {
                $this->notifyManual($documents, $pending);

                return 'manual';
            }

            if (!$plan['items']) {
                Log::info('Sincronización automática Effi: la bodega WEB ya estaba igual en los dos sistemas');

                return 'sin_cambios';
            }

            $result = $this->runner->run($plan);
            Mail::to($this->recipients())->send(new EffiSyncResultMail($this->report($plan, $result, $documents)));

            return $result['errores'] ? 'con_errores' : 'sincronizado';
        } finally {
            $lock->release();
        }
    }

    /** Lo que impide sincronizar sin que alguien decida (vacío = se puede sincronizar sola). */
    public function pendingDecisions(array $plan): array
    {
        $pending = [
            'categorias' => $plan['decisiones']['categorias'],
            'duplicados' => $plan['decisiones']['duplicados'],
            'otros' => collect($plan['manuales'])->where('tipo', '!=', 'duplicado')
                ->map(fn ($m) => ['codigo' => $m['codigo'], 'descripcion' => $m['descripcion'], 'motivo' => $m['motivo']])->values()->all(),
            'avisos' => collect($plan['items'])->filter(fn ($i) => $i['avisos'])
                ->map(fn ($i) => ['codigo' => $i['codigo'], 'descripcion' => $i['descripcion'], 'motivo' => implode(' ', $i['avisos'])])->values()->all(),
        ];

        return array_filter($pending) ? $pending : [];
    }

    /** Informe del correo de confirmación: totales y detalle por código, con lo que salió bien y lo que no. */
    public function report(array $plan, array $result, array $documents, bool $manual = false): array
    {
        $lines = [];

        foreach ($plan['items'] as $item) {
            $before = (float) ($item['effi'] ?? 0);
            $failed = $result['errores'][$item['codigo']] ?? null;
            $actions = [];

            foreach ($item['pasos'] as $step) {
                $actions[] = match ($step['tipo']) {
                    'referencia' => "Referencia cambiada: {$step['de']} → {$step['a']}",
                    'descripcion' => "Nombre cambiado al de Siigo: {$step['de']} → {$step['a']}",
                    'crear' => 'Artículo creado en Effi con los datos de Siigo',
                    'vaciar' => 'Se vació por no llevar control de stock por bodega',
                    'control' => 'Control de stock cambiado a «por bodega»',
                    'ajuste' => $step['cantidad'] > 0 ? 'Ingreso de ' . $this->units($step['cantidad']) : 'Egreso de ' . $this->units(-$step['cantidad']),
                    default => $step['tipo'],
                };
            }

            $lines[] = [
                'codigo' => $item['codigo'],
                'descripcion' => $item['descripcion'],
                'antes' => $before,
                'despues' => $failed ? $before : (float) $item['siigo'],
                'siigo' => (float) $item['siigo'],
                'acciones' => $actions,
                'error' => $failed,
            ];
        }

        $ok = collect($lines)->whereNull('error');
        $delta = fn ($l) => $l['despues'] - $l['antes'];

        return [
            'fecha' => now()->format('d/m/Y h:i A'),
            'manual' => $manual,
            'documentos' => $documents,
            'bodega_effi' => $plan['bodega_effi'],
            'totales' => [
                'unidades_siigo' => $plan['totales']['unidades_siigo'],
                'unidades_effi_antes' => $plan['totales']['unidades_effi'],
                'unidades_effi_despues' => $plan['totales']['unidades_effi'] + $ok->sum($delta),
                'entradas' => $ok->filter(fn ($l) => $delta($l) > 0)->sum($delta),
                'codigos_con_entrada' => $ok->filter(fn ($l) => $delta($l) > 0)->count(),
                'salidas' => -$ok->filter(fn ($l) => $delta($l) < 0)->sum($delta),
                'codigos_con_salida' => $ok->filter(fn ($l) => $delta($l) < 0)->count(),
                'referencias_cambiadas' => $result['hechos']['referencias'],
                'descripciones_cambiadas' => $result['hechos']['descripciones'] ?? 0,
                'articulos_creados' => $result['hechos']['creados'],
                'controles_cambiados' => $result['hechos']['controles'],
                'con_error' => count($result['errores']),
            ],
            'lineas' => $lines,
        ];
    }

    private function units(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 2, ',', '.'), '0'), ',') . ($quantity == 1 ? ' unidad' : ' unidades');
    }

    private function notifyManual(array $documents, array $pending): void
    {
        // Mismas decisiones pendientes = un solo aviso cada pocas horas (cada nota de traslado dispararía otro igual)
        $key = 'effi_auto_sync_manual:' . md5(collect($pending)->flatten(1)->map(fn ($row) => ($row['codigo'] ?? '') . ($row['modelo'] ?? ''))->sort()->implode('|'));

        if (!Cache::add($key, true, now()->addHours(self::REMINDER_HOURS))) {
            return;
        }

        Mail::to($this->recipients())->send(new EffiSyncManualRequiredMail($documents, $pending));
    }

    /** Correo de la bodega WEB según BODEGAS; si no tiene, el de tecnología. */
    public function recipients(): array
    {
        return ReceptionMailCopy::forWarehouses([(int) config('effi.siigo_warehouse')]) ?: [config('siigo.mail.technology')];
    }
}
