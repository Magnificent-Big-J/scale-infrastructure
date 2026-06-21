<?php

namespace App\Providers;

use App\Contracts\ActivityFeedServiceInterface;
use App\Contracts\CatalogueFeatureServiceInterface;
use App\Contracts\ClientServiceInterface;
use App\Contracts\CommercialOperationsServiceInterface;
use App\Contracts\DeploymentServiceInterface;
use App\Contracts\ExecutiveDashboardServiceInterface;
use App\Contracts\FinanceDashboardServiceInterface;
use App\Contracts\LookupOptionServiceInterface;
use App\Contracts\OperationsDashboardServiceInterface;
use App\Contracts\PackageServiceInterface;
use App\Contracts\PayFastCheckoutServiceInterface;
use App\Contracts\ProductServiceInterface;
use App\Contracts\ProfitabilityServiceInterface;
use App\Contracts\ReleaseOperationsServiceInterface;
use App\Contracts\ReportServiceInterface;
use App\Contracts\SupportOperationsServiceInterface;
use App\Contracts\SupportTierServiceInterface;
use App\Contracts\TicketCommentServiceInterface;
use App\Contracts\UserAdminServiceInterface;
use App\Services\ActivityFeedService;
use App\Services\CatalogueFeatureService;
use App\Services\ClientService;
use App\Services\CommercialOperationsService;
use App\Services\DeploymentService;
use App\Services\ExecutiveDashboardService;
use App\Services\FinanceDashboardService;
use App\Services\LookupOptionService;
use App\Services\OperationsDashboardService;
use App\Services\PackageService;
use App\Services\PayFastCheckoutService;
use App\Services\ProductService;
use App\Services\ProfitabilityService;
use App\Services\ReleaseOperationsService;
use App\Services\ReportService;
use App\Services\SupportOperationsService;
use App\Services\SupportTierService;
use App\Services\TicketCommentService;
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
        $this->app->bind(CommercialOperationsServiceInterface::class, CommercialOperationsService::class);
        $this->app->bind(FinanceDashboardServiceInterface::class, FinanceDashboardService::class);
        $this->app->bind(ProfitabilityServiceInterface::class, ProfitabilityService::class);
        $this->app->bind(ExecutiveDashboardServiceInterface::class, ExecutiveDashboardService::class);
        $this->app->bind(OperationsDashboardServiceInterface::class, OperationsDashboardService::class);
        $this->app->bind(ReportServiceInterface::class, ReportService::class);
        $this->app->bind(ReleaseOperationsServiceInterface::class, ReleaseOperationsService::class);
        $this->app->bind(LookupOptionServiceInterface::class, LookupOptionService::class);
        $this->app->bind(ActivityFeedServiceInterface::class, ActivityFeedService::class);
        $this->app->bind(TicketCommentServiceInterface::class, TicketCommentService::class);
    }

    public function boot(): void
    {
        //
    }
}
