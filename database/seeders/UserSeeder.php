<?php

namespace Database\Seeders;

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
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::all();
        $permissions = Permission::all();

        $person = new Person();
        $person->names = 'REVENT';
        $person->last_names = 'CALZADO';
        $person->document_type_id = DocumentType::first()->id;
        $person->document = '901582704-1';
        $person->gender_id = Gender::first()->id;
        $person->birth_date = '2000-01-01';
        $person->blood_type_id = BloodType::first()->id;
        $person->location_id = Country::where('iso3', 'COL')->first()->id;
        $person->location_type = Country::class;
        $person->address = 'Dirección';
        $person->neighborhood = 'Barrio';
        $person->phone_country_id = Country::where('iso3', 'COL')->first()->id;
        $person->phone = '0000000000';
        $person->email = 'tecnologia@revent.com.co';
        $person->save();

        $employee = new Employee();
        $employee->person_id = $person->id;
        $employee->position_id = Position::first()->id;
        $employee->risk_manager_id = RiskManager::first()->id;
        $employee->health_entity_id = HealthEntity::first()->id;
        $employee->pension_fund_id = PensionFund::first()->id;
        $employee->compensation_fund_id = CompensationFund::first()->id;
        $employee->start_date = Carbon::now()->format('Y-m-d');
        $employee->end_date = null;
        $employee->save();

        $user = new User();
        $user->employee_id = $employee->id;
        $user->username = 'revent';
        $user->password = Hash::make('R3V3N72026.');
        $user->save();

        $user->syncRoles($roles);
        $user->syncPermissions($permissions);
    }
}
