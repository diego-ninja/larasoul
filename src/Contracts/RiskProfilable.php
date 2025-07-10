<?php

namespace Ninja\Larasoul\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Ninja\Larasoul\DTO\UserAccount;
use Ninja\Larasoul\Enums\RiskLevel;
use Ninja\Larasoul\Enums\RiskStatus;
use Ninja\Larasoul\Enums\VerisoulDecision;
use Ninja\Larasoul\Models\RiskProfile;

interface RiskProfilable extends Authenticatable
{
    public function getVerisoulAccount(): UserAccount;
    public function getRiskProfile(): ?RiskProfile;
    public function hasRiskProfile(): bool;
    public function isVerified(): bool;
    public function isExpired(): bool;
    public function isFullyVerified(): bool;
    public function hasFaceVerification(): bool;
    public function hasPhoneVerification(): bool;
    public function hasIdentityVerification(): bool;
    public function getRiskScore(): float;
    public function getRiskLevel(): RiskLevel;
    public function getDecision(): ?VerisoulDecision;
    public function getRiskStatus(): RiskStatus;
}