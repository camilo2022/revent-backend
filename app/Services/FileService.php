<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use App\Models\File;

class FileService
{
    public function save($model, $file, $file_type_id, $file_subtype_id, $disk = 'public', $folder = 'files')
    {

        if (str_starts_with($file->getMimeType(), 'image/')) {
            $file = $this->process_image($file);
        }

        $path = Storage::disk($disk)->putFile($folder, $file);
        $storage_path = storage_path($disk . '/storage/' . $path);
        $this->corregirOrientacion($storage_path);

        $metadata = [
            'original_name' => $file->getClientOriginalName(),
            'original_extension' => $file->getClientOriginalExtension(),
            'client_mime' => $file->getClientMimeType(),
            'mime' => $file->getMimeType(),
            'extension' => $file->getClientOriginalExtension(),
            'size' => $file->getSize(),
            'hash_md5' => md5_file($file->getPathname()),
            'hash_sha256' => hash_file('sha256', $file->getPathname()),
            'uploaded_at' => now()->toDateTimeString(),
        ];

        if (str_starts_with($file->getMimeType(), 'image/')) {
            [$width, $height] = getimagesize($file->getPathname());
            $metadata['image'] = compact('width', 'height');
        }

        return File::updateOrCreate(
            [
                'model_id' => $model->id,
                'model_type' => get_class($model),
                'file_type_id' => $file_type_id,
                'file_subtype_id' => $file_subtype_id,
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            ],
            [
                'path' => $path,
                'mime' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'size' => $file->getSize(),
                'metadata' => $metadata,
            ]
        );
    }

    private function corregirOrientacion($rutaImagen)
    {
        $info = pathinfo($rutaImagen);
        if (strtolower($info['extension']) != 'jpg' && strtolower($info['extension']) != 'jpeg') {
            return false;
        }

        $exif = @exif_read_data($rutaImagen);
        if (!$exif || !isset($exif['Orientation'])) {
            return false;
        }

        $orientacion = $exif['Orientation'];

        if ($orientacion == 1) {
            return true;
        }

        $img = imagecreatefromjpeg($rutaImagen);

        switch ($orientacion) {
            case 3:
                $img = imagerotate($img, 180, 0);
                break;
            case 6:
                $img = imagerotate($img, -90, 0);
                break;
            case 8:
                $img = imagerotate($img, 90, 0);
                break;
            default:
                return false;
        }

        imagejpeg($img, $rutaImagen, 90);
        imagedestroy($img);

        return true;
    }

    private function process_image($file)
    {
        $path = $file->getPathname();

        $mime = mime_content_type($path);

        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($path);
                break;

            case 'image/png':
                $image = imagecreatefrompng($path);
                break;

            default:
                return $file;
        }

        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($path);


            if (!empty($exif['Orientation'])) {
                $image = $this->fix_orientation($image, $exif['Orientation']);
            }
        }

        imagedestroy($image);

        return $file;
    }

    private function fix_orientation($image, $orientation)
    {
        switch ($orientation) {

            case 3:
                $rotated = imagerotate($image, 180, 0);
                break;

            case 6:
                $rotated = imagerotate($image, -90, 0);
                break;

            case 8:
                $rotated = imagerotate($image, 90, 0);
                break;

            default:
                return $image;
        }

        imagedestroy($image);

        return $rotated ?: $image;
    }
}
