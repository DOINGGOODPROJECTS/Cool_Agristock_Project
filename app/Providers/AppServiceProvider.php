<?php

namespace App\Providers;

use App\Services\Odoo\OdooApiClient;
use App\Services\Odoo\JournalOdooExporter;
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
    }

    public function boot(): void
    {
        $this->app->singleton(JournalOdooExporter::class, function ($app) {
            return new JournalOdooExporter($app->make(OdooApiClient::class));
        });
    }
}
