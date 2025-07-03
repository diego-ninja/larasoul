<?php

namespace Ninja\Larasoul\Providers;

use Illuminate\Support\ServiceProvider;
use Ninja\Larasoul\Contracts\AccountInterface;
use Ninja\Larasoul\Contracts\FaceMatchInterface;
use Ninja\Larasoul\Contracts\IDCheckInterface;
use Ninja\Larasoul\Contracts\ListInterface;
use Ninja\Larasoul\Contracts\PhoneInterface;
use Ninja\Larasoul\Contracts\SessionInterface;
use Ninja\Larasoul\Enums\VerisoulEnvironment;
use Ninja\Larasoul\Services\VerisoulManager;

class LarasoulServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/larasoul.php', 'larasoul');

        // Register the main manager
        $this->app->singleton(VerisoulManager::class, function ($app) {
            return new VerisoulManager(
                apiKey: config('larasoul.verisoul.api_key'),
                environment: VerisoulEnvironment::from(config('larasoul.verisoul.environment')),
                config: config('larasoul.verisoul')
            );
        });

        // Register individual clients
        $this->registerClients();
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/larasoul.php' => config_path('larasoul.php'),
        ], 'larasoul-config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
            ]);
        }
    }

    private function registerClients(): void
    {
        $this->app->bind(AccountInterface::class, function ($app) {
            $manager = $app->make(VerisoulManager::class);

            return $manager->account();
        });

        $this->app->bind(SessionInterface::class, function ($app) {
            $manager = $app->make(VerisoulManager::class);

            return $manager->session();
        });

        $this->app->bind(PhoneInterface::class, function ($app) {
            $manager = $app->make(VerisoulManager::class);

            return $manager->phone();
        });

        $this->app->bind(ListInterface::class, function ($app) {
            $manager = $app->make(VerisoulManager::class);

            return $manager->list();
        });

        $this->app->bind(FaceMatchInterface::class, function ($app) {
            $manager = $app->make(VerisoulManager::class);

            return $manager->faceMatch();
        });

        $this->app->bind(IDCheckInterface::class, function ($app) {
            $manager = $app->make(VerisoulManager::class);

            return $manager->idCheck();
        });
    }
}
