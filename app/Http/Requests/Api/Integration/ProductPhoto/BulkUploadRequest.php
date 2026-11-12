<?php

namespace App\Http\Requests\Api\Integration\ProductPhoto;

use App\Services\Integration\ProductPhotoZipImportService;
use Illuminate\Validation\Validator;

/**
 * Carga masiva desde un ZIP. Además del archivo y el modo revisa todo el contenido del ZIP (estructura, nombres,
 * extensiones y tamaños) antes de guardar nada; si algo falla se informa y no se guarda ninguna imagen.
 */
class BulkUploadRequest extends ProductPhotoRequest
{
    /** Tamaño máximo del ZIP en KB (500 MB). */
    public const MAX_ZIP_SIZE_KB = 512000;

    /** Errores del ZIP que se devuelven completos antes de resumir. */
    public const MAX_ERRORS = 20;

    private ?array $plan = null;

    public function rules(): array
    {
        return [
            'zip' => ['required', 'file', 'mimes:zip', 'max:' . self::MAX_ZIP_SIZE_KB],
            'zip_mode' => ['required', 'in:' . ProductPhotoZipImportService::MODE_FOLDERS . ',' . ProductPhotoZipImportService::MODE_FILENAMES],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $errors = $this->plan()['errors'];

            foreach (array_slice($errors, 0, self::MAX_ERRORS) as $message) {
                $validator->errors()->add('zip', $message);
            }

            if (count($errors) > self::MAX_ERRORS) {
                $validator->errors()->add('zip', 'Y ' . (count($errors) - self::MAX_ERRORS) . ' problemas más. No se guardó ninguna imagen.');
            }
        }];
    }

    /** Plan de importación del ZIP ya revisado (entradas, referencias, carpetas vacías y errores). */
    public function plan(): array
    {
        return $this->plan ??= app(ProductPhotoZipImportService::class)
            ->inspect($this->file('zip')->getRealPath(), (string) $this->input('zip_mode'));
    }
}
