<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\RiskManager;
use App\Models\BloodType;
use App\Models\Color;
use App\Models\CompensationFund;
use App\Models\HealthEntity;
use App\Models\FileType;
use App\Models\Gender;
use App\Models\Item;
use App\Models\PensionFund;
use App\Models\Position;
use App\Models\Size;
use App\Models\Trademark;
use Illuminate\Database\Seeder;

class ItemsAndSubitemsSeeder extends Seeder
{
    public function run(): void
    {
        $item = new Item();
        $item->name = 'Tipos de archivos';
        $item->description = 'Listado de tipos de archivos en la organización.';
        $item->save();

        $file_type = new FileType();
        $file_type->name = 'No aplica';
        $file_type->description = 'No aplica';
        $file_type->save();

        $item = new Item();
        $item->name = 'Áreas';
        $item->description = 'Listado de áreas disponibles en la organización.';
        $item->save();

        $area = new Area();
        $area->name = 'No aplica';
        $area->description = 'No aplica';
        $area->save();

        $item = new Item();
        $item->name = 'Cargos';
        $item->description = 'Listado de cargos disponibles en la organización.';
        $item->save();

        $position = new Position();
        $position->name = 'No aplica';
        $position->description = 'No aplica';
        $position->save();

        $position->area()->sync([$area->id]);

        $item = new Item();
        $item->name = 'Géneros';
        $item->description = 'Listado de géneros.';
        $item->save();

        $gender = new Gender();
        $gender->name = 'No aplica';
        $gender->description = 'No aplica';
        $gender->save();

        $item = new Item();
        $item->name = 'Tipos de sangre';
        $item->description = 'Listado de tipos de sangre.';
        $item->save();

        $blood_type = new BloodType();
        $blood_type->name = 'No aplica';
        $blood_type->description = 'No aplica';
        $blood_type->save();

        $item = new Item();
        $item->name = 'Administradora de Riesgos';
        $item->description = 'Listado de administradoras de riesgos laborales.';
        $item->save();

        $risk_manager = new RiskManager();
        $risk_manager->name = 'No aplica';
        $risk_manager->description = 'No aplica';
        $risk_manager->save();

        $item = new Item();
        $item->name = 'Entidades de Salud';
        $item->description = 'Listado de entidades promotoras de salud.';
        $item->save();

        $health_entity = new HealthEntity();
        $health_entity->name = 'No aplica';
        $health_entity->description = 'No aplica';
        $health_entity->save();

        $item = new Item();
        $item->name = 'Fondos de pensión';
        $item->description = 'Listado de fondos de pensión.';
        $item->save();

        $pension_fund = new PensionFund();
        $pension_fund->name = 'No aplica';
        $pension_fund->description = 'No aplica';
        $pension_fund->save();

        $item = new Item();
        $item->name = 'Cajas de compensación';
        $item->description = 'Listado de cajas de compensación.';
        $item->save();

        $compensation_fund = new CompensationFund();
        $compensation_fund->name = 'No aplica';
        $compensation_fund->description = 'No aplica';
        $compensation_fund->save();

        $item = new Item();
        $item->name = 'Marcas';
        $item->description = 'Listado de marcas disponibles.';
        $item->save();

        $trademark = new Trademark();
        $trademark->name = 'REVENT';
        $trademark->description = 'Marca de la empresa REVENT S.A.S.';
        $trademark->settings = (object) ['code' => 'RV'];
        $trademark->save();

        $item = new Item();
        $item->name = 'Colores';
        $item->description = 'Listado de colores disponibles.';
        $item->save();

        $item = new Item();
        $item->name = 'Tallas';
        $item->description = 'Listado de tallas disponibles.';
        $item->save();

        $item = new Item();
        $item->name = 'Categorías';
        $item->description = 'Listado de categorías disponibles.';
        $item->save();

        $item = new Item();
        $item->name = 'Subcategorías';
        $item->description = 'Listado de subcategorías disponibles.';
        $item->save();
    }
}
