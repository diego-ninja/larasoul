<?php

namespace Ninja\Larasoul\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Ninja\Larasoul\Exceptions\HighRiskUserException;
use Symfony\Component\HttpFoundation\Response;

class RequireRiskLevel
{
    /**
     * Risk level hierarchy for comparison
     */
    private array $riskLevels = [
        'low' => 1,
        'medium' => 2,
        'high' => 3,
        'unknown' => 4,
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     *
     * @throws HighRiskUserException
     */
    public function handle(Request $request, Closure $next, string $maxRiskLevel = 'medium', ?string $redirectRoute = null): Response
    {
        $user = Auth::user();

        // Check if user is authenticated
        if (! $user) {
            return $this->handleUnauthenticated($request);
        }

        // Check if user has verification methods
        if (! method_exists($user, 'getRiskLevel') || ! method_exists($user, 'isVerified')) {
            throw new HighRiskUserException(
                'User model must use HasRiskProfile trait to check risk level'
            );
        }

        // Check if user is verified first
        if (! $user->isVerified()) {
            return $this->handleUnverified($request, $redirectRoute);
        }

        $userRiskLevel = $user->getRiskLevel();
        $userRiskScore = $user->getRiskScore();

        // Check if user's risk level exceeds the maximum allowed
        if ($this->exceedsRiskLevel($userRiskLevel, $maxRiskLevel)) {
            return $this->handleHighRisk($request, $user, $maxRiskLevel, $redirectRoute);
        }

        return $next($request);
    }

    /**
     * Check if user's risk level exceeds the maximum allowed
     */
    protected function exceedsRiskLevel(string $userRiskLevel, string $maxRiskLevel): bool
    {
        $userLevel = $this->riskLevels[$userRiskLevel] ?? 4;
        $maxLevel = $this->riskLevels[$maxRiskLevel] ?? 2;

        return $userLevel > $maxLevel;
    }

    /**
     * Handle unauthenticated user
     */
    protected function handleUnauthenticated(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Authentication required',
                'error' => 'unauthenticated',
            ], 401);
        }

        return redirect()->guest(route('login'));
    }

    /**
     * Handle unverified user
     */
    protected function handleUnverified(Request $request, ?string $redirectRoute): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'UserAccount verification is required',
                'error' => 'verification_required',
            ], 403);
        }

        $route = $redirectRoute ?: 'verification.start';

        return redirect()->route($route);
    }

    /**
     * Handle high risk user
     */
    protected function handleHighRisk(Request $request, $user, string $maxRiskLevel, ?string $redirectRoute): Response
    {
        $userRiskLevel = $user->getRiskLevel();
        $userRiskScore = $user->getRiskScore();
        $riskFlags = $user->getRiskFlags();

        $message = "Access denied. Your account risk level ({$userRiskLevel}) exceeds the maximum allowed ({$maxRiskLevel}) for this resource.";

        // Log high risk access attempt
        logger()->warning('High risk user access denied', [
            'user_id' => $user->getAuthIdentifier(),
            'user_risk_level' => $userRiskLevel,
            'user_risk_score' => $userRiskScore,
            'max_allowed_level' => $maxRiskLevel,
            'risk_flags' => $riskFlags,
            'requested_url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'high_risk_user',
                'user_risk_level' => $userRiskLevel,
                'user_risk_score' => $userRiskScore,
                'max_allowed_level' => $maxRiskLevel,
                'risk_flags' => $riskFlags,
                'support_url' => route('support.risk-review'),
            ], 403);
        }

        $route = $redirectRoute ?: 'account.risk-review';

        return redirect()->route($route)->with([
            'risk_message' => $message,
            'user_risk_level' => $userRiskLevel,
            'max_allowed_level' => $maxRiskLevel,
        ]);
    }


    /**
     * Get risk level display name
     */
    protected function getRiskLevelDisplayName(string $riskLevel): string
    {
        return match ($riskLevel) {
            'low' => 'Low Risk',
            'medium' => 'Medium Risk',
            'high' => 'High Risk',
            'unknown' => 'Unknown Risk',
            default => 'Undefined Risk',
        };
    }
}
