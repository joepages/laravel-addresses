<?php

declare(strict_types=1);

namespace Addresses;

use Addresses\Contracts\AddressRepositoryInterface;
use Addresses\Contracts\AddressServiceInterface;
use Addresses\Repositories\AddressRepository;
use Addresses\Services\AddressService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AddressesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/Config/addresses.php',
            'addresses'
        );

        // Repository
        $this->app->bind(AddressRepositoryInterface::class, AddressRepository::class);

        // Service
        $this->app->singleton(AddressServiceInterface::class, AddressService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

        $this->registerRouteMacro();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/Config/addresses.php' => config_path('addresses.php'),
            ], 'addresses-config');

            $this->commands([
                Console\Commands\InstallAddressesCommand::class,
            ]);
        }
    }

    protected function registerRouteMacro(): void
    {
        Route::macro('addressRoutes', function (string $prefix, string $controller) {
            $singular = Str::singular($prefix);

            Route::prefix("{$prefix}/{{$singular}}")->group(function () use ($controller) {
                Route::get('/addresses', [$controller, 'listAddresses']);
                Route::post('/addresses', [$controller, 'storeAddress']);
                Route::put('/addresses/{address}', [$controller, 'updateAddress']);
                Route::delete('/addresses/{address}', [$controller, 'deleteAddress']);
            });
        });
    }
}
