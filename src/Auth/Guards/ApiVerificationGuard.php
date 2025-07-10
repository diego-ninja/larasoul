<?php

namespace Ninja\Larasoul\Auth\Guards;

/**
 * Guard for API access with verification
 */
class ApiVerificationGuard extends VerificationGuard
{
    protected function shouldRequireVerification(): bool
    {
        // Check if the current route requires verification
        $route = $this->request->route();
        if ($route) {
            $middleware = $route->gatherMiddleware();

            return in_array('require.verification', $middleware);
        }

        return false;
    }

    protected function shouldCheckRiskLevel(): bool
    {
        // Check if the current route has risk level restrictions
        $route = $this->request->route();
        if ($route) {
            $middleware = $route->gatherMiddleware();
            foreach ($middleware as $middlewareName) {
                if (str_starts_with($middlewareName, 'require.risk.level')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get risk level from route middleware
     */
    protected function getMaxRiskLevel(): string
    {
        $route = $this->request->route();
        if ($route) {
            $middleware = $route->gatherMiddleware();
            foreach ($middleware as $middlewareName) {
                if (str_starts_with($middlewareName, 'require.risk.level:')) {
                    return explode(':', $middlewareName)[1] ?? 'medium';
                }
            }
        }

        return 'medium';
    }
}
