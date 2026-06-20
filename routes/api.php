<?php

use App\Http\Controllers\Api\Admin\CatalogueFeatureController;
use App\Http\Controllers\Api\Admin\PackageController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\SupportTierController;
use App\Http\Controllers\Api\Admin\UserAdminController;
use App\Http\Controllers\Api\BillingRecordController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\DeploymentController;
use App\Http\Controllers\Api\ExecutiveDashboardController;
use App\Http\Controllers\Api\FinanceDashboardController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\InfrastructureAssetController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ModuleDemoRecordController;
use App\Http\Controllers\Api\MonitoringCheckController;
use App\Http\Controllers\Api\OperationsDashboardController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProfitabilityController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SupportAgreementController;
use App\Http\Controllers\Api\SupportTicketController;
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

    Route::middleware('can:deployments.view')->group(function () {
        Route::get('deployments', [DeploymentController::class, 'index']);
        Route::middleware('can:deployments.create')->post('deployments', [DeploymentController::class, 'store']);
        Route::middleware('can:deployments.update')->match(['put', 'patch'], 'deployments/{deployment}', [DeploymentController::class, 'update']);
        Route::middleware('can:deployments.delete')->delete('deployments/{deployment}', [DeploymentController::class, 'destroy']);
        Route::middleware('can:infrastructure.create')->post('deployments/{deployment}/infrastructure-assets', [InfrastructureAssetController::class, 'store']);
        Route::middleware('can:monitoring.create')->post('deployments/{deployment}/monitoring-checks', [MonitoringCheckController::class, 'store']);
    });

    Route::middleware('can:infrastructure.view')->get('infrastructure-assets', [InfrastructureAssetController::class, 'index']);
    Route::middleware('can:monitoring.view')->get('monitoring-checks', [MonitoringCheckController::class, 'index']);

    Route::middleware('can:support_agreements.view')->group(function () {
        Route::get('support-agreements', [SupportAgreementController::class, 'index']);
        Route::middleware('can:support_agreements.create')->post('support-agreements', [SupportAgreementController::class, 'store']);
        Route::middleware('can:support_agreements.update')->match(['put', 'patch'], 'support-agreements/{supportAgreement}', [SupportAgreementController::class, 'update']);
    });

    Route::middleware('can:support_tickets.view')->group(function () {
        Route::get('support-tickets', [SupportTicketController::class, 'index']);
        Route::middleware('can:support_tickets.create')->post('support-tickets', [SupportTicketController::class, 'store']);
        Route::middleware('can:support_tickets.update')->match(['put', 'patch'], 'support-tickets/{supportTicket}', [SupportTicketController::class, 'update']);
    });

    Route::middleware('can:incidents.view')->group(function () {
        Route::get('incidents', [IncidentController::class, 'index']);
        Route::middleware('can:incidents.create')->post('incidents', [IncidentController::class, 'store']);
        Route::middleware('can:incidents.update')->match(['put', 'patch'], 'incidents/{incident}', [IncidentController::class, 'update']);
    });

    Route::middleware('can:contracts.view')->group(function () {
        Route::get('contracts', [ContractController::class, 'index']);
        Route::middleware('can:contracts.create')->post('contracts', [ContractController::class, 'store']);
        Route::middleware('can:contracts.update')->match(['put', 'patch'], 'contracts/{contract}', [ContractController::class, 'update']);
    });

    Route::middleware('can:billing.view')->group(function () {
        Route::get('billing-records', [BillingRecordController::class, 'index']);
        Route::middleware('can:billing.create')->post('billing-records', [BillingRecordController::class, 'store']);
        Route::middleware('can:billing.update')->match(['put', 'patch'], 'billing-records/{billingRecord}', [BillingRecordController::class, 'update']);
    });

    Route::middleware('can:invoices.view')->group(function () {
        Route::get('invoices', [InvoiceController::class, 'index']);
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show']);
        Route::middleware('can:invoices.create')->post('invoices', [InvoiceController::class, 'store']);
        Route::middleware('can:invoices.update')->match(['put', 'patch'], 'invoices/{invoice}', [InvoiceController::class, 'update']);
        Route::middleware('can:payments.create')->post('invoices/{invoice}/payments', [PaymentController::class, 'store']);
    });

    Route::middleware('can:billing.view')->get('dashboard/finance', [FinanceDashboardController::class, 'show']);
    Route::middleware('can:dashboard.view')->get('dashboard/executive', [ExecutiveDashboardController::class, 'show']);
    Route::middleware('can:dashboard.view')->get('dashboard/operations', [OperationsDashboardController::class, 'show']);

    Route::middleware('can:profitability.view')->group(function () {
        Route::get('profitability-records', [ProfitabilityController::class, 'index']);
        Route::middleware('can:profitability.create')->post('profitability-records', [ProfitabilityController::class, 'store']);
        Route::middleware('can:profitability.update')->match(['put', 'patch'], 'profitability-records/{profitabilityRecord}', [ProfitabilityController::class, 'update']);
    });

    Route::middleware('can:reports.view')->group(function () {
        Route::get('reports', [ReportController::class, 'index']);
        Route::get('reports/{type}', [ReportController::class, 'show']);
        Route::middleware('can:reports.export')->get('reports/{type}/export', [ReportController::class, 'export']);
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
