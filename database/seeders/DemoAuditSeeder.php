<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\BloodType;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Color;
use App\Models\CompensationFund;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\FileType;
use App\Models\Gender;
use App\Models\HealthEntity;
use App\Models\Module;
use App\Models\PensionFund;
use App\Models\Person;
use App\Models\PersonType;
use App\Models\Position;
use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderDetail;
use App\Models\ProductionOrderDetailQuantity;
use App\Models\RiskManager;
use App\Models\Size;
use App\Models\Store;
use App\Models\Subcategory;
use App\Models\Submodule;
use App\Models\Supplier;
use App\Models\TechnicalSheet;
use App\Models\Trademark;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Historial de auditoría de demostración para poder revisar el botón "Auditoría" de cada registro:
 * un movimiento de creación por registro y, en fichas, productos, órdenes y colecciones, cambios posteriores
 * (estado, precio, observaciones) con valores antes y después. Es repetible: borra lo que sembró antes (tag "demo").
 *   php artisan db:seed --class=DemoAuditSeeder
 */
class DemoAuditSeeder extends Seeder
{
    private const TAG = 'demo';

    private array $users = [];

    public function run(): void
    {
        $this->users = User::pluck('id')->all();
        if (!$this->users) {
            $this->command?->warn('No hay usuarios: corre UserSeeder primero.');
            return;
        }

        DB::table('audits')->where('tags', self::TAG)->delete();

        $classes = [
            Area::class, BloodType::class, Category::class, Collection::class, Color::class, CompensationFund::class,
            DocumentType::class, Employee::class, FileType::class, Gender::class, HealthEntity::class, Module::class,
            PensionFund::class, Person::class, PersonType::class, Position::class, Product::class, ProductDetail::class,
            ProductionOrder::class, ProductionOrderDetail::class, ProductionOrderDetailQuantity::class, RiskManager::class,
            Size::class, Store::class, Subcategory::class, Submodule::class, Supplier::class, TechnicalSheet::class,
            Trademark::class, User::class,
        ];

        $rows = [];
        foreach ($classes as $class) {
            $query = $class::query();
            if (in_array(SoftDeletes::class, class_uses_recursive($class), true)) {
                $query->withTrashed();
            }

            foreach ($query->get() as $record) {
                $created = $record->created_at ? Carbon::parse($record->created_at) : now()->subDays(7);
                $rows[] = $this->row($class, $record->getKey(), 'created', $created, [], $this->snapshot($record));

                foreach ($this->changes($class, $record, $created) as [$event, $when, $old, $new]) {
                    $rows[] = $this->row($class, $record->getKey(), $event, $when, $old, $new);
                }

                if (method_exists($record, 'trashed') && $record->trashed()) {
                    $rows[] = $this->row($class, $record->getKey(), 'deleted', Carbon::parse($record->deleted_at), $this->snapshot($record), []);
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('audits')->insert($chunk);
        }

        $this->command?->info(count($rows) . ' movimientos de auditoría de demostración creados.');
    }

    /** Cambios posteriores a la creación, con valores antes y después. */
    private function changes(string $class, $record, Carbon $created): array
    {
        $list = [];

        if ($class === TechnicalSheet::class) {
            $list[] = ['updated', $created->copy()->addHours(2), ['observation' => null], ['observation' => 'Se ajusta la descripción de materiales.']];
            if (in_array($record->status, ['En revision', 'Aprobado'], true)) {
                $list[] = ['updated', $created->copy()->addDay(), ['status' => 'Pendiente'], ['status' => 'En revision']];
            }
            if ($record->status === 'Aprobado') {
                $list[] = ['updated', $created->copy()->addDays(2), ['status' => 'En revision'], ['status' => 'Aprobado']];
            }
        }

        if ($class === Product::class) {
            $price = (float) $record->price;
            $list[] = ['updated', $created->copy()->addHours(3), ['price' => number_format($price + 10000, 2, '.', '')], ['price' => number_format($price, 2, '.', '')]];
            $list[] = ['updated', $created->copy()->addDays(1), ['description' => null], ['description' => 'Referencia de la colección.']];
        }

        if ($class === ProductionOrder::class && $record->status === 'Aprobado') {
            $list[] = ['updated', $created->copy()->addDay(), ['status' => 'Pendiente'], ['status' => 'Aprobado']];
        }

        if ($class === Collection::class) {
            $list[] = ['updated', $created->copy()->addHours(1), ['description' => null], ['description' => 'Colección de calzado.']];
        }

        return $list;
    }

    /** Valores del registro tal como los guarda la auditoría. */
    private function snapshot($record): array
    {
        return collect($record->attributesToArray())
            ->except(['password', 'remember_token', 'created_at', 'updated_at', 'deleted_at'])
            ->map(fn ($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v)
            ->all();
    }

    private function row(string $type, $id, string $event, Carbon $when, array $old, array $new): array
    {
        $url = '/api/' . str(class_basename($type))->snake()->plural() . match ($event) {
            'created' => '/store',
            'deleted' => '/delete/' . $id,
            default => '/update/' . $id,
        };

        return [
            'user_type' => User::class,
            'user_id' => $this->users[$id % count($this->users)] ?? $this->users[0],
            'event' => $event,
            'auditable_type' => $type,
            'auditable_id' => $id,
            'old_values' => json_encode($old, JSON_UNESCAPED_UNICODE),
            'new_values' => json_encode($new, JSON_UNESCAPED_UNICODE),
            'url' => 'http://127.0.0.1:8099' . $url,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36',
            'tags' => self::TAG,
            'created_at' => $when,
            'updated_at' => $when,
        ];
    }
}
