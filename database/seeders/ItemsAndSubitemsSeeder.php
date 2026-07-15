<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\RiskManager;
use App\Models\BloodType;
use App\Models\Color;
use App\Models\CompensationFund;
use App\Models\DocumentType;
use App\Models\HealthEntity;
use App\Models\FileType;
use App\Models\Gender;
use App\Models\Item;
use App\Models\PensionFund;
use App\Models\PersonType;
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

        $file_type = new FileType();
        $file_type->name = 'Fotografía';
        $file_type->description = 'Fotografías o imágenes de personas, productos, instalaciones u otros.';
        $file_type->save();

        $file_type = new FileType();
        $file_type->name = 'Documento de identidad';
        $file_type->description = 'Documentos de identificación como cédula, pasaporte o NIT.';
        $file_type->save();

        $file_type = new FileType();
        $file_type->name = 'Documento adjunto';
        $file_type->description = 'Documentos de soporte asociados a un registro.';
        $file_type->save();

        $file_type = new FileType();
        $file_type->name = 'Comprobante';
        $file_type->description = 'Comprobantes de pago, consignaciones, recibos o facturas.';
        $file_type->save();

        $file_type = new FileType();
        $file_type->name = 'Firma';
        $file_type->description = 'Firmas manuscritas o digitales.';
        $file_type->save();

        $item = new Item();
        $item->name = 'Tipos de personas';
        $item->description = 'Listado de tipos de personas disponibles en la organización.';
        $item->save();

        $item = new Item();
        $item->name = 'Tipos de documentos';
        $item->description = 'Listado de tipos de documentos disponibles en la organización.';
        $item->save();

        $person_type = new PersonType();
        $person_type->name = 'Persona jurídica';
        $person_type->description = 'Entidad legal constituida conforme a la ley, con capacidad para adquirir derechos y contraer obligaciones.';
        $person_type->settings = (object) ['code' => 'PJ'];
        $person_type->save();

        $document_type = new DocumentType();
        $document_type->name = 'Número de Identificación Tributaria';
        $document_type->description = 'Número de identificación tributaria asignado por la DIAN a personas jurídicas.';
        $document_type->settings = (object) ['code' => 'NIT'];
        $document_type->save();

        $document_type->person_type()->sync([$person_type->id]);

        $person_type = new PersonType();
        $person_type->name = 'Persona natural';
        $person_type->description = 'Persona que actúa en nombre propio y ejerce derechos y obligaciones como individuo.';
        $person_type->settings = (object) ['code' => 'PN'];
        $person_type->save();

        $document_type = new DocumentType();
        $document_type->name = 'Cédula de ciudadanía';
        $document_type->description = 'Documento de identificación para ciudadanos colombianos mayores de edad.';
        $document_type->settings = (object) ['code' => 'CC'];
        $document_type->save();

        $document_type->person_type()->sync([$person_type->id]);

        $document_type = new DocumentType();
        $document_type->name = 'Tarjeta de identidad';
        $document_type->description = 'Documento de identificación para menores de edad colombianos.';
        $document_type->settings = (object) ['code' => 'TI'];
        $document_type->save();

        $document_type->person_type()->sync([$person_type->id]);

        $document_type = new DocumentType();
        $document_type->name = 'Cédula de extranjería';
        $document_type->description = 'Documento de identificación para extranjeros residentes en Colombia.';
        $document_type->settings = (object) ['code' => 'CE'];
        $document_type->save();

        $document_type->person_type()->sync([$person_type->id]);

        $document_type = new DocumentType();
        $document_type->name = 'Pasaporte';
        $document_type->description = 'Documento oficial de identificación para viajes internacionales.';
        $document_type->settings = (object) ['code' => 'PA'];
        $document_type->save();

        $document_type->person_type()->sync([$person_type->id]);

        $document_type = new DocumentType();
        $document_type->name = 'Permiso por Protección Temporal';
        $document_type->description = 'Documento expedido a migrantes acogidos al Estatuto Temporal de Protección.';
        $document_type->settings = (object) ['code' => 'PPT'];
        $document_type->save();

        $document_type->person_type()->sync([$person_type->id]);

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
