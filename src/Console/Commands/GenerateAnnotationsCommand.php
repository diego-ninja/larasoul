<?php

namespace Ninja\Larasoul\Console\Commands;

use Illuminate\Console\Command;
use ReflectionClass;

class GenerateAnnotationsCommand extends Command
{
    protected $signature = 'larasoul:generate-annotations 
                           {--model= : Generate annotations for specific model}
                           {--force : Overwrite existing annotations}';

    protected $description = 'Generate PHPDoc annotations for Larasoul models and traits';

    public function handle(): int
    {
        $this->info('Generating PHPDoc annotations for Larasoul...');

        if ($model = $this->option('model')) {
            return $this->generateForModel($model);
        }

        $this->generateForAllModels();

        $this->info('✅ PHPDoc annotations generated successfully!');

        return self::SUCCESS;
    }

    private function generateForAllModels(): void
    {
        $models = [
            'Ninja\Larasoul\Models\RiskProfile',
        ];

        $traits = [
            'Ninja\Larasoul\Traits\HasRiskProfile',
        ];

        foreach ($models as $model) {
            $this->generateModelAnnotations($model);
        }

        foreach ($traits as $trait) {
            $this->generateTraitAnnotations($trait);
        }

        // Generate User model annotations if it exists and uses the trait
        $this->generateUserModelAnnotations();
    }

    private function generateForModel(string $model): int
    {
        if (! class_exists($model)) {
            $this->error("Model {$model} does not exist");

            return self::FAILURE;
        }

        $this->generateModelAnnotations($model);
        $this->info("✅ Annotations generated for {$model}");

        return self::SUCCESS;
    }

    /**
     * @throws \ReflectionException
     */
    private function generateModelAnnotations(string $modelClass): void
    {
        $reflection = new ReflectionClass($modelClass);
        $properties = $this->getModelProperties($modelClass);
        $methods = $this->getModelMethods($modelClass);
        $scopes = $this->getModelScopes($modelClass);

        $annotations = $this->buildAnnotations($properties, $methods, $scopes);

        $this->info("Generated annotations for {$modelClass}:");
        $this->line($annotations);
    }

    private function generateTraitAnnotations(string $traitClass): void
    {
        $reflection = new ReflectionClass($traitClass);
        $methods = $this->getTraitMethods($traitClass);

        $annotations = $this->buildTraitAnnotations($methods);

        $this->info("Generated trait annotations for {$traitClass}:");
        $this->line($annotations);
    }

    private function generateUserModelAnnotations(): void
    {
        $userModel = config('auth.providers.users.model', 'App\Models\User');

        if (! class_exists($userModel)) {
            return;
        }

        $reflection = new ReflectionClass($userModel);
        $traits = $reflection->getTraitNames();

        if (! in_array('Ninja\Larasoul\Traits\HasVerisoulProfile', $traits)) {
            $this->warn('User model does not use HasVerisoulProfile trait');

            return;
        }

        $this->info('Generating User model annotations with verification support...');

        $verificationProperties = $this->getVerificationProperties();
        $verificationMethods = $this->getVerificationMethods();
        $verificationScopes = $this->getVerificationScopes();

        $annotations = $this->buildUserAnnotations($verificationProperties, $verificationMethods, $verificationScopes);

        $this->line($annotations);
    }

    private function getModelProperties(string $modelClass): array
    {
        // For UserVerification model
        if ($modelClass === 'Ninja\Larasoul\Models\RiskProfile') {
            return [
                // Database columns
                'id' => 'int',
                'user_id' => 'int',
                'session_id' => 'string|null',
                'account_id' => 'string|null',
                'verification_status' => 'string',
                'decision' => 'Ninja\Larasoul\Enums\VerisoulDecision|null',
                'risk_score' => 'float|null',
                'risk_flags' => 'array|null',
                'document_verified_at' => 'Carbon|null',
                'face_verified_at' => 'Carbon|null',
                'phone_verified_at' => 'Carbon|null',
                'identity_verified_at' => 'Carbon|null',
                'document_data' => 'array|null',
                'document_signals' => 'array|null',
                'document_country_code' => 'string|null',
                'document_state' => 'string|null',
                'document_type' => 'string|null',
                'face_match_score' => 'float|null',
                'photo_urls' => 'array|null',
                'device_network_signals' => 'array|null',
                'referring_session_signals' => 'array|null',
                'session_data' => 'array|null',
                'linked_accounts' => 'array|null',
                'num_linked_accounts' => 'int',
                'phone_data' => 'array|null',
                'metadata' => 'array|null',
                'verisoul_request_id' => 'string|null',
                'verified_at' => 'Carbon|null',
                'expires_at' => 'Carbon|null',
                'failure_reason' => 'string|null',
                'last_risk_check_at' => 'Carbon|null',
                'verification_attempts' => 'int',
                'is_active' => 'bool',
                'created_at' => 'Carbon|null',
                'updated_at' => 'Carbon|null',

                // Computed properties
                'risk_level' => 'string',
                'verified_types' => 'array',
                'verification_score' => 'int',
                'health_status' => 'string',
                'days_until_expiration' => 'int|null',
                'is_expired' => 'bool',
                'is_about_to_expire' => 'bool',
                'is_verified' => 'bool',
                'is_fully_verified' => 'bool',
                'has_document_verification' => 'bool',
                'has_face_verification' => 'bool',
                'has_phone_verification' => 'bool',
                'has_identity_verification' => 'bool',
                'requires_manual_review' => 'bool',
                'has_blocking_risk_flags' => 'bool',
                'has_moderate_risk_flags' => 'bool',
                'risk_flags_enum' => 'array',
            ];
        }

        return [];
    }

