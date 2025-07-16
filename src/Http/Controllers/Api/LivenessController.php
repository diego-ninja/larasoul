<?php

namespace Ninja\Larasoul\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Session;
use Ninja\Larasoul\Contracts\RiskProfilable;
use Ninja\Larasoul\Enums\LivenessSession;
use Ninja\Larasoul\Exceptions\VerisoulApiException;
use Ninja\Larasoul\Models\UserVerification;
use Symfony\Component\HttpFoundation\Response;

class LivenessController extends Controller
{
    public function start(string $sessionType): JsonResponse
    {
        $sessionType = LivenessSession::from($sessionType);
        $this->guard($sessionType);

        try {
            $session = $sessionType->api()->session(Session::get(config('larasoul.session.verisoul_session_id')));

            return response()->json($session->asResource());
        } catch (VerisoulApiException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }
    }

    public function verify(string $sessionType, Request $request): JsonResponse
    {
        $sessionType = LivenessSession::from($sessionType);
        $this->guard($sessionType);

        /** @var RiskProfilable $user */
        $user = auth()->user();

        try {
            $sessionId = (string) $request->input('session_id');
            $success = (bool) $request->input('success');

            if (! $success) {
                return response()->json(['error' => $request->input('error_message')], Response::HTTP_BAD_REQUEST);
            }

            $response = $sessionType->api()->verify($sessionId);

            if ($response->isSuccessful()) {
                $userVerification = new UserVerification;
                $userVerification->user_id = $user->getAuthIdentifier();
                $userVerification->type = $sessionType->getVerificationType();
                $userVerification->risk_score = $response->riskScore;
                $userVerification->decision = $response->decision;
                $userVerification->risk_signals = $response->getRiskSignals();
                $user->getRiskProfile()->markAsVerified($sessionType->getVerificationType());
            }

            if (config('larasoul.verisoul.liveness.auto_enroll')) {
                $response = $sessionType->api()->enroll($sessionId, $user->getVerisoulAccount());
            }

            return response()->json($response->asResource());
        } catch (VerisoulApiException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }
    }

    public function enroll(string $sessionType, Request $request): JsonResponse
    {
        $sessionType = LivenessSession::from($sessionType);
        $this->guard($sessionType);

        try {
            $sessionId = $request->input('session_id');
            $accountId = $request->input('account_id');

            $verification = $sessionType->api()->verify($sessionId);

            if ($verification->isSuccessful()) {
                $response = $sessionType->api()->enroll($sessionId, $accountId);

                return response()->json($response->asResource());
            }

            return response()->json(['error' => 'Verification failed'], 400);

        } catch (VerisoulApiException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }
    }

    private function guard(LivenessSession $sessionType): void
    {
        if (! $sessionType->enabled()) {
            abort(Response::HTTP_SERVICE_UNAVAILABLE, 'Verisoul is not enabled in this environment');
        }

        if (! auth()->user()) {
            abort(Response::HTTP_UNAUTHORIZED, 'User not authenticated');
        }

        if (! auth()->user() instanceof RiskProfilable) {
            abort(Response::HTTP_BAD_REQUEST, 'User entity must implement RiskProfilable interface');
        }
    }
}
