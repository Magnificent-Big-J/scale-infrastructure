<?php

use App\Http\Controllers\Api\Admin\PackageController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\UserAdminController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Resources\AuthUserResource;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/v1/ping', fn () => response()->json(['status' => 'ok']));

    Route::get('/v1/me', fn ($request) => new AuthUserResource($request->user()));

    Route::get('/v1/profile', [ProfileController::class, 'show']);
    Route::patch('/v1/profile', [ProfileController::class, 'update']);
    Route::put('/v1/profile/password', [ProfileController::class, 'updatePassword']);

    Route::middleware('can:users.view')->group(function () {
        Route::get('/v1/users', [UserAdminController::class, 'index']);
        Route::middleware('can:users.create')->post('/v1/users', [UserAdminController::class, 'store']);
        Route::middleware('can:users.update')->patch('/v1/users/{user}', [UserAdminController::class, 'update']);
    });

    Route::middleware('can:products.view')->group(function () {
        Route::get('/v1/products', [ProductController::class, 'index']);
        Route::middleware('can:products.create')->post('/v1/products', [ProductController::class, 'store']);
        Route::middleware('can:products.update')->match(['put', 'patch'], '/v1/products/{product}', [ProductController::class, 'update']);
        Route::middleware('can:products.delete')->delete('/v1/products/{product}', [ProductController::class, 'destroy']);
    });

    Route::middleware('can:packages.view')->group(function () {
        Route::get('/v1/packages', [PackageController::class, 'index']);
        Route::get('/v1/products/{product}/packages', [PackageController::class, 'index']);
        Route::middleware('can:packages.create')->post('/v1/packages', [PackageController::class, 'store']);
        Route::middleware('can:packages.create')->post('/v1/products/{product}/packages', [PackageController::class, 'store']);
        Route::middleware('can:packages.update')->match(['put', 'patch'], '/v1/packages/{package}', [PackageController::class, 'update']);
        Route::middleware('can:packages.delete')->delete('/v1/packages/{package}', [PackageController::class, 'destroy']);
    });
});
