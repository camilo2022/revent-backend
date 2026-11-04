<?php

namespace App\Http\Requests\ProductionOrder;

use App\Models\ProductionOrder;

// Mismos datos que al crear más el id de la orden; solo se edita mientras está pendiente
class ProductionOrderUpdateRequest extends ProductionOrderStoreRequest
{
    public function rules(): array
    {
        return ['id' => ['required', 'exists:production_orders,id']] + parent::rules();
    }

    public function attributes(): array
    {
        return ['id' => 'Identificador de la orden de producción'] + parent::attributes();
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) return;

            $production_order = ProductionOrder::find($this->route('id'));

            if ($production_order->status !== ProductionOrder::PENDING) {
                $validator->errors()->add('id', "La orden está en estado {$production_order->status} y no se puede editar.");
            }
        });

        // Si la orden no es editable no tiene sentido revisar el resto
        parent::withValidator($validator);
    }
}
