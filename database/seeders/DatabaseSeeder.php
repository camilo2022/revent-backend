<?php

namespace Database\Seeders;

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
            ErpPermissionsSeeder::class,
            ItemsAndSubitemsSeeder::class,
            ErpItemsSeeder::class,
            LocationsSeeder::class,
            UserSeeder::class,
            ErpNavigationSeeder::class,
            //ModulesAndSubmodulesSeeder::class
        ]);
    }
}
