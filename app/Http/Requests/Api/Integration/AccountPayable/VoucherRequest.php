<?php

namespace App\Http\Requests\Api\Integration\AccountPayable;

use App\Http\Requests\Api\Integration\IntegrationRequest;

/**
 * Campos comunes de un recibo de pago, anticipo o cruce contable. El proveedor y las listas llegan como arrays
 * (multipart `proveedor[AccountID]`, `documentos[0][doc][DueName]`...), ya no como JSON dentro de un texto.
 */
abstract class VoucherRequest extends IntegrationRequest
{
    /** Decimales con los que se comparan las sumas de valores. */
    protected const DECIMALS = 2;

    protected function provider(): array
    {
        return [
            'proveedor' => ['required', 'array'],
            'proveedor.AccountID' => ['required', 'integer', 'min:1'],
            'proveedor.MsThirdPartyID' => ['required', 'string', 'max:50'],
        ];
    }

    /** Suma de una clave numérica sobre una lista; lo no numérico cuenta 0 porque cada fila ya se valida aparte. */
    protected function sumOf(string $list, string $key): float
    {
        return round(collect($this->input($list, []))->sum(fn ($row) => is_numeric($row[$key] ?? null) ? (float) $row[$key] : 0), self::DECIMALS);
    }

    public function messages(): array
    {
        return parent::messages() + [
            'min.array' => 'Debes seleccionar al menos un elemento.',
            'distinct' => 'El documento está repetido.',
            'valor.min' => 'El valor debe ser mayor a cero.',
            'documentos.*.covered.min' => 'El valor aplicado debe ser mayor a cero.',
            'recibos.*.used.min' => 'El valor usado debe ser mayor a cero.',
            'comprobante.max' => 'El comprobante no debe superar 10 MB.',
        ];
    }

    public function attributes(): array
    {
        return parent::attributes() + [
            'proveedor' => 'proveedor',
            'proveedor.AccountID' => 'cuenta del proveedor',
            'proveedor.MsThirdPartyID' => 'identificador del tercero',
            'tipo' => 'tipo',
            'accion' => 'acción',
            'origen' => 'origen',
            'fecha' => 'fecha',
            'observaciones' => 'observaciones',
            'valor' => 'valor',
            'documentos' => 'documentos',
            'documentos.*.doc' => 'documento',
            'documentos.*.doc.DueName' => 'número del documento',
            'documentos.*.doc.ACEntryID' => 'identificador de la compra',
            'documentos.*.covered' => 'valor aplicado',
            'recibos' => 'recibos',
            'recibos.*.doc' => 'recibo',
            'recibos.*.doc.DueName' => 'número del recibo',
            'recibos.*.used' => 'valor usado',
            'comprobante' => 'comprobante',
        ];
    }
}
