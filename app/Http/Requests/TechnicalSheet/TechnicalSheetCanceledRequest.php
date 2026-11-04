<?php

namespace App\Http\Requests\TechnicalSheet;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\TechnicalSheet;

class TechnicalSheetCanceledRequest extends FormRequest
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
            'id' => ['required', 'exists:technical_sheets,id,deleted_at,NULL']
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'exists' => 'No hay ningún registro.'
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Identificador de la ficha técnica'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['id' => $this->route('id')]);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) return;

            $technical_sheet = TechnicalSheet::with('product.colors.sizes')->find($this->route('id'));

            if (!in_array($technical_sheet->status, [TechnicalSheet::PENDING, TechnicalSheet::REVIEW, TechnicalSheet::APPROVED])) {
                $validator->errors()->add('id', "La ficha está en estado {$technical_sheet->status} y no se puede cancelar.");
            }
        });
    }
}
