<?php

namespace Ninja\Larasoul\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Ninja\Larasoul\Exceptions\VerificationRequiredException;
use Symfony\Component\HttpFoundation\Response;

class RequireVerification
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $redirectRoute = null): Response
    {
        $user = Auth::user();

        // Check if user is authenticated
        if (! $user) {
            return $this->handleUnauthenticated($request, $redirectRoute);
        }

        // Check if user has the verification trait
        if (! method_exists($user, 'isVerified')) {
            throw new VerificationRequiredException(
                'User model must use HasRiskProfile trait to check verification status'
            );
        }

        // Check if user is verified
        if (! $user->isVerified()) {
            return $this->handleUnverified($request, $user, $redirectRoute);
        }

        // Check if verification is expired
        if ($user->isVerificationExpired()) {
            return $this->handleExpiredVerification($request, $user, $redirectRoute);
        }

        return $next($request);
    }

    /**
     * Handle unauthenticated user
     */
    protected function handleUnauthenticated(Request $request, ?string $redirectRoute): Response
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
    protected function handleUnverified(Request $request, $user, ?string $redirectRoute): Response
    {
        $verificationStatus = $user->getVerificationStatus();
        $message = $this->getVerificationMessage($verificationStatus);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'verification_required',
                'verification_status' => $verificationStatus,
                'verification_url' => $this->getVerificationUrl($user),
                'missing_requirements' => $this->getMissingRequirements($user),
            ], 403);
        }

        $route = $redirectRoute ?: $this->getDefaultVerificationRoute($user);

        return redirect()->route($route)->with([
            'verification_message' => $message,
            'verification_status' => $verificationStatus,
        ]);
    }

    /**
     * Handle expired verification
     */
    protected function handleExpiredVerification(Request $request, $user, ?string $redirectRoute): Response
    {
        $message = 'Your verification has expired and needs to be renewed';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'verification_expired',
                'expired_at' => $user->getVerificationExpiryDate(),
                'verification_url' => $this->getVerificationUrl($user),
            ], 403);
        }

        $route = $redirectRoute ?: 'verification.renewal';

        return redirect()->route($route)->with([
            'verification_message' => $message,
            'verification_expired' => true,
        ]);
    }

    /**
     * Get verification message based on status
     */
    protected function getVerificationMessage(string $status): string
    {
        return match ($status) {
            'pending' => 'Your account verification is still being processed',
            'failed' => 'UserAccount verification failed. Please try again',
            'manual_review' => 'Your account is under manual review',
            'expired' => 'Your verification has expired and needs renewal',
            default => 'UserAccount verification is required to access this resource',
        };
    }

    /**
     * Get verification URL for user
     */
    protected function getVerificationUrl($user): ?string
    {
        $status = $user->getVerificationStatus();

        return match ($status) {
            'pending' => route('verification.status'),
            'failed' => route('verification.retry'),
            'manual_review' => route('verification.review'),
            'expired' => route('verification.renewal'),
            default => route('verification.start'),
        };
    }

    /**
     * Get missing verification requirements
     */
    protected function getMissingRequirements($user): array
    {
        if (! method_exists($user, 'getMissingVerificationRequirements')) {
            return [];
        }

        return $user->getMissingVerificationRequirements('basic');
    }

    /**
     * Get default verification route based on user status
     */
    protected function getDefaultVerificationRoute($user): string
    {
        $status = $user->getVerificationStatus();

        return match ($status) {
            'pending' => 'verification.status',
            'failed' => 'verification.retry',
            'manual_review' => 'verification.review',
            'expired' => 'verification.renewal',
            default => 'verification.start',
        };
    }
}
