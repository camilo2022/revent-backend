<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Area;
use App\Models\RiskManager;
use App\Models\BloodType;
use App\Models\CompensationFund;
use App\Models\Employee;
use App\Models\HealthEntity;
use App\Models\Gender;
use App\Models\PensionFund;
use App\Models\Person;
use App\Models\Position;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $baseUrl = 'http://45.76.251.153/API_GT/api';

        $username = config('services.siesa.username');
        $password = config('services.siesa.password');

        $token = Http::asForm()->post("$baseUrl/login/authenticate", [
            'Username' => $username,
            'Password' => $password
        ])
            ->throw()
            ->body();

        $token = trim($token, '"');

        $response = Http::withToken($token)->get("$baseUrl/orgBless/getEmpleados")->throw();

        $items = $response->json('detail');

        foreach ($items as $item) {
            $area = Area::firstOrCreate(
                ['name' => $item['Area']],
                ['description' => $item['Area']]
            );

            $position = Position::firstOrCreate(
                ['name' => $item['Cargo']],
                ['description' => $item['Cargo']]
            );

            $position->area()->sync([$area->id]);

            $gender = Gender::firstOrCreate(
                ['name' => $item['Genero']],
                ['description' => $item['Genero'] == 'F' ? 'Femenino' : 'Masculino']
            );

            $blood_type = BloodType::firstOrCreate(
                ['name' => $item['TipoSangre']],
                ['description' => $item['TipoSangre']]
            );

            $risk_manager = RiskManager::firstOrCreate(
                ['name' => $item['ARL']],
                ['description' => $item['ARL']]
            );

            $health_entity = HealthEntity::firstOrCreate(
                ['name' => $item['EPS']],
                ['description' => $item['EPS']]
            );

            $pension_fund = PensionFund::firstOrCreate(
                ['name' => $item['FondoPension']],
                ['description' => $item['FondoPension']]
            );

            $compensation_fund = CompensationFund::firstOrCreate(
                ['name' => $item['CajaCompensacion']],
                ['description' => $item['CajaCompensacion']]
            );

            $person = new Person();
            $person->document = $item['Documento'];
            $person->names = $item['Nombres'];
            $person->last_names = $item['Apellidos'];
            $person->gender_id = $gender->id;
            $person->birth_date = $item['FechaNacimiento'];
            $person->blood_type_id = $blood_type->id;
            $person->address = $item['Direccion'];
            $person->phone = $item['Celular'];
            $person->save();

            $employee = new Employee();
            $employee->person_id = $person->id;
            $employee->operation_center = $item['CentroOperacion'];
            $employee->position_id = $position->id;
            $employee->risk_manager_id = $risk_manager->id;
            $employee->health_entity_id = $health_entity->id;
            $employee->pension_fund_id = $pension_fund->id;
            $employee->compensation_fund_id = $compensation_fund->id;
            $employee->start_date = !empty($item['FechaIngreso']) ? Carbon::createFromFormat('d/m/Y h:i:s A', $item['FechaIngreso'])->format('Y-m-d H:i:s') : null;
            $employee->end_date = !empty($item['FechaTerminacion']) ? Carbon::createFromFormat('d/m/Y h:i:s A', $item['FechaTerminacion'])->format('Y-m-d H:i:s') : null;
            $employee->save();
        }
    }
}
