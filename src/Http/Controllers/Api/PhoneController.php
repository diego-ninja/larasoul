<?php

namespace Ninja\Larasoul\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Ninja\Larasoul\Contracts\RiskProfilable;
use Ninja\Larasoul\Enums\VerificationType;
use Ninja\Larasoul\Exceptions\VerisoulApiException;
use Ninja\Larasoul\Exceptions\VerisoulConnectionException;
use Ninja\Larasoul\Facades\Verisoul;
use Ninja\Larasoul\Http\Requests\VerifyPhoneRequest;
use Symfony\Component\HttpFoundation\Response;

class PhoneController extends Controller
{
    /**
     * Verify a phone number and get carrier information
     */
    public function verify(VerifyPhoneRequest $request): JsonResponse
    {
        $this->guard();

        try {
            $response = Verisoul::phone()->verifyPhone($request->phoneNumber);

            /** @var RiskProfilable $user */
            $user = auth()->user();

            // Mark user as phone verified if the phone number is valid
            if ($response->phone->valid) {
                $user->getRiskProfile()->markAsVerified(VerificationType::Phone);
            }

            return response()->json([
                'success' => true,
                'data' => $response->asResource(),
                'phone_verified' => $response->phone->valid,
            ]);
        } catch (VerisoulApiException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'details' => $e->getErrorDetails(),
            ], $e->getCode());
        } catch (VerisoulConnectionException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Connection error: '.$e->getMessage(),
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    private function guard(): void
    {
        if (! config('larasoul.verisoul.enabled')) {
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
