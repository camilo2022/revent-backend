<?php

namespace App\Http\Requests\ProductionOrder;

use App\Models\TechnicalSheet;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductionOrderStoreRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Error de validación.',
            'attributes' => $this->attributes(),
            'errors' => $validator->errors()
        ], 422));
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'technical_sheet_id' => ['required', 'integer', 'exists:technical_sheets,id,deleted_at,NULL'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id,deleted_at,NULL'],
            'due_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'vat_percentage' => ['required', 'integer', 'between:0,100'],
            'delivery_note_percentage' => ['required', 'integer', 'between:0,100'],
            'production_order_details' => ['required', 'array', 'min:1'],
            'production_order_details.*' => ['array'],
            'production_order_details.*.product_detail_id' => ['required', 'integer', 'distinct', 'exists:product_details,id'],
            'production_order_details.*.cost' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'production_order_details.*.price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'production_order_details.*.observation' => ['nullable', 'string', 'max:1000'],
            'production_order_details.*.production_order_detail_quantities' => ['required', 'array', 'min:1'],
            'production_order_details.*.production_order_detail_quantities.*' => ['array'],
            'production_order_details.*.production_order_detail_quantities.*.store_id' => ['required', 'integer', 'exists:stores,id,deleted_at,NULL'],
            'production_order_details.*.production_order_detail_quantities.*.product_detail_id' => ['required', 'integer', 'exists:product_details,id'],
            'production_order_details.*.production_order_detail_quantities.*.quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'exists' => 'No registrado en el sistema.',
            'integer' => 'Debe ser un número entero.',
            'numeric' => 'Debe ser un valor numérico.',
            'string' => 'Debe ser una cadena de texto.',
            'array' => 'Debe ser un arreglo de datos.',
            'distinct' => 'No se puede repetir.',
            'between' => 'Debe estar entre :min y :max.',
            'min' => 'El valor debe ser mayor o igual a :min.',
            'max' => 'Se permite máximo :max.',
            'date_format' => 'Formato inválido. Permitido :format.',
            'after_or_equal' => 'No puede ser anterior a hoy.',
            'production_order_details.min' => 'Debe tener al menos un color.',
        ];
    }

    public function attributes(): array
    {
        return [
            'technical_sheet_id' => 'Ficha técnica',
            'supplier_id' => 'Proveedor',
            'due_date' => 'Fecha de vencimiento',
            'vat_percentage' => 'Porcentaje con IVA',
            'delivery_note_percentage' => 'Porcentaje por remisión',
            'production_order_details' => 'Colores de la orden',
            'production_order_details.*.product_detail_id' => 'Color',
            'production_order_details.*.cost' => 'Costo',
            'production_order_details.*.price' => 'Precio de venta',
            'production_order_details.*.observation' => 'Observación',
            'production_order_details.*.production_order_detail_quantities' => 'Cantidades',
            'production_order_details.*.production_order_detail_quantities.*.store_id' => 'Tienda',
            'production_order_details.*.production_order_detail_quantities.*.product_detail_id' => 'Talla',
            'production_order_details.*.production_order_detail_quantities.*.quantity' => 'Cantidad',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) return;

            $technical_sheet = TechnicalSheet::with('product.colors.sizes')->find($this->input('technical_sheet_id'));

            if ($technical_sheet->status !== TechnicalSheet::APPROVED) {
                $validator->errors()->add('technical_sheet_id', 'La ficha técnica debe estar aprobada.');
                return;
            }

            // Colores del producto de la ficha con los ids de sus tallas
            $colors = $technical_sheet->product->colors->keyBy('id');
            $pairs = 0;

            foreach ($this->input('production_order_details') as $i => $detail) {
                $color = $colors->get($detail['product_detail_id']);

                if (!$color) {
                    $validator->errors()->add("production_order_details.$i.product_detail_id", 'El color no pertenece al producto de la ficha.');
                    continue;
                }

                $sizeIds = $color->sizes->pluck('id');
                $seen = [];

                foreach ($detail['production_order_detail_quantities'] as $j => $quantity) {
                    $path = "production_order_details.$i.production_order_detail_quantities.$j";

                    if (!$sizeIds->contains($quantity['product_detail_id'])) {
                        $validator->errors()->add("$path.product_detail_id", 'La talla no pertenece a este color.');
                    }

                    // Una tienda no repite la misma talla dentro de un color
                    $key = $quantity['store_id'] . '-' . $quantity['product_detail_id'];

                    if (isset($seen[$key])) {
                        $validator->errors()->add("$path.product_detail_id", 'La tienda ya tiene esta talla en este color.');
                    }

                    $seen[$key] = true;
                    $pairs += $quantity['quantity'];
                }
            }

            if ($validator->errors()->isEmpty() && $pairs === 0) {
                $validator->errors()->add('production_order_details', 'La orden debe tener al menos un par.');
            }
        });
    }
}
