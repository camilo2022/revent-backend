<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::all();
        $permissions = Permission::all();

        $person = new Person();
        $person->document = '0000000000';
        $person->names = 'Super';
        $person->last_names = 'Admin';
        $person->gender_id = 3;
        $person->birth_date = '1990-01-01';
        $person->blood_type_id = 4;
        $person->address = 'Dirección principal';
        $person->phone = '0000000000';
        $person->save();

        $employee = new Employee();
        $employee->person_id = $person->id;
        $employee->operation_center = 'Principal';
        $employee->position_id = 2;
        $employee->risk_manager_id = 5;
        $employee->health_entity_id = 6;
        $employee->pension_fund_id = 7;
        $employee->compensation_fund_id = 8;
        $employee->start_date = Carbon::now();
        $employee->end_date = null;
        $employee->save();

        $user = new User();
        $user->employee_id = $employee->id;
        $user->email = 'superadmin@kanri.com';
        $user->password = Hash::make('P4ssw0rd.');
        $user->save();

        $user->syncRoles($roles);
        $user->syncPermissions($permissions);
    }
}
