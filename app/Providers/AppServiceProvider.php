<?php

namespace App\Providers;

use App\Contracts\PackageServiceInterface;
use App\Contracts\PayFastCheckoutServiceInterface;
use App\Contracts\ProductServiceInterface;
use App\Contracts\UserAdminServiceInterface;
use App\Services\PackageService;
use App\Services\PayFastCheckoutService;
use App\Services\ProductService;
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
    }

    public function boot(): void
    {
        //
    }
}
