<?php

namespace App\Http\Requests\Api\Integration\ProductPhoto;

use App\Http\Requests\Api\Integration\IntegrationRequest;
use App\Services\Integration\ProductPhotoImageService;
use App\Services\ProductPhotoService;
use Illuminate\Validation\Validator;

/**
 * Base de las solicitudes de fotos de producto: nombres de atributos, referencia ya normalizada y ruta del explorador ya resuelta.
 */
abstract class ProductPhotoRequest extends IntegrationRequest
{
    /** Máximo de fotos por solicitud. */
    public const MAX_PHOTOS = 30;

    private ?array $folderInfo = null;

    private bool $folderResolved = false;

    public function attributes(): array
    {
        return parent::attributes() + [
            'referencia' => 'Referencia',
            'photos' => 'Fotos',
            'photos.*' => 'Foto',
            'path' => 'Ruta',
            'name' => 'Nombre',
            'filename' => 'Nombre del archivo',
            'zip' => 'Archivo ZIP',
            'zip_mode' => 'Modo del ZIP',
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'image' => 'Debe ser una imagen.',
            'photos.min' => 'Envía al menos una foto.',
            'photos.max' => 'Envía máximo ' . self::MAX_PHOTOS . ' fotos por vez.',
            'photos.*.max' => 'Cada foto debe pesar máximo 10 MB.',
            'photos.*.mimes' => 'Cada foto debe ser JPG, PNG o WEBP.',
            'photos.*.image' => 'Cada archivo debe ser una imagen válida.',
            'zip.max' => 'El ZIP no debe superar 500 MB.',
        ];
    }

    /** Referencia como nombre de carpeta (mayúsculas, sin caracteres no permitidos). */
    public function reference(): string
    {
        return app(ProductPhotoImageService::class)->sanitizeReference((string) $this->input('referencia'));
    }

    /** Ruta lógica del explorador ya resuelta y segura, o null si no es válida. */
    public function folder(): ?array
    {
        if (!$this->folderResolved) {
            $this->folderInfo = app(ProductPhotoService::class)->resolverRuta($this->input('path'));
            $this->folderResolved = true;
        }

        return $this->folderInfo;
    }

    /** La referencia debe quedar con al menos una letra o número una vez normalizada. */
    protected function validateReference(Validator $validator): void
    {
        if ($validator->errors()->has('referencia')) {
            return;
        }

        if (!preg_match('/[A-ZÑ0-9]/u', $this->reference())) {
            $validator->errors()->add('referencia', 'La referencia debe tener al menos una letra o un número.');
        }
    }

    /** La ruta del explorador debe ser válida: sin salirse de products, máximo producto/color y color del catálogo. */
    protected function validateFolder(Validator $validator, bool $requireInside = false): void
    {
        if ($validator->errors()->has('path')) {
            return;
        }

        $info = $this->folder();

        if (!$info) {
            $validator->errors()->add('path', 'Ruta no válida.');

            return;
        }

        if ($requireInside && $info['level'] === 0) {
            $validator->errors()->add('path', 'Abre la carpeta de un producto o de un color.');
        }
    }

    /** Nombre de archivo de una foto: sin carpetas ni rutas y con extensión de imagen. */
    protected function filenameRules(): array
    {
        return ['required', 'string', 'max:255', 'regex:/^[^\/\\\\]+\.(jpe?g|png|webp)$/i'];
    }
}
