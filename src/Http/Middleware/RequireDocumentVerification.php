<?php

namespace Ninja\Larasoul\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Ninja\Larasoul\Exceptions\VerificationRequiredException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Specific middleware classes for each verification type
 */
class RequireDocumentVerification extends RequireVerification
{
    /**
     * @throws VerificationRequiredException
     */
    public function handle(Request $request, Closure $next, ?string $redirectRoute = null): Response
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        // First check basic verification
        $basicResponse = parent::handle($request, function () {
            return response('continue');
        }, $redirectRoute);
        if ($basicResponse->getContent() !== 'continue') {
            return $basicResponse;
        }

        // Then check specific document verification
        if (! $user->hasDocumentVerification()) {
            return $this->handleMissingDocumentVerification($request, $user, $redirectRoute);
        }

        return $next($request);
    }

    protected function handleMissingDocumentVerification(Request $request, $user, ?string $redirectRoute): Response
    {
        $message = 'Document verification is required to access this resource.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'document_verification_required',
                'verification_url' => route('verification.document'),
            ], 403);
        }

        $route = $redirectRoute ?: 'verification.document';

        return redirect()->route($route)->with('verification_message', $message);
    }
}
