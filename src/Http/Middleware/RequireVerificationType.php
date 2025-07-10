<?php

namespace Ninja\Larasoul\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Ninja\Larasoul\Exceptions\VerificationTypeRequiredException;
use Symfony\Component\HttpFoundation\Response;

class RequireVerificationType
{
    /**
     * Available verification types
     */
    private array $validTypes = [
        'document',
        'face',
        'phone',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): (Response)  $next
     *
     * @throws VerificationTypeRequiredException
     */
    public function handle(Request $request, Closure $next, string $verificationType, ?string $redirectRoute = null): Response
    {
        $user = Auth::user();

        // Check if user is authenticated
        if (! $user) {
            return $this->handleUnauthenticated($request);
        }

        // Validate verification type
        if (! in_array($verificationType, $this->validTypes)) {
            throw new VerificationTypeRequiredException(
                "Invalid verification type: {$verificationType}. Valid types are: ".implode(', ', $this->validTypes)
            );
        }

        // Check if user has verification methods
        $methodName = 'has'.ucfirst($verificationType).'Verification';
        if (! method_exists($user, $methodName)) {
            throw new VerificationTypeRequiredException(
                'User model must use HasRiskProfile trait to check verification types'
            );
        }

        // Check if user has the required verification type
        if (! $user->$methodName()) {
            return $this->handleMissingVerificationType($request, $user, $verificationType, $redirectRoute);
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
     * Handle missing verification type
     */
    protected function handleMissingVerificationType(Request $request, $user, string $verificationType, ?string $redirectRoute): Response
    {
        $message = $this->getVerificationTypeMessage($verificationType);
        $verificationUrl = $this->getVerificationTypeUrl($verificationType);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'verification_type_required',
                'required_type' => $verificationType,
                'verification_url' => $verificationUrl,
                'current_verifications' => $this->getCurrentVerifications($user),
            ], 403);
        }

        $route = $redirectRoute ?: $this->getDefaultRedirectRoute($verificationType);

        return redirect()->route($route)->with([
            'verification_type_message' => $message,
            'required_type' => $verificationType,
        ]);
    }

    /**
     * Get verification type specific message
     */
    protected function getVerificationTypeMessage(string $verificationType): string
    {
        return match ($verificationType) {
            'document' => 'Document verification is required to access this resource. Please upload a valid government-issued ID.',
            'face' => 'Face verification is required to access this resource. Please complete the face verification process.',
            'phone' => 'Phone verification is required to access this resource. Please verify your phone number.',
            'identity' => 'Full identity verification is required to access this resource. Please complete the identity verification process.',
            default => ucfirst($verificationType).' verification is required to access this resource.',
        };
    }

    /**
     * Get verification URL for specific type
     */
    protected function getVerificationTypeUrl(string $verificationType): ?string
    {
        $routes = config('larasoul.verification.routes', []);

        return match ($verificationType) {
            'document' => $routes['document'] ?? route('verification.document'),
            'face' => $routes['face'] ?? route('verification.face'),
            'phone' => $routes['phone'] ?? route('verification.phone'),
            default => route('verification.start'),
        };
    }

    /**
     * Get default redirect route for verification type
     */
    protected function getDefaultRedirectRoute(string $verificationType): string
    {
        return match ($verificationType) {
            'document' => 'verification.document',
            'face' => 'verification.face',
            'phone' => 'verification.phone',
            'identity' => 'verification.identity',
            default => 'verification.start',
        };
    }

    /**
     * Get current user verifications
     */
    protected function getCurrentVerifications($user): array
    {
        if (! method_exists($user, 'getVerifiedTypes')) {
            return [];
        }

        return $user->getVerifiedTypes();
    }
}
