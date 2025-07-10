<?php

namespace Ninja\Larasoul\Listeners;

use Exception;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Ninja\Larasoul\Api\Responses\AuthenticateSessionResponse;
use Ninja\Larasoul\Contracts\RiskProfilable;
use Ninja\Larasoul\Enums\RiskStatus;
use Ninja\Larasoul\Enums\VerisoulDecision;
use Ninja\Larasoul\Events\HighRiskUserDetected;
use Ninja\Larasoul\Events\ManualReviewRequired;
use Ninja\Larasoul\Events\UserRiskVerificationCompleted;
use Ninja\Larasoul\Events\UserRiskVerificationFailed;
use Ninja\Larasoul\Models\RiskProfile;
use Ninja\Larasoul\Services\VerisoulManager;
use Ninja\Larasoul\Services\VerisoulSessionManager;

final readonly class HandleAuthUser
{
    public function __construct(
        private VerisoulManager $verisoulManager,
        private VerisoulSessionManager $sessionManager
    ) {}

    public function handle(Login|Registered $event): void
    {
        $user = $event->user;
        if (! $user instanceof RiskProfilable) {
            Log::info('User is not risk profilable. Skipping', [
                'user_id' => $user->getAuthIdentifier(),
            ]);

            return;
        }

        $sessionId = Session::get(config('larasoul.session.verisoul_session_id'));
        $sessionData = $this->sessionManager->getSessionDataBySessionId($sessionId);

        if (! $sessionData) {
            Log::info('No Verisoul session data found in Laravel session for user login', [
                'user_id' => $user->getAuthIdentifier(),
            ]);

            return;
        }

        // Get or create risk profile
        $riskProfile = RiskProfile::for($user);

        // Now that we have a user, store the session with user association
        $this->sessionManager->storeSessionId(
            sessionId: $sessionData['session_id'],
            userId: $user->getAuthIdentifier(),
            metadata: $sessionData['metadata'] ?? []
        );

        try {
            if ($riskProfile->needsVerification()) {
                $response = $this->verisoulManager->session()->authenticate(
                    account: $user->getVerisoulAccount(),
                    sessionId: $sessionData['session_id']
                );

                $this->updateRiskProfile($riskProfile, $response);
            }

        } catch (Exception $e) {
            Log::error('Failed to authenticate Verisoul session on login', [
                'user_id' => $user->getAuthIdentifier(),
                'session_id' => $sessionData['session_id'],
                'error' => $e->getMessage(),
            ]);

            $riskProfile->update([
                'status' => RiskStatus::Pending,
                'failure_reason' => 'Verisoul authentication failed: '.$e->getMessage(),
                'last_risk_check_at' => now(),
            ]);
        }
    }

    /**
     * Update risk profile with Verisoul response data
     */
    private function updateRiskProfile(RiskProfile $riskProfile, AuthenticateSessionResponse $response): void
    {
        $updateData = [
            'decision' => $response->decision,
            'score' => $response->accountScore,
            'signals' => $response->getRiskSignals(),
            'last_risk_check_at' => now(),
        ];

        // Update status based on decision
        switch ($response->decision) {
            case VerisoulDecision::Real:
                $updateData['status'] = RiskStatus::Verified;
                $updateData['verified_at'] = now();
                $updateData['expires_at'] = now()->addMonths(config('larasoul.verification.expiry_months', 12));
                break;

            case VerisoulDecision::Fake:
                $updateData['status'] = RiskStatus::Failed;
                break;

            case VerisoulDecision::Suspicious:
                $updateData['status'] = RiskStatus::ManualReview;
                break;

            default:
                $updateData['status'] = RiskStatus::Pending;
        }

        // Additional risk assessment based on score
        if (isset($response->accountScore)) {
            $score = $response->accountScore;

            // High risk score should trigger manual review even if decision is Real
            if ($score >= 0.7 && $updateData['status'] === RiskStatus::Verified) {
                $updateData['status'] = RiskStatus::ManualReview;
            }

            // Very high risk should be marked as failed
            if ($score >= 0.9) {
                $updateData['status'] = RiskStatus::Failed;
            }
        }

        $riskProfile->update($updateData);

        // Fire additional events based on the decision
        if ($updateData['status'] === RiskStatus::Failed) {
            event(new UserRiskVerificationFailed($riskProfile));
        } elseif ($updateData['status'] === RiskStatus::ManualReview) {
            event(new ManualReviewRequired($riskProfile));
        } elseif ($updateData['status'] === RiskStatus::Verified) {
            event(new UserRiskVerificationCompleted($riskProfile));
        }

        // Check for high risk
        if (isset($response->accountScore) && $response->accountScore >= 0.8) {
            event(new HighRiskUserDetected($riskProfile));
        }
    }
}
