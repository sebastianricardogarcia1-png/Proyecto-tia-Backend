<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ImageUploadRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    /**
     * Subir y almacenar una imagen de producto en storage/app/public/productos.
     */
    public function upload(ImageUploadRequest $request): JsonResponse
    {
        try {
            $timestamp = now()->timestamp;
            $random = Str::random(8);

            // Caso 1: Archivo binario (multipart/form-data)
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $extension = strtolower($file->getClientOriginalExtension()) ?: 'webp';
                $fileName = "producto-{$timestamp}-{$random}.{$extension}";

                $path = $file->storeAs('productos', $fileName, 'public');

                $url = asset('storage/' . $path);

                return response()->json([
                    'message' => 'Imagen subida exitosamente.',
                    'url' => $url,
                    'path' => $path,
                    'fileName' => $fileName,
                ], 201);
            }

            // Caso 2: Cadena en Base64 (data:image/...;base64,...)
            $base64String = $request->input('image');
            preg_match('/^data:image\/([a-zA-Z0-9+.-]+);base64,/', $base64String, $matches);
            $mimeType = $matches[1] ?? 'webp';
            $extension = $mimeType === 'jpeg' ? 'jpg' : ($mimeType === 'png' ? 'png' : 'webp');

            $data = substr($base64String, strpos($base64String, ',') + 1);
            $decodedData = base64_decode($data);

            if ($decodedData === false) {
                return response()->json([
                    'message' => 'No se pudo decodificar la imagen en base64.',
                ], 422);
            }

            $fileName = "producto-{$timestamp}-{$random}.{$extension}";
            $path = "productos/{$fileName}";

            Storage::disk('public')->put($path, $decodedData);

            $url = asset('storage/' . $path);

            return response()->json([
                'message' => 'Imagen subida exitosamente.',
                'url' => $url,
                'path' => $path,
                'fileName' => $fileName,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al procesar la subida de imagen.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
