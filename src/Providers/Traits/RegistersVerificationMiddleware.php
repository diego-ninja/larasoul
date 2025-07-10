<?php

namespace Ninja\Larasoul\Providers\Traits;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\Router;
use Ninja\Larasoul\Auth\Guards\ApiVerificationGuard;
use Ninja\Larasoul\Auth\Guards\HighSecurityVerificationGuard;
use Ninja\Larasoul\Auth\Guards\VerificationGuard;
use Ninja\Larasoul\Http\Middleware\RequireDocumentVerification;
use Ninja\Larasoul\Http\Middleware\RequireFaceVerification;
use Ninja\Larasoul\Http\Middleware\RequirePhoneVerification;
use Ninja\Larasoul\Http\Middleware\RequireRiskLevel;
use Ninja\Larasoul\Http\Middleware\RequireVerification;
use Ninja\Larasoul\Http\Middleware\RequireVerificationLevel;
use Ninja\Larasoul\Http\Middleware\RequireVerificationType;

/**
 * Trait to add middleware registration to LarasoulServiceProvider
 */
trait RegistersVerificationMiddleware
{
    /**
     * Register verification middleware
     *
     * @throws BindingResolutionException
     */
    protected function registerVerificationMiddleware(): void
    {
        $router = $this->app->make(Router::class);

        // Register route middleware
        $router->aliasMiddleware('require.verification', RequireVerification::class);
        $router->aliasMiddleware('require.risk.level', RequireRiskLevel::class);
        $router->aliasMiddleware('require.verification.type', RequireVerificationType::class);
        $router->aliasMiddleware('require.verification.level', RequireVerificationLevel::class);

        // Specific verification type middleware
        $router->aliasMiddleware('require.document', RequireDocumentVerification::class);
        $router->aliasMiddleware('require.face', RequireFaceVerification::class);
        $router->aliasMiddleware('require.phone', RequirePhoneVerification::class);

        // Register middleware groups
        $router->middlewareGroup('verified', [
            RequireVerification::class,
        ]);

        $router->middlewareGroup('low-risk', [
            RequireVerification::class,
            RequireRiskLevel::class.':low',
        ]);

        $router->middlewareGroup('premium-verified', [
            RequireVerification::class,
            RequireVerificationLevel::class.':premium',
            RequireRiskLevel::class.':medium',
        ]);

        $router->middlewareGroup('high-security', [
            RequireVerification::class,
            RequireVerificationLevel::class.':high_value',
            RequireRiskLevel::class.':low',
        ]);
    }

    /**
     * Register verification guards
     */
    protected function registerVerificationGuards(): void
    {
        $this->app['auth']->extend('verification', function ($app, $name, array $config) {
            return new VerificationGuard(
                $app['auth']->createUserProvider($config['provider'] ?? null),
                $app['request'],
                $config['input_key'] ?? 'api_token',
                $config['storage_key'] ?? 'api_token',
                $config['hash'] ?? 'sha256'
            );
        });

        $this->app['auth']->extend('high-security-verification', function ($app, $name, array $config) {
            return new HighSecurityVerificationGuard(
                $app['auth']->createUserProvider($config['provider'] ?? null),
                $app['request'],
                $config['input_key'] ?? 'api_token',
                $config['storage_key'] ?? 'api_token',
                $config['hash'] ?? 'sha256'
            );
        });

        $this->app['auth']->extend('api-verification', function ($app, $name, array $config) {
            return new ApiVerificationGuard(
                $app['auth']->createUserProvider($config['provider'] ?? null),
                $app['request'],
                $config['input_key'] ?? 'api_token',
                $config['storage_key'] ?? 'api_token',
                $config['hash'] ?? 'sha256'
            );
        });
    }
}
