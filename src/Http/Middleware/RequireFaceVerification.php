<?php

namespace Ninja\Larasoul\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireFaceVerification extends RequireVerification
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

        // Then check specific face verification
        if (! $user->hasFaceVerification()) {
            return $this->handleMissingFaceVerification($request, $user, $redirectRoute);
        }

        return $next($request);
    }

    protected function handleMissingFaceVerification(Request $request, $user, ?string $redirectRoute): Response
    {
        $message = 'Face verification is required to access this resource.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'face_verification_required',
                'verification_url' => route('verification.face'),
            ], 403);
        }

        $route = $redirectRoute ?: 'verification.face';

        return redirect()->route($route)->with('verification_message', $message);
    }
}
