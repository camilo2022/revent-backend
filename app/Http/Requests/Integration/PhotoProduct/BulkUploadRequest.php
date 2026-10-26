<?php

namespace App\Http\Requests\Integration\PhotoProduct;

class BulkUploadRequest extends PhotoProductRequest
{
    /** Tamaño máximo del ZIP en KB (500 MB). */
    public const MAX_ZIP_SIZE_KB = 512000;

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'zip' => ['required', 'file', 'mimes:zip', 'max:' . self::MAX_ZIP_SIZE_KB],
            'zip_mode' => ['required', 'in:folders,filenames'],
        ];
    }
}
