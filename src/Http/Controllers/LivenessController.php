<?php

namespace Ninja\Larasoul\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Ninja\Larasoul\Enums\LivenessSession;
use Ninja\Larasoul\Exceptions\VerisoulApiException;
use Ninja\Larasoul\Facades\Verisoul;
use Symfony\Component\HttpFoundation\Response;

class LivenessController extends Controller
{
    public function start(LivenessSession $sessionType): JsonResponse
    {
        if (! $sessionType->enabled()) {
            return response()->json([
                'error' => 'Verisoul is not enabled in this environment',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            $session = $sessionType === LivenessSession::FaceMatch ?
                Verisoul::faceMatch()->session() :
                Verisoul::idCheck()->session();

            return response()->json($session->asResource());
        } catch (VerisoulApiException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }
    }

    public function verify(LivenessSession $sessionType, string $sessionId): JsonResponse
    {
        if (! $sessionType->enabled()) {
            return response()->json([
                'error' => 'Verisoul is not enabled in this environment',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $api = $sessionType === LivenessSession::IDCheck
            ? Verisoul::idCheck()
            : Verisoul::faceMatch();

        try {
            $response = $api->verify($sessionId);

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
