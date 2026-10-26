<?php

namespace App\Http\Requests\Integration\PhotoProduct;

class ExplorerCreateFolderRequest extends PhotoProductRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'path' => ['nullable', 'string', 'max:200'],
            'name' => ['required', 'string', 'max:100'],
        ];
    }
}
