<?php

namespace App\Http\Requests\TechnicalSheet;

use App\Models\TechnicalSheet;

// Mismos datos que al crear (sin el producto) más el id de la ficha; solo se edita en borrador o revisión
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

            if (!in_array($technical_sheet->status, [TechnicalSheet::PENDING, TechnicalSheet::REVIEW])) {
                $validator->errors()->add('id', "La ficha está en estado {$technical_sheet->status} y no se puede editar. Cree una nueva versión.");
            }
        });
    }
}
