<?php

namespace App\Providers;

use App\Contracts\CatalogueFeatureServiceInterface;
use App\Contracts\ClientServiceInterface;
use App\Contracts\DeploymentServiceInterface;
use App\Contracts\PackageServiceInterface;
use App\Contracts\PayFastCheckoutServiceInterface;
use App\Contracts\ProductServiceInterface;
use App\Contracts\SupportOperationsServiceInterface;
use App\Contracts\SupportTierServiceInterface;
use App\Contracts\UserAdminServiceInterface;
use App\Services\CatalogueFeatureService;
use App\Services\ClientService;
use App\Services\DeploymentService;
use App\Services\PackageService;
use App\Services\PayFastCheckoutService;
use App\Services\ProductService;
use App\Services\SupportOperationsService;
use App\Services\SupportTierService;
use App\Services\UserAdminService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserAdminServiceInterface::class, UserAdminService::class);
        $this->app->bind(PayFastCheckoutServiceInterface::class, PayFastCheckoutService::class);
        $this->app->bind(ProductServiceInterface::class, ProductService::class);
        $this->app->bind(PackageServiceInterface::class, PackageService::class);
        $this->app->bind(CatalogueFeatureServiceInterface::class, CatalogueFeatureService::class);
        $this->app->bind(SupportTierServiceInterface::class, SupportTierService::class);
        $this->app->bind(ClientServiceInterface::class, ClientService::class);
        $this->app->bind(DeploymentServiceInterface::class, DeploymentService::class);
        $this->app->bind(SupportOperationsServiceInterface::class, SupportOperationsService::class);
    }

    public function boot(): void
    {
        //
    }
}
