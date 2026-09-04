<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PhotoProductSiigoController extends Controller
{
    private const DISK = 'public';
    private const BASE_PATH = 'products';

    public function product_photo()
    {
        return view('integration.photo_product_siigo');
    }

    public function product_photo_search(Request $request)
    {
        $request->validate([
            'referencia' => 'required|string',
        ]);

        $referencia = $this->sanitize_referencia($request->input('referencia'));
        $path = self::BASE_PATH . "/{$referencia}";

        if (!Storage::disk(self::DISK)->exists($path)) {
            return response()->json([
                'exists' => false,
                'referencia' => $referencia,
                'photos' => [],
            ]);
        }

        $files = collect(Storage::disk(self::DISK)->files($path))
            ->map(fn ($file) => [
                'name' => basename($file),
                'url' => Storage::disk(self::DISK)->url($file),
                'size' => Storage::disk(self::DISK)->size($file),
            ])
            ->values();

        return response()->json([
            'exists' => $files->isNotEmpty(),
            'referencia' => $referencia,
            'photos' => $files,
        ]);
    }

    public function product_photo_upload(Request $request)
    {
        $request->validate([
            'referencia' => 'required|string',
            'photos' => 'required|array|min:1',
            'photos.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $referencia = $this->sanitize_referencia($request->input('referencia'));
        $path = self::BASE_PATH . "/{$referencia}";

        $uploaded = [];

        foreach ($request->file('photos') as $photo) {
            $filename = Str::uuid() . '.' . $photo->getClientOriginalExtension();

            Storage::disk(self::DISK)->putFileAs($path, $photo, $filename);

            $uploaded[] = [
                'name' => $filename,
                'url' => Storage::disk(self::DISK)->url("{$path}/{$filename}"),
                'size' => $photo->getSize(),
            ];
        }

        return response()->json([
            'success' => true,
            'referencia' => $referencia,
            'uploaded' => $uploaded,
        ]);
    }

    public function product_photo_delete(Request $request)
    {
        $request->validate([
            'referencia' => 'required|string',
            'filename' => 'required|string',
        ]);

        $referencia = $this->sanitize_referencia($request->input('referencia'));
        $filename = basename($request->input('filename'));
        $path = self::BASE_PATH . "/{$referencia}/{$filename}";

        if (!Storage::disk(self::DISK)->exists($path)) {
            return response()->json([
                'success' => false,
                'error' => 'La foto no existe',
            ], 404);
        }

        Storage::disk(self::DISK)->delete($path);

        return response()->json([
            'success' => true,
        ]);
    }

    private function sanitize_referencia(string $referencia): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9\-_]/', '-', trim($referencia)));
    }
}
