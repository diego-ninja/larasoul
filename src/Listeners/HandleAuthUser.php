<?php

namespace Ninja\Larasoul\Listeners;

use Exception;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Ninja\Larasoul\Contracts\RiskProfilable;
use Ninja\Larasoul\Enums\RiskLevel;
use Ninja\Larasoul\Enums\VerificationStatus;
use Ninja\Larasoul\Events\HighRiskUserDetected;
use Ninja\Larasoul\Models\RiskProfile;
use Ninja\Larasoul\Services\VerisoulApi;
use Ninja\Larasoul\Services\VerisoulSessionManager;

final readonly class HandleAuthUser
{
    public function __construct(
        private VerisoulApi $verisoulManager,
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
            if ($riskProfile->needsAssessment()) {
                $response = $this->verisoulManager->session()->authenticate(
                    account: $user->getVerisoulAccount(),
                    sessionId: $sessionData['session_id']
                );

                $riskProfile->updateRiskAssessment(
                    decision: $response->decision,
                    riskLevel: RiskLevel::withScore($response->accountScore->value()),
                    riskScore: $response->accountScore,
                    riskSignals: $response->getRiskSignals(),
                );

                if ($riskProfile->isHighRisk()) {
                    event(new HighRiskUserDetected($riskProfile));
                }
            }

        } catch (Exception $e) {
            Log::error('Failed to authenticate Verisoul session on login', [
                'user_id' => $user->getAuthIdentifier(),
                'session_id' => $sessionData['session_id'],
                'error' => $e->getMessage(),
            ]);

            $riskProfile->update([
                'status' => VerificationStatus::Pending,
                'failure_reason' => 'Verisoul authentication failed: '.$e->getMessage(),
                'last_risk_check_at' => now(),
            ]);
        }
    }
}
