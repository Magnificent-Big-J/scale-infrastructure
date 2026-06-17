<?php

use App\Http\Controllers\Api\Admin\PackageController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\UserAdminController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Resources\AuthUserResource;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('ping', fn () => response()->json(['status' => 'ok']));

    Route::get('me', fn ($request) => new AuthUserResource($request->user()));

    Route::get('profile', [ProfileController::class, 'show']);
    Route::patch('profile', [ProfileController::class, 'update']);
    Route::put('profile/password', [ProfileController::class, 'updatePassword']);

    Route::middleware('can:users.view')->group(function () {
        Route::get('users', [UserAdminController::class, 'index']);
        Route::middleware('can:users.create')->post('users', [UserAdminController::class, 'store']);
        Route::middleware('can:users.update')->patch('users/{user}', [UserAdminController::class, 'update']);
    });

    Route::middleware('can:products.view')->group(function () {
        Route::get('products', [ProductController::class, 'index']);
        Route::middleware('can:products.create')->post('products', [ProductController::class, 'store']);
        Route::middleware('can:products.update')->match(['put', 'patch'], 'products/{product}', [ProductController::class, 'update']);
        Route::middleware('can:products.delete')->delete('products/{product}', [ProductController::class, 'destroy']);
    });

    Route::middleware('can:packages.view')->group(function () {
        Route::get('packages', [PackageController::class, 'index']);
        Route::get('products/{product}/packages', [PackageController::class, 'index']);
        Route::middleware('can:packages.create')->post('packages', [PackageController::class, 'store']);
        Route::middleware('can:packages.create')->post('products/{product}/packages', [PackageController::class, 'store']);
        Route::middleware('can:packages.update')->match(['put', 'patch'], 'packages/{package}', [PackageController::class, 'update']);
        Route::middleware('can:packages.delete')->delete('packages/{package}', [PackageController::class, 'destroy']);
    });
});