    private function getModelMethods(string $modelClass): array
    {
        if ($modelClass === 'Ninja\Larasoul\Models\RiskProfile') {
            return [
                'user' => 'BelongsTo',
                'isVerified' => 'bool',
                'isFullyVerified' => 'bool',
                'hasDocumentVerification' => 'bool',
                'hasFaceVerification' => 'bool',
                'hasPhoneVerification' => 'bool',
                'hasIdentityVerification' => 'bool',
                'getRiskLevel' => 'string',
                'requiresManualReview' => 'bool',
                'getRiskFlags' => 'array',
                'hasRiskFlag' => 'bool',
                'hasBlockingRiskFlags' => 'bool',
                'hasModerateRiskFlags' => 'bool',
                'getVerifiedTypes' => 'array',
                'isExpired' => 'bool',
                'getDaysUntilExpiration' => 'int|null',
                'isAboutToExpire' => 'bool',
                'markAsVerified' => 'self',
                'markAsFailed' => 'self',
                'markForManualReview' => 'self',
                'incrementAttempts' => 'self',
                'updateRiskCheck' => 'self',
                'getVerificationScore' => 'int',
                'getHealthStatus' => 'string',
            ];
        }

        return [];
    }

    private function getModelScopes(string $modelClass): array
    {
        if ($modelClass === 'Ninja\Larasoul\Models\UserVerification') {
            return [
                'verified',
                'failed',
                'pending',
                'manualReview',
                'expired',
                'aboutToExpire',
                'lowRisk',
                'mediumRisk',
                'highRisk',
                'documentVerified',
                'faceVerified',
                'phoneVerified',
                'active',
                'recent',
                'needsRiskCheck',
            ];
        }

        return [];
    }

    private function getTraitMethods(string $traitClass): array
    {
        if ($traitClass === 'Ninja\Larasoul\Traits\HasRiskProfile') {
            return [
                'verification' => 'HasOne',
                'verifications' => 'HasMany',
                'hasVerification' => 'bool',
                'isVerified' => 'bool',
                'isFullyVerified' => 'bool',
                'hasDocumentVerification' => 'bool',
                'hasFaceVerification' => 'bool',
                'hasPhoneVerification' => 'bool',
                'hasIdentityVerification' => 'bool',
                'getRiskScore' => 'float',
                'getRiskLevel' => 'string',
                'isLowRisk' => 'bool',
                'isMediumRisk' => 'bool',
                'isHighRisk' => 'bool',
                'getVerificationDecision' => 'Ninja\Larasoul\Enums\VerisoulDecision|null',
                'isReal' => 'bool',
                'isFake' => 'bool',
                'isSuspicious' => 'bool',
                'requiresManualReview' => 'bool',
                'hasRiskFlag' => 'bool',
                'getRiskFlags' => 'array',
                'hasBlockingRiskFlags' => 'bool',
                'getFaceMatchScore' => 'float|null',
                'hasGoodFaceMatch' => 'bool',
                'getDocumentCountry' => 'string|null',
                'getDocumentType' => 'string|null',
                'isVerificationExpired' => 'bool',
                'getVerificationExpiryDate' => 'string|null',
                'getLinkedAccountsCount' => 'int',
                'hasLinkedAccounts' => 'bool',
                'getVerificationAttempts' => 'int',
                'hasExceededVerificationAttempts' => 'bool',
                'getVerificationStatus' => 'string',
                'isVerificationPending' => 'bool',
                'isVerificationFailed' => 'bool',
                'getVerificationFailureReason' => 'string|null',
                'getLastRiskCheckDate' => 'string|null',
                'isRiskCheckDue' => 'bool',
                'getVerificationSummary' => 'array',
                'getVerificationLevel' => 'string',
                'needsVerificationRenewal' => 'bool',
                'canPerformHighValueTransactions' => 'bool',
                'canAccessPremiumFeatures' => 'bool',
                'meetsVerificationRequirement' => 'bool',
                'getMissingVerificationRequirements' => 'array',
                'getVerificationStatusForApi' => 'array',
            ];
        }

        return [];
    }

