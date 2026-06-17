<?php

use App\Http\Controllers\Api\Admin\CatalogueFeatureController;
use App\Http\Controllers\Api\Admin\PackageController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\SupportTierController;
use App\Http\Controllers\Api\Admin\UserAdminController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ModuleDemoRecordController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Resources\AuthUserResource;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('ping', fn () => response()->json(['status' => 'ok']));

    Route::get('me', fn ($request) => new AuthUserResource($request->user()));

    Route::get('profile', [ProfileController::class, 'show']);
    Route::patch('profile', [ProfileController::class, 'update']);
    Route::put('profile/password', [ProfileController::class, 'updatePassword']);

    Route::get('module-demo/{pageKey}', [ModuleDemoRecordController::class, 'index'])
        ->where('pageKey', '[A-Za-z0-9_.-]+');

    Route::middleware('can:clients.view')->group(function () {
        Route::get('clients', [ClientController::class, 'index']);
        Route::get('clients/{client}', [ClientController::class, 'show']);
        Route::middleware('can:clients.create')->post('clients', [ClientController::class, 'store']);
        Route::middleware('can:clients.update')->match(['put', 'patch'], 'clients/{client}', [ClientController::class, 'update']);
        Route::middleware('can:clients.delete')->delete('clients/{client}', [ClientController::class, 'destroy']);
    });

    Route::middleware('can:contacts.view')->group(function () {
        Route::middleware('can:contacts.create')->post('clients/{client}/contacts', [ContactController::class, 'store']);
        Route::middleware('can:contacts.update')->match(['put', 'patch'], 'contacts/{contact}', [ContactController::class, 'update']);
        Route::middleware('can:contacts.delete')->delete('contacts/{contact}', [ContactController::class, 'destroy']);
    });

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

    Route::middleware('can:catalogue_features.view')->group(function () {
        Route::get('catalogue-features', [CatalogueFeatureController::class, 'index']);
        Route::get('products/{product}/catalogue-features', [CatalogueFeatureController::class, 'index']);
        Route::middleware('can:catalogue_features.create')->post('catalogue-features', [CatalogueFeatureController::class, 'store']);
        Route::middleware('can:catalogue_features.create')->post('products/{product}/catalogue-features', [CatalogueFeatureController::class, 'store']);
        Route::middleware('can:catalogue_features.update')->match(['put', 'patch'], 'catalogue-features/{catalogueFeature}', [CatalogueFeatureController::class, 'update']);
        Route::middleware('can:catalogue_features.delete')->delete('catalogue-features/{catalogueFeature}', [CatalogueFeatureController::class, 'destroy']);
    });

    Route::middleware('can:support_tiers.view')->group(function () {
        Route::get('support-tiers', [SupportTierController::class, 'index']);
        Route::middleware('can:support_tiers.create')->post('support-tiers', [SupportTierController::class, 'store']);
        Route::middleware('can:support_tiers.update')->match(['put', 'patch'], 'support-tiers/{supportTier}', [SupportTierController::class, 'update']);
        Route::middleware('can:support_tiers.delete')->delete('support-tiers/{supportTier}', [SupportTierController::class, 'destroy']);
    });
});
