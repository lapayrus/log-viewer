<?php

namespace Lapayrus\LogViewer;

use Illuminate\Support\ServiceProvider;

class LogViewerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerRoutes();
        $this->registerViews();
        $this->registerPublishing();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/log-viewer.php', 'log-viewer'
        );
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
    }

    /**
     * Register the package views.
     *
     * @return void
     */
    protected function registerViews()
    {
        $this->loadViewsFrom(__DIR__.'/Views', 'log-viewer');
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/log-viewer.php' => config_path('log-viewer.php'),
            ], 'lapayrus-log-viewer-config');
        }
    }
}