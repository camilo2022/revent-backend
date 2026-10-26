<?php

namespace App\Http\Requests\Integration\PhotoProduct;

class ExplorerUploadRequest extends PhotoProductRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'path' => ['required', 'string', 'max:200'],
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }
}
