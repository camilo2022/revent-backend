<?php

namespace Database\Seeders;

use App\Jobs\ImportMasiveTransferSiigoJob;
use App\Models\Area;
use App\Models\BloodType;
use App\Models\CompensationFund;
use App\Models\Country;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\Gender;
use App\Models\HealthEntity;
use App\Models\PensionFund;
use App\Models\Permission;
use App\Models\Person;
use App\Models\Position;
use App\Models\RiskManager;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Cada bodega de Siigo es una tienda del ERP: se crea (o se reutiliza por nombre) y queda mapeada con el id de
 * su bodega y el de su tránsito. Para cada tienda se crea su persona, empleado (área Comercial, cargo Tienda,
 * "No aplica" en salud y seguridad social) y usuario, asociado a su tienda; más el supervisor `operaciones` con
 * acceso a todas las bodegas. Las bodegas se leen de ImportMasiveTransferSiigoJob::BODEGAS sin modificarlo.
 * Idempotente: no duplica tiendas ni usuarios y no cambia contraseñas de usuarios que ya existen.
 *   php artisan db:seed --class=StoreWarehousesSeeder   (después de IntegrationPermissionsSeeder)
 * Las contraseñas iniciales se muestran UNA sola vez, al crearlas; no se guardan en ningún archivo.
 */
class StoreWarehousesSeeder extends Seeder
{
    private const SUPERVISOR = 'operaciones';

    /** Roles que recibe por defecto el usuario de cada tienda. */
    private const STORE_ROLES = [
        'integration.transfer_approval',
        'integration.masive_transfers',
        'integration.inventory_filter',
        'integration.purchase_order_reception',
        'integration.credentials',
    ];

    private const NOT_APPLICABLE = 'No aplica';

    private Country $country;

    private string $domain;

    private array $passwords = [];

    public function run(): void
    {
        $this->country = Country::where('iso3', 'COL')->first() ?? Country::firstOrFail();
        $this->domain = config('siigo.mail.domain', 'revent.com.co');

        $warehouses = (new \ReflectionClassConstant(ImportMasiveTransferSiigoJob::class, 'BODEGAS'))->getValue()['DIRECTO'];

        $position = $this->commercialPosition();

        // Tiendas primero: cada bodega de Siigo es una tienda
        $stores = [];

        foreach ($warehouses as $warehouseId => $warehouse) {
            $stores[$warehouseId] = $this->store((int) $warehouseId, $warehouse);
        }

        // El supervisor se crea antes que las tiendas para quedarse con el correo `operaciones@...` que repiten varias bodegas
        $this->supervisor($position, $stores[-1] ?? null);

        foreach ($warehouses as $warehouseId => $warehouse) {
            // SIN ASIGNAR (-1) es solo una tienda, la opera el supervisor
            if ((int) $warehouseId === -1) {
                continue;
            }

            $this->storeUser($stores[$warehouseId], $warehouse, $position);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->report(count($warehouses));
    }

    private function store(int $warehouseId, array $warehouse): Store
    {
        $name = $this->storeName($warehouse['name']);
        $key = $this->key($name);

        $store = Store::withTrashed()->where('siigo_warehouse_id', $warehouseId)->first()
            ?? Store::withTrashed()->get()->first(fn (Store $store) => $this->key($store->name) === $key);

        if (!$store) {
            $store = Store::create([
                'code' => $this->storeCode($key),
                'name' => $name,
                'location_id' => $this->country->id,
                'location_type' => Country::class,
                'address' => 'Por definir',
                'neighborhood' => 'Por definir',
            ]);
        }

        $transit = $warehouse['transito'] ?? null;
        $transitTaken = $transit !== null
            && Store::withTrashed()->where('siigo_transit_warehouse_id', $transit)->where('id', '!=', $store->id)->exists();

        $store->siigo_warehouse_id = $warehouseId;
        $store->siigo_transit_warehouse_id = $transitTaken ? $store->siigo_transit_warehouse_id : $transit;

        // Correo de notificaciones: el mismo al que ya llegan los traslados de la bodega (si la tienda no tiene uno)
        if (!$store->email && !empty($warehouse['emails'][0])) {
            $email = $warehouse['emails'][0];
            $store->email = str_contains($email, '@') ? $email : "{$email}@{$this->domain}";
        }

        $store->save();

        return $store;
    }

    private function supervisor(Position $position, ?Store $unassigned): void
    {
        if (User::where('username', self::SUPERVISOR)->exists()) {
            return;
        }

        $user = $this->createUser(self::SUPERVISOR, 'Operaciones', 'Revent', self::SUPERVISOR, $position);

        // Todo lo de integraciones y acceso a todas las bodegas
        $roles = Role::where('name', 'like', 'integration.%')->get();
        $user->assignRole($roles);
        $user->givePermissionTo(Permission::where('name', 'like', 'integration.%')->get());
        $user->givePermissionTo(Permission::where('name', 'stores.access_all')->first() ?? []);

        if ($unassigned) {
            $user->stores()->syncWithoutDetaching([$unassigned->id]);
        }
    }

    private function storeUser(Store $store, array $warehouse, Position $position): void
    {
        $username = $this->username($store->name);

        if (User::where('username', $username)->exists()) {
            return;
        }

        $email = ($warehouse['emails'] ?? [])[0] ?? null;

        $user = $this->createUser($username, 'Tienda', $this->lastNames($store->name), $email, $position);

        $roles = Role::whereIn('name', self::STORE_ROLES)->get();
        $user->assignRole($roles);
        $user->givePermissionTo($roles->flatMap->permissions->unique('id'));
        $user->stores()->syncWithoutDetaching([$store->id]);
    }

    private function createUser(string $username, string $names, string $lastNames, ?string $email, Position $position): User
    {
        $person = Person::create([
            'names' => $names,
            'last_names' => $lastNames,
            'document_type_id' => (DocumentType::where('settings->code', 'NIT')->first() ?? DocumentType::first())->id,
            'document' => $this->nextUnique('people', 'document', 9900000001),
            'gender_id' => $this->notApplicable(Gender::class)->id,
            'birth_date' => '2000-01-01',
            'blood_type_id' => $this->notApplicable(BloodType::class)->id,
            'location_id' => $this->country->id,
            'location_type' => Country::class,
            'address' => 'Por definir',
            'neighborhood' => 'Por definir',
            'phone_country_id' => $this->country->id,
            'phone' => $this->nextUnique('people', 'phone', 3000000001),
            'email' => $this->email($email, $username),
        ]);

        $employee = Employee::create([
            'person_id' => $person->id,
            'position_id' => $position->id,
            'risk_manager_id' => $this->notApplicable(RiskManager::class)->id,
            'health_entity_id' => $this->notApplicable(HealthEntity::class)->id,
            'pension_fund_id' => $this->notApplicable(PensionFund::class)->id,
            'compensation_fund_id' => $this->notApplicable(CompensationFund::class)->id,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => null,
        ]);

        $password = Str::password(16);

        $user = new User();
        $user->employee_id = $employee->id;
        $user->username = $username;
        $user->password = $password;
        $user->save();

        $this->passwords[] = [$username, $password];

        return $user;
    }

    /** Cargo TIENDA dentro del área COMERCIAL. */
    private function commercialPosition(): Position
    {
        $area = Area::firstOrCreate(['name' => 'COMERCIAL'], ['description' => 'Área comercial.']);

        $position = Position::firstOrCreate(['name' => 'TIENDA'], ['description' => 'Usuario de tienda.']);
        $position->area()->syncWithoutDetaching([$area->id]);

        return $position;
    }

    private function notApplicable(string $model)
    {
        return $model::firstOrCreate(['name' => self::NOT_APPLICABLE], ['description' => self::NOT_APPLICABLE]);
    }

    /** Siguiente valor numérico libre de la columna, empezando en $start. */
    private function nextUnique(string $table, string $column, int $start): string
    {
        $max = \DB::table($table)->where($column, 'like', substr((string) $start, 0, 8) . '%')->max($column);
        $value = $max ? ((int) $max) + 1 : $start;

        while (\DB::table($table)->where($column, (string) $value)->exists()) {
            $value++;
        }

        return (string) $value;
    }

    private function email(?string $email, string $username): string
    {
        $email = $email ? (str_contains($email, '@') ? $email : "{$email}@{$this->domain}") : null;

        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL) && !Person::where('email', $email)->exists()) {
            return $email;
        }

