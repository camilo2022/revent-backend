<?php


namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class InventorySiigoExport implements FromArray, Responsable, WithHeadings, WithTitle
{
    use Exportable;

    private $inventory;

    public function __construct($inventory)
    {
        $this->inventory = $inventory;
    }

    public function headings(): array
    {
        return [
            'CODIGO',
            'DESCRIPCION',
            'NOMBRE',
            'COLOR',
            'CATEGORIA',
            'TAMAÑO',
            'CODIGO_BARRAS',
            'MARCA',
            'MODELO',
            'BODEGA',
            'CANTIDAD'
        ];
    }

    public function title(): string
    {
        return 'inventario_siigo';
    }

    public function array(): array
    {
       $array = [];
       $rows = $this->inventory;
            $i=0;

            foreach($rows as $row){
                $fila = [
                    'CODIGO' => $row['code'],
                    'DESCRIPCION' => $row['description'],
                    'NOMBRE' => $row['name'],
                    'COLOR' => $row['color'],
                    'CATEGORIA' => $row['category'],
                    'TAMAÑO' => $row['size'],
                    'CODIGO_BARRAS' => $row['barcode'],
                    'MARCA' => $row['brand'],
                    'MODELO' => $row['model'],
                    'BODEGA' => $row['warehouse'],
                    'CANTIDAD' => $row['quantity']
                ];
                array_push($array,$fila);
                $i++;
            }

       return $array;
    }


}
