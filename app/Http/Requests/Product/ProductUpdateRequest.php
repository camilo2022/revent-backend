<?php

namespace App\Http\Requests\Product;

use App\Models\Product;
use App\Models\ProductionOrderDetail;
use App\Models\ProductionOrderDetailQuantity;

// Mismos datos que al crear (sin la colección: el código ya la lleva) más el id del producto
class ProductUpdateRequest extends ProductStoreRequest
{
    public function rules(): array
    {
        $rules = array_diff_key(parent::rules(), ['collection_id' => true]);

        return ['id' => ['required', 'exists:products,id']] + $rules;
    }

    public function attributes(): array
    {
        return ['id' => 'Identificador del producto'] + parent::attributes();
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }

    public function withValidator($validator)
    {
        parent::withValidator($validator);

        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) return;

            $product = Product::with('colors.sizes')->find($this->route('id'));
            $sent = collect($this->input('colors_id', []));

            // Un color o talla que ya está en una orden de producción no se puede quitar
            foreach ($product->colors as $color) {
                $sentColor = $sent->firstWhere('color_id', $color->assignable_id);

                if (!$sentColor) {
                    if (ProductionOrderDetail::where('product_detail_id', $color->id)->exists()) {
                        $validator->errors()->add('colors_id', "El color {$color->assignable?->name} está en órdenes de producción y no se puede quitar.");
                    }
                    continue;
                }

                $sentSizes = collect($sentColor['sizes_id'])->pluck('size_id');

                foreach ($color->sizes as $size) {
                    if (!$sentSizes->contains($size->assignable_id) && ProductionOrderDetailQuantity::where('product_detail_id', $size->id)->exists()) {
                        $validator->errors()->add('colors_id', "La talla {$size->assignable?->name} del color {$color->assignable?->name} está en órdenes de producción y no se puede quitar.");
                    }
                }
            }
        });
    }
}
