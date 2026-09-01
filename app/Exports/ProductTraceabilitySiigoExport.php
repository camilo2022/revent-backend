<?php

namespace App\Exports;

use Carbon\Carbon;
use Generator;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class MasiveProductTraceabilitySiigoExport extends DefaultValueBinder implements FromGenerator, Responsable, WithHeadings, WithTitle, WithCustomValueBinder
{
    use Exportable;

    protected $codigos;
    protected $data;
    protected $invoices;
    protected $documents;

    public function __construct($codigos, $data)
    {
        $this->codigos = $codigos;
        $this->data = $data;
    }

    public function headings(): array
    {
        return [
            'BODEGA',
            'CODIGO PRODUCTO',
            'NOMBRE PRODUCTO',
            'REFERENCIA FABRICA',
            'COMPROBANTE',
            'SECUENCIA',
            'FECHA',
            'HORA',
            'CANTIDAD INICIAL',
            'CANTIDAD ENTRADA',
            'CANTIDAD SALIDA',
            'SALDO CANTIDADES',
            'VALOR ENTRADA',
            'VALOR SALIDA'
        ];
    }

    public function title(): string
    {
        return 'trazabilidad_de_producto';
    }

    public function generator(): Generator
    {
        Carbon::setLocale('es');

        foreach ($this->codigos as $codigo) {

            $items = collect($this->data[$codigo] ?? []);

            if($items->isNotEmpty()) {
                $saldo = null;

                foreach ($items as $item) {

                    $elaboration_date = Carbon::parse($item->ElaborationDate);

                    $date = $elaboration_date->format('d/m/Y');
                    $time = $elaboration_date->format('h:i A');

                    if (is_null($saldo)) {
                        $saldo = $item->InitialQuantityHidden ?? 0;
                    }

                    $saldo = $saldo + ($item->EntryQuantity ?? 0) - ($item->OutputQuantity ?? 0);

                    yield [
                        'BODEGA' => $item->Warehouse,
                        'CODIGO PRODUCTO' => $item->ProductCode,
                        'NOMBRE PRODUCTO' => $item->ProductName,
                        'REFERENCIA FABRICA' => $item->FactoryReference,
                        'COMPROBANTE' => $item->Voucher
                            ? '=HYPERLINK("' . $item->UrlHyperLink . '","' . $item->Voucher . '")'
                            : $item->Voucher,
                        'SECUENCIA' => $item->SEQUENCE,
                        'FECHA' => $date,
                        'HORA' => $time,
                        'CANTIDAD INICIAL' => $item->InitialQuantityHidden,
                        'CANTIDAD ENTRADA' => $item->EntryQuantity,
                        'CANTIDAD SALIDA' => $item->OutputQuantity,
                        'SALDO CANTIDADES' => $saldo,
                        'VALOR ENTRADA' => $item->EntryValue,
                        'VALOR SALIDA' => $item->OutputValue
                    ];
                }
            } else {
                yield [
                    'BODEGA' => '-',
                    'CODIGO PRODUCTO' => $codigo,
                    'NOMBRE PRODUCTO' => '-',
                    'REFERENCIA FABRICA' => null,
                    'COMPROBANTE' => null,
                    'SECUENCIA' => 0,
                    'FECHA' => null,
                    'HORA' => null,
                    'CANTIDAD INICIAL' => null,
                    'CANTIDAD ENTRADA' => null,
                    'CANTIDAD SALIDA' => null,
                    'SALDO CANTIDADES' => null,
                    'VALOR ENTRADA' => null,
                    'VALOR SALIDA' => null
                ];
            }
        }
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value) && str_starts_with($value, '=HYPERLINK(')) {
            $cell->setValueExplicit($value, DataType::TYPE_FORMULA);
            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
