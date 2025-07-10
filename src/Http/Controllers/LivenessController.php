<?php

namespace Ninja\Larasoul\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Session;
use Ninja\Larasoul\Contracts\RiskProfilable;
use Ninja\Larasoul\Enums\LivenessSession;
use Ninja\Larasoul\Exceptions\VerisoulApiException;
use Ninja\Larasoul\Facades\Verisoul;
use Symfony\Component\HttpFoundation\Response;

class LivenessController extends Controller
{
    public function start(LivenessSession $sessionType): JsonResponse|RedirectResponse
    {
        if (! $sessionType->enabled()) {
            return response()->json([
                'error' => 'Verisoul is not enabled in this environment',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $user = auth()->user();
        if (! $user) {
            return response()->json([
                'error' => 'User not authenticated',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (! $user instanceof RiskProfilable) {
            return response()->json([
                'error' => 'User entity must implement RiskProfilable interface',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $session = $sessionType === LivenessSession::FaceMatch ?
                Verisoul::faceMatch()->session(Session::get(config('larasoul.session.verisoul_session_id'))) :
                Verisoul::idCheck()->session(Session::get(config('larasoul.session.verisoul_session_id')));

            if (config('larasoul.verisoul.liveness.auto_send')) {
                $callbackUrl = route(config('larasoul.verisoul.liveness.verification_callback_url'), ['sessionType' => $sessionType->value]);
                $redirectUrl = $session->redirectUrl(
                    environment: config('larasoul.verisoul.environment'),
                    redirectUrl: $callbackUrl
                );

                return redirect()->away($redirectUrl);
            }

            return response()->json($session->asResource());
        } catch (VerisoulApiException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }
    }

    public function verify(LivenessSession $sessionType, Request $request): JsonResponse
    {
        if (! $sessionType->enabled()) {
            return response()->json([
                'error' => 'Verisoul is not enabled in this environment',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $user = auth()->user();
        if (! $user) {
            return response()->json([
                'error' => 'User not authenticated',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (! $user instanceof RiskProfilable) {
            return response()->json([
                'error' => 'User entity must implement RiskProfilable interface',
            ], Response::HTTP_BAD_REQUEST);
        }

        $api = $sessionType === LivenessSession::IDCheck
            ? Verisoul::idCheck()
            : Verisoul::faceMatch();

        try {
            $sessionId = (string) $request->input('session_id');
            $success = (bool) $request->input('success');

            if (! $success) {
                return response()->json(['error' => $request->input('error_message')], Response::HTTP_BAD_REQUEST);
            }

            $response = $api->verify($sessionId);
            if ($response->isSuccessful() && config('larasoul.verisoul.liveness.auto_enroll')) {
                $response = $api->enroll($sessionId, $user->getVerisoulAccount());
            }

            return response()->json($response->asResource());
        } catch (VerisoulApiException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }
    }

    public function enroll(LivenessSession $sessionType, Request $request): JsonResponse
    {
        if (! $sessionType->enabled()) {
            return response()->json([
                'error' => 'Verisoul is not enabled in this environment',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $user = auth()->user();
        if (! $user) {
            return response()->json([
                'error' => 'User not authenticated',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (! $user instanceof RiskProfilable) {
            return response()->json([
                'error' => 'User entity must implement RiskProfilable interface',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $sessionId = $request->input('session_id');
            $accountId = $request->input('account_id');

            $api = $sessionType === LivenessSession::IDCheck
                ? Verisoul::idCheck()
                : Verisoul::faceMatch();

            $verification = $api->verify($sessionId);

            if ($verification->isSuccessful()) {
                $response = $api->enroll($sessionId, $accountId);

                return response()->json($response->asResource());
            }

            return response()->json(['error' => 'Verification failed'], 400);

        } catch (VerisoulApiException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }
    }
}
