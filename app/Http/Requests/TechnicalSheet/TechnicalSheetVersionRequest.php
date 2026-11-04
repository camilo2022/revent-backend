<?php

namespace App\Http\Requests\TechnicalSheet;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\TechnicalSheet;

class TechnicalSheetVersionRequest extends FormRequest
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

            $technical_sheet = TechnicalSheet::find($this->route('id'));

            // Una sola versión en borrador por producto
            $draft = TechnicalSheet::where('product_id', $technical_sheet->product_id)
                ->whereIn('status', [TechnicalSheet::PENDING, TechnicalSheet::REVIEW])
                ->exists();

            if ($draft) {
                $validator->errors()->add('id', 'El producto ya tiene una versión de la ficha en borrador o en revisión.');
            }
        });
    }
}
