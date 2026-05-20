<?php

namespace Tcc\EmdhaEsign;

use Illuminate\Support\ServiceProvider;
use Tcc\EmdhaEsign\Commands\EmdhaCheckCommand;
use Tcc\EmdhaEsign\Services\EmdhaApiService;
use Tcc\EmdhaEsign\Services\EmdhaSdkService;
use Tcc\EmdhaEsign\Services\EmdhaSigningService;
use Tcc\EmdhaEsign\Services\EmdhaXmlService;

class EmdhaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/emdha.php',
            'emdha'
        );

        $this->app->singleton(EmdhaSdkService::class, function ($app) {
            return new EmdhaSdkService($app['config']->get('emdha', []));
        });

        $this->app->singleton(EmdhaApiService::class, function ($app) {
            return new EmdhaApiService($app['config']->get('emdha', []));
        });

        $this->app->singleton(EmdhaXmlService::class, function ($app) {
            return new EmdhaXmlService($app['config']->get('emdha', []));
        });

        $this->app->singleton(EmdhaSigningService::class, function ($app) {
            return new EmdhaSigningService(
                $app->make(EmdhaSdkService::class),
                $app->make(EmdhaApiService::class),
                $app->make(EmdhaXmlService::class),
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/emdha.php' => $this->app->configPath('emdha.php'),
            ], 'emdha-config');

            $this->commands([
                EmdhaCheckCommand::class,
            ]);
        }
    }
}