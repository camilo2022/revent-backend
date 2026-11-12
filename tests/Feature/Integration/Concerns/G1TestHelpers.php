<?php

namespace Tests\Feature\Integration\Concerns;

use App\Models\User;
use App\Services\Siigo\SiigoCatalogService;
use App\Services\Siigo\SiigoClient;
use App\Services\Siigo\SiigoUserService;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Ayudas de las pruebas de los submódulos de integración (exportes, sync, traslados masivos, trazabilidad, clientes por venta).
 * Se apoyan en los usuarios sembrados: `alegra` (tienda con bodegas 3 y 70) y `operaciones` (todas las bodegas).
 * Las pruebas corren dentro de una transacción (DatabaseTransactions), así los permisos que se cambian aquí se deshacen.
 */
trait G1TestHelpers
{
    /** Inicia sesión con un usuario de tienda que solo tiene los permisos indicados. */
    protected function actingWith(array $permissions, string $username = 'alegra'): User
    {
        $user = User::where('username', $username)->firstOrFail();
        $user->syncRoles([]);
        $user->syncPermissions($permissions);

        $user = $user->fresh();
        Sanctum::actingAs($user);

        return $user;
    }

    /** Valor de una propiedad (incluso privada) de un job, para comparar sus parámetros. */
    protected function jobValue(object $job, string $property): mixed
    {
        $reflection = new \ReflectionProperty($job, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($job);
    }

    /** El token de la API pública no se pide a Siigo en las pruebas. */
    protected function fakeSiigoToken(): void
    {
        $this->mock(SiigoClient::class, function ($mock) {
            $mock->shouldReceive('token')->andReturn('token-api-de-prueba');
        });
    }

    /** Bodegas de Siigo (como las entrega el catálogo) para las pruebas. */
    protected function fakeCatalog(array $warehousesWithTransit): void
    {
        $this->fakeSiigoToken();

        $this->mock(SiigoCatalogService::class, function ($mock) use ($warehousesWithTransit) {
            $mock->shouldReceive('warehousesWithTransit')->andReturn($warehousesWithTransit);
            $mock->shouldReceive('warehouses')->andReturn(
                array_values(array_map(fn ($w) => ['id' => $w['id'], 'name' => $w['name']], array_filter($warehousesWithTransit, fn ($w) => $w['id'] !== -1)))
            );
        });
    }

    /** El token del usuario de Siigo se valida sin salir a la red. */
    protected function fakeSiigoUser(bool $valid = true, int $id = 597): void
    {
        $this->mock(SiigoUserService::class, function ($mock) use ($valid, $id) {
            $mock->shouldReceive('fromToken')->andReturn($valid
                ? ['success' => true, 'data' => ['id' => $id, 'user' => 'operaciones@revent.com.co', 'name' => 'Operaciones'], 'message' => 'ok']
                : ['success' => false, 'data' => [], 'message' => 'Error de autenticación']);
        });
    }

    /** Token con la forma de un JWT (la regla solo revisa el formato antes de consultar a Siigo). */
    protected function jwt(): string
    {
        return 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxIn0.firma-de-prueba';
    }

    /**
     * Excel de prueba: [hoja => filas], donde la primera fila de cada hoja son los encabezados.
     *
     * @param array<string, array<int, array<int, mixed>>> $sheets
     */
    protected function xlsx(array $sheets, string $name = 'archivo.xlsx'): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($sheets as $title => $rows) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($title);
            $sheet->fromArray($rows, null, 'A1');
        }

        $path = tempnam(sys_get_temp_dir(), 'g1xlsx');
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

        return new UploadedFile($path, $name, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