        return "{$username}@{$this->domain}";
    }

    /** "P R I N C I P A L" => "PRINCIPAL"; el resto queda igual. */
    private function storeName(string $name): string
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));

        return preg_match('/^(\S ){2,}\S$/', $name) ? str_replace(' ', '', $name) : $name;
    }

    /** Nombre sin tildes, mayúsculas y solo letras y números, para comparar tiendas por nombre. */
    private function key(string $name): string
    {
        return preg_replace('/[^A-Z0-9]/', '', Str::upper(Str::ascii($name)));
    }

    private function username(string $name): string
    {
        return Str::lower($this->key($name));
    }

    /** Apellidos de la persona: el nombre de la tienda solo con letras y espacios. */
    private function lastNames(string $name): string
    {
        $lastNames = trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Za-zÁÉÍÓÚáéíóúÑñ\s]/u', ' ', $name)));

        return $lastNames !== '' ? $lastNames : 'Sin nombre';
    }

    /** Código de la tienda: las primeras 3 letras; si ya existe, 4 o 5 letras y luego un número. */
    private function storeCode(string $key): string
    {
        $candidates = [substr($key, 0, 3), substr($key, 0, 4), substr($key, 0, 5)];

        for ($i = 1; $i <= 9; $i++) {
            $candidates[] = substr($key, 0, 4) . $i;
        }

        foreach ($candidates as $code) {
            if ($code !== '' && !Store::withTrashed()->where('code', $code)->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException("No se pudo generar un código libre para la tienda {$key}.");
    }

    private function report(int $warehouses): void
    {
        $this->command?->info("Tiendas mapeadas con bodegas de Siigo: {$warehouses}. Usuarios creados en esta corrida: " . count($this->passwords) . '.');

        if ($this->passwords) {
            $this->command?->warn('Contraseñas iniciales (se muestran una sola vez; guárdalas ahora):');
            $this->command?->table(['Usuario', 'Contraseña'], $this->passwords);
        }
    }
}
