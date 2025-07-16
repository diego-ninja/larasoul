<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Ninja\Larasoul\Enums\RiskLevel;
use Ninja\Larasoul\Enums\SecurityLevel;
use Ninja\Larasoul\Enums\VerificationStatus;
use Ninja\Larasoul\Enums\VerificationType;
use Ninja\Larasoul\Enums\VerisoulDecision;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_profile', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('decision', VerisoulDecision::values())->nullable()->index();
            $table->enum('risk_level', RiskLevel::values())->nullable()->default('unknown')->index();
            $table->enum('security_level', SecurityLevel::values())->nullable()->default('none')->index();
            $table->decimal('risk_score', 3)->nullable()->index(); // 0.00 to 1.00
            $table->json('risk_signals')->nullable();
            $table->timestamp('assessed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique('user_id', 'risk_profile_unique');
            $table->index(['user_id', 'decision']);
            $table->index(['score', 'decision']);
            $table->index(['assessed_at', 'expires_at']);
        });

        Schema::create('user_verification', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', VerificationType::values())->index();
            $table->enum('status', VerificationStatus::values())->nullable()->default('pending')->index();
            $table->enum('decision', VerisoulDecision::values())->nullable()->index();
            $table->decimal('risk_score', 3)->nullable()->index(); // 0.00 to 1.00
            $table->json('risk_signals')->nullable();
            $table->json('risk_flags')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'type'], 'user_verification_unique');
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'decision']);
            $table->index(['user_id', 'verified_at']);
            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_profile');
        Schema::dropIfExists('user_verification');
    }
};
