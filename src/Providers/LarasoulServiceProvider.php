<?php

namespace Ninja\Larasoul\Providers;

use Illuminate\Support\ServiceProvider;
use Ninja\Larasoul\Api\Contracts\AccountInterface;
use Ninja\Larasoul\Api\Contracts\FaceMatchInterface;
use Ninja\Larasoul\Api\Contracts\IDCheckInterface;
use Ninja\Larasoul\Api\Contracts\ListInterface;
use Ninja\Larasoul\Api\Contracts\PhoneInterface;
use Ninja\Larasoul\Api\Contracts\SessionInterface;
use Ninja\Larasoul\Enums\VerisoulEnvironment;
use Ninja\Larasoul\Models\RiskProfile;
use Ninja\Larasoul\Models\UserVerification;
use Ninja\Larasoul\Observers\RiskProfileObserver;
use Ninja\Larasoul\Providers\Traits\RegistersVerificationMiddleware;
use Ninja\Larasoul\Services\VerisoulApi;
use Ninja\Larasoul\Services\VerisoulSessionManager;

class LarasoulServiceProvider extends ServiceProvider
{
    use RegistersVerificationMiddleware;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/larasoul.php', 'larasoul');

        // Register the main manager (existing)
        $this->app->singleton(VerisoulApi::class, function ($app) {
            return new VerisoulApi(
                apiKey: config('larasoul.verisoul.api_key'),
                environment: VerisoulEnvironment::from(config('larasoul.verisoul.environment')),
                config: config('larasoul.verisoul')
            );
        });

        $this->app->singleton(VerisoulSessionManager::class, function ($app) {
            return new VerisoulSessionManager;
        });

        $this->app->singleton(\Ninja\Larasoul\Services\VerisoulScriptGenerator::class, function ($app) {
            return new \Ninja\Larasoul\Services\VerisoulScriptGenerator($app['request']);
        });

        // Register individual clients (existing)
        $this->registerClients();

        // Register facade aliases
        $this->app->alias(VerisoulApi::class, 'verisoul');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/larasoul.php' => config_path('larasoul.php'),
        ], 'larasoul-config');

        // Publish migrations (new)
        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'larasoul-migrations');

        // Register model observers (new)
        RiskProfile::observe(RiskProfileObserver::class);

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Ninja\Larasoul\Console\Commands\GenerateAnnotationsCommand::class,
            ]);
        }

        // Load routes (existing)
        $this->loadRoutesFrom(__DIR__.'/../../routes/larasoul.php');

        $this->registerEventListeners();
        $this->registerModelBindings();
        $this->registerValidationRules();
        $this->registerViewComposers();
        $this->registerBladeDirectives();
    }

    /**
     * Register individual clients (existing functionality)
     */
    private function registerClients(): void
    {
        $this->app->bind(AccountInterface::class, function ($app) {
            $manager = $app->make(VerisoulApi::class);

            return $manager->account();
        });

        $this->app->bind(SessionInterface::class, function ($app) {
            $manager = $app->make(VerisoulApi::class);

            return $manager->session();
        });

        $this->app->bind(PhoneInterface::class, function ($app) {
            $manager = $app->make(VerisoulApi::class);

            return $manager->phone();
        });

        $this->app->bind(ListInterface::class, function ($app) {
            $manager = $app->make(VerisoulApi::class);

            return $manager->list();
        });

        $this->app->bind(FaceMatchInterface::class, function ($app) {
            $manager = $app->make(VerisoulApi::class);

            return $manager->faceMatch();
        });

        $this->app->bind(IDCheckInterface::class, function ($app) {
            $manager = $app->make(VerisoulApi::class);

            return $manager->idCheck();
        });
    }

    /**
     * Register event listeners (new functionality)
     */
    protected function registerEventListeners(): void
    {

        // Login
        $this->app['events']->listen(
            \Illuminate\Auth\Events\Login::class,
            \Ninja\Larasoul\Listeners\HandleAuthUser::class
        );

        // Register
        $this->app['events']->listen(
            \Illuminate\Auth\Events\Registered::class,
            \Ninja\Larasoul\Listeners\HandleAuthUser::class
        );
    }

    /**
     * Register model bindings (new functionality)
     */
    protected function registerModelBindings(): void
    {
        // Allow custom user verification model
        $this->app->bind(
            RiskProfile::class,
            config('larasoul.verification.models.risk_profile', RiskProfile::class)
        );

        $this->app->bind(
            UserVerification::class,
            config('larasoul.verification.models.user_verification', UserVerification::class)
        );

    }

    /**
     * Register custom validation rules (new functionality)
     */
    protected function registerValidationRules(): void
    {
        $this->app['validator']->extend('verification_required', function ($attribute, $value, $parameters, $validator) {
            $userId = $value;
            $requiredLevel = $parameters[0] ?? 'basic';

            $user = app(config('auth.providers.users.model'))->find($userId);

            if (! $user) {
                return false;
            }

            $requirements = config("larasoul.verification.requirements.{$requiredLevel}", []);

            foreach ($requirements as $type) {
                $methodName = 'has'.ucfirst($type).'Verification';
                if (method_exists($user, $methodName) && ! $user->$methodName()) {
                    return false;
                }
            }

            return true;
        });

        $this->app['validator']->extend('risk_level_max', function ($attribute, $value, $parameters, $validator) {
            $userId = $value;
            $maxRiskLevel = $parameters[0] ?? 'medium';

            $user = app(config('auth.providers.users.model'))->find($userId);

            if (! $user) {
                return false;
            }

            $riskLevels = ['low' => 1, 'medium' => 2, 'high' => 3];
            $userRiskLevel = $riskLevels[$user->getRiskLevel()] ?? 3;
            $maxAllowedLevel = $riskLevels[$maxRiskLevel] ?? 2;

            return $userRiskLevel <= $maxAllowedLevel;
        });

        $this->app['validator']->extend('verification_not_expired', function ($attribute, $value, $parameters, $validator) {
            $userId = $value;

            $user = app(config('auth.providers.users.model'))->find($userId);

            if (! $user) {
                return false;
            }

            return ! $user->isVerificationExpired();
        });
    }

    /**
     * Register view composers for frontend integration
     */
    protected function registerViewComposers(): void
    {
        if (config('larasoul.verisoul.frontend.enabled', false)) {
            // Standard Blade integration
            if (config('larasoul.verisoul.frontend.auto_inject', false)) {
                view()->composer('*', \Ninja\Larasoul\View\Composers\VerisoulScriptComposer::class);
            }
        }
    }

    /**
     * Register Blade directives for Verisoul
     */
    protected function registerBladeDirectives(): void
    {
        \Blade::directive('verisoul', function ($expression) {
            return "<?php echo app('\\Ninja\\Larasoul\\Services\\VerisoulScriptGenerator')->generate({$expression}); ?>";
        });

        \Blade::directive('verisoulHead', function () {
            return "<?php echo app('\\Ninja\\Larasoul\\Services\\VerisoulScriptGenerator')->generateForHead(); ?>";
        });

        \Blade::directive('verisoulSession', function ($expression) {
            return "<?php echo app('\\Ninja\\Larasoul\\Services\\VerisoulScriptGenerator')->generateSessionScript({$expression}); ?>";
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            VerisoulApi::class,
            'verisoul',
        ];
    }
}
