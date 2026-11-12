<?php

namespace App\Http\Requests\Api\Integration\PurchaseOrderReception;

use App\Http\Requests\Api\Integration\IntegrationRequest;
use App\Rules\Integration\AccessibleWarehouse;
use Illuminate\Validation\Validator;

/**
 * Confirma la recepción de una orden de compra: token de Siigo, los datos de la recepción (`receiving`, una lista
 * con la orden, los ítems, los totales y la lista de verificación) y las fotos de evidencia.
 * Cada bodega de los ítems recibidos debe estar entre las tiendas del usuario.
 */
class ConfirmRequest extends IntegrationRequest
{
    private const CHECKLIST_FLAGS = [
        'cajas_master', 'sellos', 'estado_cajas', 'documentos', 'empaque', 'stickers', 'producto', 'exactitud',
        'variacion_diseno', 'firma', 'reporte_whatsapp', 'respaldo_whatsapp', 'alerta', 'venta',
    ];

    /** Acepta `receiving` como lista o como texto JSON, y los valores "true"/"false" que genera un FormData. */
    protected function prepareForValidation(): void
    {
        $receiving = $this->input('receiving');

        if (is_string($receiving)) {
            $decoded = json_decode($receiving, true);
            $receiving = is_array($decoded) ? $decoded : $receiving;
        }

        if (is_array($receiving) && is_array($receiving['Checklist'] ?? null)) {
            foreach (self::CHECKLIST_FLAGS as $flag) {
                $value = $receiving['Checklist'][$flag] ?? null;

                if ($value === 'true' || $value === 'false') {
                    $receiving['Checklist'][$flag] = $value === 'true';
                }
            }
        }

        $this->merge(['receiving' => $receiving]);
    }

    public function rules(): array
    {
        $flags = collect(self::CHECKLIST_FLAGS)->mapWithKeys(fn ($flag) => ["receiving.Checklist.{$flag}" => ['required', 'boolean']])->all();

        return [
            'token' => ['required', 'string', $this->siigoToken()],

            'receiving' => ['required', 'array'],
            'receiving.Order' => ['required', 'array'],
            'receiving.Order.ACEntryID' => ['required'],
            'receiving.Order.DocName' => ['required', 'string'],
            'receiving.Order.DocDate' => ['required', 'string'],
            'receiving.Order.Observations' => ['nullable', 'string'],
            'receiving.Order.Warehouse' => ['nullable', 'string'],
            'receiving.Items' => ['nullable', 'array'],
            'receiving.Items.*.WarehouseCode' => ['nullable', 'integer'],
            'receiving.Items.*.ReceivingQuantity' => ['nullable', 'numeric', 'min:0'],
            'receiving.Totals' => ['nullable', 'array'],
            'receiving.Totals.Requested' => ['nullable', 'numeric'],
            'receiving.Totals.Receiving' => ['nullable', 'numeric'],
            'receiving.Totals.Pending' => ['nullable', 'numeric'],

            'receiving.Checklist' => ['required', 'array'],
            ...$flags,
            'receiving.Checklist.referencias_recibidas' => ['nullable', 'string'],
            'receiving.Checklist.observaciones' => ['required', 'string'],
            'receiving.Checklist.responsable' => ['required', 'array'],
            'receiving.Checklist.responsable.nombre' => ['required', 'string', 'max:150'],
            'receiving.Checklist.responsable.cargo' => ['required', 'string', 'max:150'],
            'receiving.Checklist.responsable.departamento' => ['required', 'string', 'max:150'],
            'receiving.Checklist.responsable.celular' => ['nullable', 'string', 'max:30'],

            'imagenes' => ['required', 'array', 'min:1'],
            'imagenes.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:8192'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $checked = [];

            foreach ((array) $this->input('receiving.Items', []) as $position => $item) {
                $code = $item['WarehouseCode'] ?? null;

                if ($code === null || $code === '' || isset($checked[(string) $code])) {
                    continue;
                }

                $checked[(string) $code] = true;

                (new AccessibleWarehouse())->validate("receiving.Items.{$position}.WarehouseCode", $code, function (string $message) use ($validator, $position) {
                    $validator->errors()->add("receiving.Items.{$position}.WarehouseCode", $message);
                });
            }
        }];
    }

    /** Los datos de la recepción completos (ya validados), tal como los espera el correo de confirmación. */
    public function receivingData(): array
    {
        return (array) $this->input('receiving');
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'token.required' => 'Carga tu token de Siigo en Autorización Siigo.',
            'receiving.required' => 'Faltan los datos de la recepción.',
            'imagenes.required' => 'Debes adjuntar al menos una imagen de evidencia.',
            'imagenes.min' => 'Debes adjuntar al menos :min imagen.',
            'imagenes.*.image' => 'El archivo debe ser una imagen.',
            'imagenes.*.mimes' => 'La imagen debe ser JPG o PNG.',
            'imagenes.*.max' => 'Cada imagen debe pesar máximo :max KB.',
            'max.string' => 'El campo admite máximo :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'receiving' => 'Datos de recepción',
            'receiving.Order' => 'Orden',
            'receiving.Order.ACEntryID' => 'Id de la orden',
            'receiving.Order.DocName' => 'Nombre del documento',
            'receiving.Order.DocDate' => 'Fecha del documento',
            'receiving.Items' => 'Ítems',
            'receiving.Items.*.WarehouseCode' => 'Bodega del ítem',
            'receiving.Items.*.ReceivingQuantity' => 'Cantidad recibida',
            'receiving.Totals' => 'Totales',
            'receiving.Checklist' => 'Lista de verificación',
            'receiving.Checklist.observaciones' => 'Observaciones',
            'receiving.Checklist.responsable' => 'Responsable',
            'receiving.Checklist.responsable.nombre' => 'Nombre del responsable',
            'receiving.Checklist.responsable.cargo' => 'Cargo del responsable',
            'receiving.Checklist.responsable.departamento' => 'Departamento del responsable',
            'receiving.Checklist.responsable.celular' => 'Celular del responsable',
            'imagenes' => 'Imágenes',
            'imagenes.*' => 'Imagen',
        ];
    }
}
