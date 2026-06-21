<?php

namespace App\Providers;

use App\Catalogue\CatalogueManager;
use Illuminate\Support\ServiceProvider;

class CatalogueServiceProvider extends ServiceProvider
{
    /**
     * Register catalogue services.
     */
    public function register(): void
    {
        $this->app->singleton(CatalogueManager::class, fn ($app): CatalogueManager => new CatalogueManager(
            $app->make('config'),
        ));
    }
}