    private function getVerificationProperties(): array
    {
        return [
            'has_verification' => 'bool',
            'is_verified' => 'bool',
            'is_fully_verified' => 'bool',
            'has_document_verification' => 'bool',
            'has_face_verification' => 'bool',
            'has_phone_verification' => 'bool',
            'has_identity_verification' => 'bool',
            'risk_score' => 'float',
            'risk_level' => 'string',
            'is_low_risk' => 'bool',
            'is_medium_risk' => 'bool',
            'is_high_risk' => 'bool',
            'verification_decision' => 'Ninja\Larasoul\Enums\VerisoulDecision|null',
            'is_real' => 'bool',
            'is_fake' => 'bool',
            'is_suspicious' => 'bool',
            'requires_manual_review' => 'bool',
            'risk_flags' => 'array',
            'has_blocking_risk_flags' => 'bool',
            'face_match_score' => 'float|null',
            'has_good_face_match' => 'bool',
            'document_country' => 'string|null',
            'document_type' => 'string|null',
            'is_verification_expired' => 'bool',
            'verification_expiry_date' => 'string|null',
            'linked_accounts_count' => 'int',
            'has_linked_accounts' => 'bool',
            'verification_attempts' => 'int',
            'has_exceeded_verification_attempts' => 'bool',
            'verification_status' => 'string',
            'is_verification_pending' => 'bool',
            'is_verification_failed' => 'bool',
            'verification_failure_reason' => 'string|null',
            'last_risk_check_date' => 'string|null',
            'is_risk_check_due' => 'bool',
            'verification_summary' => 'array',
            'verification_level' => 'string',
            'needs_verification_renewal' => 'bool',
            'can_perform_high_value_transactions' => 'bool',
            'can_access_premium_features' => 'bool',
            'verification_status_for_api' => 'array',
        ];
    }

    private function getVerificationMethods(): array
    {
        return $this->getTraitMethods('Ninja\Larasoul\Traits\HasRiskProfile');
    }

    private function getVerificationScopes(): array
    {
        return [
            'verified',
            'lowRisk',
            'highRisk',
            'documentVerified',
            'faceVerified',
            'requiresManualReview',
            'eligibleForPremium',
            'needsRenewal',
            'fullyVerified',
        ];
    }

    private function buildAnnotations(array $properties, array $methods, array $scopes): string
    {
        $annotations = [];

        // Properties
        foreach ($properties as $name => $type) {
            $annotations[] = " * @property {$type} \${$name}";
        }

        $annotations[] = ' *';

        // Relations
        $annotations[] = ' * @property-read \\App\\Models\\User $user';

        $annotations[] = ' *';

        // Methods
        foreach ($methods as $name => $returnType) {
            if ($returnType === 'BelongsTo' || $returnType === 'HasOne' || $returnType === 'HasMany') {
                continue; // Skip relation methods
            }
            $annotations[] = " * @method {$returnType} {$name}()";
        }

        $annotations[] = ' *';

        // Scopes
        foreach ($scopes as $scope) {
            $annotations[] = " * @method static Builder|UserVerification {$scope}()";
        }

        return implode("\n", $annotations);
    }

    private function buildTraitAnnotations(array $methods): string
    {
        $annotations = [];

        foreach ($methods as $name => $returnType) {
            $annotations[] = " * @method {$returnType} {$name}()";
        }

        return implode("\n", $annotations);
    }

    private function buildUserAnnotations(array $properties, array $methods, array $scopes): string
    {
        $annotations = [];

        // Verification properties
        $annotations[] = ' * // Verification Properties (from HasVerisoulProfile trait)';
        foreach ($properties as $name => $type) {
            $annotations[] = " * @property-read {$type} \${$name}";
        }

        $annotations[] = ' *';

        // Verification methods
        foreach ($methods as $name => $returnType) {
            if ($returnType === 'HasOne' || $returnType === 'HasMany') {
                continue; // Skip relation methods, they're already defined
            }
            $annotations[] = " * @method {$returnType} {$name}()";
        }

        $annotations[] = ' *';

        // Verification scopes
        foreach ($scopes as $scope) {
            $annotations[] = " * @method static Builder|User {$scope}()";
        }

        return implode("\n", $annotations);
    }
}
