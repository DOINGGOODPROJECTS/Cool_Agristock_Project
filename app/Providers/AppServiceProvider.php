<?php

namespace App\Providers;

use App\Services\Odoo\OdooApiClient;
use App\Services\Odoo\JournalOdooExporter;
use App\Services\ThingsBoard\ThingsBoardApiClient;
use App\Services\ThingsBoard\ThingsBoardService;
use App\Services\FacilityDashboard\FacilityDashboardClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OdooApiClient::class, function () {
            return new OdooApiClient(
                url:      config('odoo.url'),
                database: config('odoo.database'),
                username: config('odoo.username'),
                apiKey:   config('odoo.api_key'),
                timeout:  config('odoo.timeout', 30),
                dryRun:   config('odoo.dry_run', false),
            );
        });

        $this->app->singleton(ThingsBoardApiClient::class, function () {
            return new ThingsBoardApiClient(
                url:      config('thingsboard.url'),
                username: config('thingsboard.username'),
                password: config('thingsboard.password'),
                timeout:  config('thingsboard.timeout', 15),
                tokenTtl: config('thingsboard.token_ttl', 900),
            );
        });

        $this->app->singleton(ThingsBoardService::class, function ($app) {
            return new ThingsBoardService(
                client:                        $app->make(ThingsBoardApiClient::class),
                mockMode:                      config('thingsboard.mock', true),
                defaultStaleThresholdMinutes:  config('thingsboard.default_stale_threshold_minutes', 15),
                telemetryKeys:                 config('thingsboard.telemetry_keys', []),
            );
        });

        $this->app->singleton(FacilityDashboardClient::class, function () {
            return new FacilityDashboardClient(
                url:      config('facility_dashboard.url'),
                email:    config('facility_dashboard.email'),
                password: config('facility_dashboard.password'),
                timeout:  config('facility_dashboard.timeout', 10),
            );
        });
    }

    public function boot(): void
    {
        $this->app->singleton(JournalOdooExporter::class, function ($app) {
            return new JournalOdooExporter($app->make(OdooApiClient::class));
        });
    }
}
