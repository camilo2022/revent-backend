<?php

namespace App\Http\Requests\Integration\InvoicePurchaseOrder;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

class ConfirmedRequest extends FormRequest
{
    /** Respuesta de error propia del módulo: {success: false, message: primer error, errors}. */
    protected function failedValidation(ValidatorContract $validator)
    {
        $errors = $validator->errors()->messages();

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => collect($errors)->flatten()->first() ?? 'Datos inválidos.',
            'errors' => $errors,
        ], 422));
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'receivingData' => ['required', 'string'],
            'imagenes' => ['required', 'array', 'min:1'],
            'imagenes.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:8192'],
        ];
    }

    /** Reglas del JSON de recepción (receivingData), ya decodificado. */
    public function receivingRules(): array
    {
        return [
            'Order' => ['required', 'array'],
            'Order.ACEntryID' => ['required'],
            'Order.DocName' => ['required', 'string'],
            'Order.DocDate' => ['required', 'string'],
            'Order.Observations' => ['nullable', 'string'],
            'Order.Warehouse' => ['nullable', 'string'],

            'Items' => ['nullable', 'array'],

            'Totals' => ['nullable', 'array'],
            'Totals.Requested' => ['nullable', 'numeric'],
            'Totals.Receiving' => ['nullable', 'numeric'],
            'Totals.Pending' => ['nullable', 'numeric'],

            'Checklist' => ['required', 'array'],
            'Checklist.cajas_master' => ['required', 'boolean'],
            'Checklist.sellos' => ['required', 'boolean'],
            'Checklist.estado_cajas' => ['required', 'boolean'],
            'Checklist.documentos' => ['required', 'boolean'],
            'Checklist.empaque' => ['required', 'boolean'],
            'Checklist.stickers' => ['required', 'boolean'],
            'Checklist.producto' => ['required', 'boolean'],
            'Checklist.exactitud' => ['required', 'boolean'],
            'Checklist.variacion_diseno' => ['required', 'boolean'],
            'Checklist.firma' => ['required', 'boolean'],
            'Checklist.reporte_whatsapp' => ['required', 'boolean'],
            'Checklist.respaldo_whatsapp' => ['required', 'boolean'],
            'Checklist.alerta' => ['required', 'boolean'],
            'Checklist.venta' => ['required', 'boolean'],
            'Checklist.referencias_recibidas' => ['nullable', 'string'],
            'Checklist.observaciones' => ['required', 'string'],

            'Checklist.responsable' => ['required', 'array'],
            'Checklist.responsable.nombre' => ['required', 'string', 'max:150'],
            'Checklist.responsable.cargo' => ['required', 'string', 'max:150'],
            'Checklist.responsable.departamento' => ['required', 'string', 'max:150'],
            'Checklist.responsable.celular' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * Tras validar los campos del formulario, valida el contenido del JSON de recepción
     * (solo si lo anterior pasó, para no procesar un payload ya inválido).
     */
    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $data = json_decode($this->input('receivingData'), true);

            if (!is_array($data)) {
                $validator->errors()->add('receivingData', 'Los datos de recepción no tienen un formato válido.');

                return;
            }

            $inner = Validator::make($data, $this->receivingRules(), $this->messages(), $this->attributes());

            foreach ($inner->errors()->messages() as $key => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($key, $message);
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser una cadena de texto.',
            'array' => 'El campo :attribute debe ser un arreglo.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'boolean' => 'El campo :attribute debe ser verdadero o falso.',
            'file' => 'El campo :attribute debe ser un archivo.',
            'image' => 'El archivo :attribute debe ser una imagen.',
            'mimes' => 'El archivo :attribute debe ser de tipo: :values.',
            'min.array' => 'Debes adjuntar al menos :min :attribute.',
            'max.array' => 'Se permiten máximo :max :attribute.',
            'max.file' => 'El archivo :attribute no debe superar :max KB.',
            'max.string' => 'El campo :attribute admite máximo :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'token' => 'token',
            'receivingData' => 'datos de recepción',
            'imagenes' => 'imágenes',
            'imagenes.*' => 'imagen',
            'Order' => 'orden',
            'Order.ACEntryID' => 'id de la orden',
            'Order.DocName' => 'nombre del documento',
            'Order.DocDate' => 'fecha del documento',
            'Order.Observations' => 'observaciones de la orden',
            'Order.Warehouse' => 'bodega',
            'Items' => 'ítems',
            'Totals' => 'totales',
            'Totals.Requested' => 'total solicitado',
            'Totals.Receiving' => 'total recibido',
            'Totals.Pending' => 'total pendiente',
            'Checklist' => 'lista de verificación',
            'Checklist.observaciones' => 'observaciones',
            'Checklist.referencias_recibidas' => 'referencias recibidas',
            'Checklist.responsable' => 'responsable',
            'Checklist.responsable.nombre' => 'nombre del responsable',
            'Checklist.responsable.cargo' => 'cargo del responsable',
            'Checklist.responsable.departamento' => 'departamento del responsable',
            'Checklist.responsable.celular' => 'celular del responsable',
        ];
    }
}
