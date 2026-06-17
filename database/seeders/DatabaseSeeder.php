<?php

namespace Database\Seeders;

use Database\Seeders\UserSeeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            ItemsAndSubitemsSeeder::class,
            UserSeeder::class,
            ModulesAndSubmodulesSeeder::class,
            EmployeeSeeder::class
        ]);
    }
}
