<?php

namespace App\Http\Requests\TechnicalSheet;

use App\Models\TechnicalSheet;

// Mismos datos que al crear (sin el producto) más el id de la ficha; una ficha cancelada no se edita (los cambios quedan en la auditoría)
class TechnicalSheetUpdateRequest extends TechnicalSheetStoreRequest
{
    public function rules(): array
    {
        $rules = array_diff_key(parent::rules(), ['product_id' => true]);

        return ['id' => ['required', 'exists:technical_sheets,id,deleted_at,NULL']] + $rules;
    }

    public function attributes(): array
    {
        return ['id' => 'Identificador de la ficha técnica'] + parent::attributes();
    }

    protected function prepareForValidation()
    {
        parent::prepareForValidation();

        $this->merge(['id' => $this->route('id')]);
    }

    public function withValidator($validator)
    {
        parent::withValidator($validator);

        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) return;

            $technical_sheet = TechnicalSheet::find($this->route('id'));

            if ($technical_sheet->status === TechnicalSheet::CANCELED) {
                $validator->errors()->add('id', 'La ficha está cancelada y no se puede editar.');
            }
        });
    }
}
