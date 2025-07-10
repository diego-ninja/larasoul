<?php

namespace Ninja\Larasoul\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePhoneVerification extends RequireVerification
{
    public function handle(Request $request, \Closure $next, ?string $redirectRoute = null): Response
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        // First check basic verification
        $basicResponse = parent::handle($request, function () {
            return response('continue');
        }, $redirectRoute);
        if ($basicResponse->getContent() !== 'continue') {
            return $basicResponse;
        }

        // Then check specific phone verification
        if (! $user->hasPhoneVerification()) {
            return $this->handleMissingPhoneVerification($request, $user, $redirectRoute);
        }

        return $next($request);
    }

    protected function handleMissingPhoneVerification(Request $request, $user, ?string $redirectRoute): Response
    {
        $message = 'Phone verification is required to access this resource.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'phone_verification_required',
                'verification_url' => route('verification.phone'),
            ], 403);
        }

        $route = $redirectRoute ?: 'verification.phone';

        return redirect()->route($route)->with('verification_message', $message);
    }
}
