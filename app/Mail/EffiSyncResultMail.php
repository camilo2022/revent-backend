<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Confirmación a la bodega WEB: la sincronización automática con Effi se hizo; qué se movió y qué quedó pendiente. */
class EffiSyncResultMail extends Mailable
{
    use SerializesModels;

    public function __construct(public array $report)
    {
    }

    public function build()
    {
        // Pruebas: si hay un destinatario de prueba, el correo va solo allí (no a la bodega WEB)
        if ($target = \App\Support\Integration\EffiMailRedirect::target()) {
            $this->to = [];
            $this->to($target);
        }

        $t = $this->report['totales'];
        $n = fn ($v) => number_format($v, 0, ',', '.');

        $kind = $this->report['manual'] ? 'sincronización manual' : 'sincronización automática';
        $subject = $t['con_error']
            ? "Effi: {$kind} de la bodega WEB terminada, con {$t['con_error']} código(s) pendientes"
            : "Effi: {$kind} de la bodega WEB terminada (ingresaron {$n($t['entradas'])} y salieron {$n($t['salidas'])} unidades)";

        return $this
            ->subject($subject)
            ->view('email.effi_sync.result', ['link' => route('siigo.effi_sync')])
            ->attachData($this->csv(), 'sincronizacion_effi_bodega_web.csv', ['mime' => 'text/csv']);
    }

    /** Detalle completo por código (el cuerpo del correo muestra solo los primeros). */
    private function csv(): string
    {
        $rows = [['Código', 'Descripción', 'Unidades antes en Effi', 'Unidades ahora en Effi', 'Unidades en Siigo', 'Qué se hizo', 'Error']];

        foreach ($this->report['lineas'] as $l) {
            $rows[] = [$l['codigo'], $l['descripcion'], $l['antes'], $l['despues'], $l['siigo'], implode(' | ', $l['acciones']), $l['error'] ?? ''];
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");

        foreach ($rows as $row) {
            fputcsv($handle, $row, ';');
        }

        rewind($handle);

        return stream_get_contents($handle);
    }
}
