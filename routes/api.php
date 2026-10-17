<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ImageUploadController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Públicas (Catálogo y Login)
|--------------------------------------------------------------------------
*/
Route::get('productos', [ProductController::class, 'index']);
Route::get('productos/{producto}', [ProductController::class, 'show']);
Route::post('login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Rutas Protegidas (Solo Administrador Autenticado con Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    // Subida de imágenes protegida para administrador
    Route::post('upload-image', [ImageUploadController::class, 'upload']);

    Route::post('productos', [ProductController::class, 'store']);
    Route::match(['put', 'patch'], 'productos/{producto}', [ProductController::class, 'update']);
    Route::delete('productos/{producto}', [ProductController::class, 'destroy']);
});
