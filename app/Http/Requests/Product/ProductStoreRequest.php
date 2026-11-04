<?php

namespace App\Http\Requests\Product;

use App\Models\Color;
use App\Models\Size;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductStoreRequest extends FormRequest
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
            'collection_id' => ['required', 'exists:collections,id,deleted_at,NULL'],
            'name' => ['required', 'uppercase', 'string', 'min:2', 'max:60'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'colors_id' => ['required', 'array', 'min:1'],
            'colors_id.*' => ['array'],
            'colors_id.*.color_id' => ['required', 'integer', 'exists:subitems,id,item_id,' . Color::ITEM_ID . ',deleted_at,NULL'],
            'colors_id.*.description' => ['nullable', 'string', 'max:1000'],
            'colors_id.*.sizes_id' => ['required', 'array', 'min:1'],
            'colors_id.*.sizes_id.*' => ['array'],
            'colors_id.*.sizes_id.*.size_id' => ['required', 'integer', 'exists:subitems,id,item_id,' . Size::ITEM_ID . ',deleted_at,NULL'],
            'colors_id.*.sizes_id.*.description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'exists' => 'No registrado en el sistema.',
            'string' => 'Debe ser una cadena de texto.',
            'integer' => 'Debe ser un número entero.',
            'numeric' => 'Debe ser un valor numérico.',
            'array' => 'Debe ser un arreglo de datos.',
            'uppercase' => 'Debe estar en mayúsculas.',
            'min' => 'El valor debe ser mayor o igual a :min.',
            'max' => 'Se permite máximo :max.',
            'colors_id.min' => 'Debe tener al menos un color.',
            'colors_id.*.sizes_id.min' => 'Cada color debe tener al menos una talla.',
        ];
    }

    public function attributes(): array
    {
        return [
            'collection_id' => 'Colección',
            'name' => 'Nombre de la referencia',
            'description' => 'Descripción',
            'price' => 'Precio de venta',
            'cost' => 'Costo',
            'colors_id' => 'Colores',
            'colors_id.*.color_id' => 'Color',
            'colors_id.*.description' => 'Descripción del color',
            'colors_id.*.sizes_id' => 'Tallas',
            'colors_id.*.sizes_id.*.size_id' => 'Talla',
            'colors_id.*.sizes_id.*.description' => 'Descripción de la talla',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) return;

            $colors = $this->input('colors_id', []);
            $colorIds = array_column($colors, 'color_id');

            // El mismo color no puede repetirse en el producto
            foreach ($colorIds as $i => $id) {
                if (array_search($id, $colorIds) !== $i) {
                    $validator->errors()->add("colors_id.$i.color_id", 'El color ya está en el producto.');
                }
            }

            // La talla no puede repetirse dentro de un color (sí puede estar en otro color)
            foreach ($colors as $i => $color) {
                $sizeIds = array_column($color['sizes_id'], 'size_id');

                foreach ($sizeIds as $j => $id) {
                    if (array_search($id, $sizeIds) !== $j) {
                        $validator->errors()->add("colors_id.$i.sizes_id.$j.size_id", 'La talla ya está en este color.');
                    }
                }
            }
        });
    }
}
