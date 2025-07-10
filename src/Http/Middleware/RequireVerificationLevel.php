<?php

namespace Ninja\Larasoul\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Ninja\Larasoul\Exceptions\VerificationLevelRequiredException;
use Ninja\Larasoul\Services\VerificationService;
use Symfony\Component\HttpFoundation\Response;

class RequireVerificationLevel
{
    public function __construct(
        protected VerificationService $verificationService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     *
     * @throws VerificationLevelRequiredException
     */
    public function handle(Request $request, Closure $next, string $requiredLevel = 'basic', ?string $redirectRoute = null): Response
    {
        $user = Auth::user();

        // Check if user is authenticated
        if (! $user) {
            return $this->handleUnauthenticated($request);
        }

        // Check if user has verification methods
        if (! method_exists($user, 'isVerified')) {
            throw new VerificationLevelRequiredException(
                'User model must use HasRiskProfile trait to check verification levels'
            );
        }

        // Validate required level
        $availableLevels = array_keys(config('larasoul.verification.requirements', []));
        if (! in_array($requiredLevel, $availableLevels)) {
            throw new VerificationLevelRequiredException(
                "Invalid verification level: {$requiredLevel}. Available levels: ".implode(', ', $availableLevels)
            );
        }

        // Check if user meets the required verification level
        if (! $this->verificationService->meetsRequirementsForLevel($user, $requiredLevel)) {
            return $this->handleInsufficientLevel($request, $user, $requiredLevel, $redirectRoute);
        }

        // Additional checks for specific levels
        if (! $this->passesAdditionalChecks($user, $requiredLevel)) {
            return $this->handleAdditionalChecksFailed($request, $user, $requiredLevel, $redirectRoute);
        }

        return $next($request);
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
     * Handle insufficient verification level
     */
    protected function handleInsufficientLevel(Request $request, $user, string $requiredLevel, ?string $redirectRoute): Response
    {
        $currentLevel = $this->getCurrentLevel($user);
        $missingRequirements = $this->verificationService->getMissingRequirementsForLevel($user, $requiredLevel);
        $message = $this->getLevelMessage($requiredLevel, $currentLevel);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'insufficient_verification_level',
                'current_level' => $currentLevel,
                'required_level' => $requiredLevel,
                'missing_requirements' => $missingRequirements,
                'verification_urls' => $this->getVerificationUrls($missingRequirements),
                'upgrade_url' => $this->getUpgradeUrl($requiredLevel),
            ], 403);
        }

        $route = $redirectRoute ?: $this->getUpgradeRoute($requiredLevel);

        return redirect()->route($route)->with([
            'verification_level_message' => $message,
            'current_level' => $currentLevel,
            'required_level' => $requiredLevel,
            'missing_requirements' => $missingRequirements,
        ]);
    }

    /**
     * Handle failed additional checks
     */
    protected function handleAdditionalChecksFailed(Request $request, $user, string $requiredLevel, ?string $redirectRoute): Response
    {
        $reason = $this->getAdditionalCheckFailureReason($user, $requiredLevel);
        $message = "Access denied: {$reason}";

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'additional_checks_failed',
                'required_level' => $requiredLevel,
                'failure_reason' => $reason,
                'support_url' => route('support.verification'),
            ], 403);
        }

        $route = $redirectRoute ?: 'support.verification';

        return redirect()->route($route)->with([
            'verification_error' => $message,
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Get current verification level for user
     */
    protected function getCurrentLevel($user): string
    {
        if (method_exists($user, 'getVerificationLevel')) {
            return $user->getVerificationLevel();
        }

        // Fallback: determine level based on verification types
        $verifiedTypes = method_exists($user, 'getVerifiedTypes') ? $user->getVerifiedTypes() : [];

        if (count($verifiedTypes) >= 4) {
            return 'high_value';
        } elseif (count($verifiedTypes) >= 3) {
            return 'premium';
        } elseif (count($verifiedTypes) >= 2) {
            return 'standard';
        } elseif (count($verifiedTypes) >= 1) {
            return 'basic';
        }

        return 'none';
    }

    /**
     * Get level-specific message
     */
    protected function getLevelMessage(string $requiredLevel, string $currentLevel): string
    {
        $levelDescriptions = [
            'basic' => 'Basic verification (phone)',
            'standard' => 'Standard verification (phone + face)',
            'premium' => 'Premium verification (phone + face + document)',
            'high_value' => 'High-value verification (phone + face + document + identity)',
        ];

        $required = $levelDescriptions[$requiredLevel] ?? $requiredLevel;
        $current = $levelDescriptions[$currentLevel] ?? $currentLevel;

        return "This resource requires {$required} level access. Your current level is {$current}.";
    }

    /**
     * Get verification URLs for missing requirements
     */
    protected function getVerificationUrls(array $missingRequirements): array
    {
        $urls = [];

        foreach ($missingRequirements as $requirement) {
            $urls[$requirement] = match ($requirement) {
                'phone' => route('verification.phone'),
                'face' => route('verification.face'),
                'identity' => route('verification.identity'),
                default => route('verification.start'),
            };
        }

        return $urls;
    }

    /**
     * Get upgrade URL for required level
     */
    protected function getUpgradeUrl(string $requiredLevel): string
    {
        return route('verification.upgrade', ['level' => $requiredLevel]);
    }

    /**
     * Get upgrade route for required level
     */
    protected function getUpgradeRoute(string $requiredLevel): string
    {
        return 'verification.upgrade';
    }

    /**
     * Perform additional checks based on verification level
     */
    protected function passesAdditionalChecks($user, string $requiredLevel): bool
    {
        // For high-value operations, check risk level
        if (in_array($requiredLevel, ['premium', 'high_value'])) {
            if (! method_exists($user, 'getRiskLevel')) {
                return false;
            }

            $riskLevel = $user->getRiskLevel();

            // High-value requires low risk
            if ($requiredLevel === 'high_value' && $riskLevel !== 'low') {
                return false;
            }

            // Premium allows low or medium risk
            if ($requiredLevel === 'premium' && $riskLevel === 'high') {
                return false;
            }
        }

        // Check for expired verification
        if (method_exists($user, 'isVerificationExpired') && $user->isVerificationExpired()) {
            return false;
        }

        // Check for blocking risk flags
        if (method_exists($user, 'hasBlockingRiskFlags') && $user->hasBlockingRiskFlags()) {
            return false;
        }

        return true;
    }

    /**
     * Get reason for additional check failure
     */
    protected function getAdditionalCheckFailureReason($user, string $requiredLevel): string
    {
        if (method_exists($user, 'hasBlockingRiskFlags') && $user->hasBlockingRiskFlags()) {
            return 'Your account has security flags that prevent access';
        }

        if (method_exists($user, 'isVerificationExpired') && $user->isVerificationExpired()) {
            return 'Your verification has expired and needs renewal';
        }

        if (method_exists($user, 'getRiskLevel')) {
            $riskLevel = $user->getRiskLevel();

            if ($requiredLevel === 'high_value' && $riskLevel !== 'low') {
                return 'High-value operations require low risk profile';
            }

            if ($requiredLevel === 'premium' && $riskLevel === 'high') {
                return 'Premium features are not available for high-risk accounts';
            }
        }

        return 'Additional security requirements not met';
    }
}
