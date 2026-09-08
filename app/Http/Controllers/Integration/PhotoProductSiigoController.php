<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PhotoProductSiigoController extends Controller
{
    private const DISK = 'public';
    private const BASE_PATH = 'products';
    private const ALLOWED_USER_IDS = [597];

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
            'token' => 'required|string',
            'referencia' => 'required|string',
            'photos' => 'required|array|min:1',
            'photos.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $usuario = $this->validar_usuario_permitido($request->input('token'));

        if (!$usuario['success']) {
            return response()->json([
                'success' => false,
                'error' => $usuario['message'],
            ], 401);
        }

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
            'token' => 'required|string',
            'referencia' => 'required|string',
            'filename' => 'required|string',
        ]);

        $usuario = $this->validar_usuario_permitido($request->input('token'));

        if (!$usuario['success']) {
            return response()->json([
                'success' => false,
                'error' => $usuario['message'],
            ], 401);
        }

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

    private function validar_usuario_permitido(string $token): array
    {
        $usuario = $this->obtener_datos_usuario($token);

        if (!$usuario['success']) {
            return [
                'success' => false,
                'message' => $usuario['message'],
            ];
        }

        if (!in_array($usuario['data']['id'], self::ALLOWED_USER_IDS, true)) {
            return [
                'success' => false,
                'message' => 'Usuario no autorizado',
            ];
        }

        return [
            'success' => true,
            'data' => $usuario['data'],
        ];
    }

    private function obtener_datos_usuario(string $token)
    {
        $response = Http::retry(3, 3000)->withHeaders([
            'Authorization' => $token,
            'Accept' => '*/*',
            'Referer' => 'https://siigonube.siigo.com/',
        ])->get('https://services.siigo.com/cross/globalstate/api/v1/Settings/LoadSettings');

        if (!$response->successful()) {
            return [
                'success' => false,
                'data' => [],
                'message' => 'Error de autenticacion'
            ];
        }

        $data = $response->json();

        return [
            'success' => true,
            'data' => [
                'id' => $data['userID'],
                'name' => $data['userName'],
            ],
            'message' => 'Usuario encontrado exitosamente'
        ];
    }
}
