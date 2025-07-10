<?php

namespace Database\Factories\Ninja\Larasoul\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Ninja\Larasoul\Enums\IDBarcodeStatus;
use Ninja\Larasoul\Enums\IDDigitalSpoof;
use Ninja\Larasoul\Enums\IDFaceStatus;
use Ninja\Larasoul\Enums\IDStatus;
use Ninja\Larasoul\Enums\IDTextStatus;
use Ninja\Larasoul\Enums\IDValidity;
use Ninja\Larasoul\Enums\VerisoulDecision;
use Ninja\Larasoul\Models\RiskProfile;
use Ninja\Larasoul\Models\VerificationDocument;

class VerificationDocumentFactory extends Factory
{
    protected $model = VerificationDocument::class;

    public function definition(): array
    {
        $decision = $this->faker->randomElement(VerisoulDecision::cases());
        $riskScore = match ($decision) {
            VerisoulDecision::Real => $this->faker->randomFloat(2, 0.0, 0.3),
            VerisoulDecision::Suspicious => $this->faker->randomFloat(2, 0.3, 0.7),
            VerisoulDecision::Fake => $this->faker->randomFloat(2, 0.7, 1.0),
        };

        $documentTypes = ['Driver License', 'Passport', 'National ID', 'State ID'];
        $countries = ['US', 'CA', 'GB', 'DE', 'FR', 'AU'];
        $states = ['CA', 'NY', 'TX', 'FL', 'WA', 'ON', 'BC'];

        return [
            'verification_profile_id' => RiskProfile::factory(),
            'request_id' => 'req_'.$this->faker->uuid(),
            'decision' => $decision,
            'risk_score' => $riskScore,
            'risk_flags' => $this->faker->randomElements([
                'multiple_accounts',
                'device_mismatch',
                'location_mismatch',
                'suspicious_behavior',
            ], $this->faker->numberBetween(0, 2)),
            'document_type' => $this->faker->randomElement($documentTypes),
            'document_country_code' => $this->faker->randomElement($countries),
            'document_state' => $this->faker->randomElement($states),
            'template_type' => $this->faker->randomElement(['driver_license', 'passport', 'national_id']),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'date_of_birth' => $this->faker->dateTimeBetween('-80 years', '-18 years'),
            'date_of_expiration' => $this->faker->dateTimeBetween('now', '+10 years'),
            'date_of_issue' => $this->faker->dateTimeBetween('-10 years', 'now'),
            'id_number' => $this->faker->regexify('[A-Z]{2}[0-9]{8}'),
            'secondary_id_number' => $this->faker->optional()->regexify('[0-9]{6}'),
            'address_street' => $this->faker->streetAddress(),
            'address_city' => $this->faker->city(),
            'address_state' => $this->faker->randomElement($states),
            'address_postal_code' => $this->faker->postcode(),
            'address_country' => $this->faker->randomElement($countries),
            'id_age' => $this->faker->numberBetween(1, 10),
            'face_match_score' => $this->faker->randomFloat(2, 0.5, 1.0),
            'barcode_status' => $this->faker->randomElement(IDBarcodeStatus::cases()),
            'face_status' => $this->faker->randomElement(IDFaceStatus::cases()),
            'text_status' => $this->faker->randomElement(IDTextStatus::cases()),
            'is_digital_spoof' => $this->faker->randomElement(IDDigitalSpoof::cases()),
            'is_full_id_captured' => $this->faker->randomElement(IDStatus::cases()),
            'id_validity' => $this->faker->randomElement(IDValidity::cases()),
            'photo_urls' => [
                'front' => $this->faker->imageUrl(640, 480, 'business'),
                'back' => $this->faker->imageUrl(640, 480, 'business'),
                'selfie' => $this->faker->imageUrl(480, 640, 'people'),
            ],
            'metadata' => [
                'user_agent' => $this->faker->userAgent(),
                'ip_address' => $this->faker->ipv4(),
                'device_id' => $this->faker->uuid(),
            ],
            'processing_status' => 'processed',
            'processed_at' => now(),
            'verified_at' => $decision === VerisoulDecision::Real ? now() : null,
        ];
    }

    /**
     * Create a verified document
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'decision' => VerisoulDecision::Real,
            'risk_score' => $this->faker->randomFloat(2, 0.0, 0.3),
            'processing_status' => 'processed',
            'processed_at' => now(),
            'verified_at' => now(),
        ]);
    }

    /**
     * Create a suspicious document
     */
    public function suspicious(): static
    {
        return $this->state(fn (array $attributes) => [
            'decision' => VerisoulDecision::Suspicious,
            'risk_score' => $this->faker->randomFloat(2, 0.3, 0.7),
            'processing_status' => 'processed',
            'processed_at' => now(),
            'verified_at' => null,
        ]);
    }

    /**
     * Create a fake document
     */
    public function fake(): static
    {
        return $this->state(fn (array $attributes) => [
            'decision' => VerisoulDecision::Fake,
            'risk_score' => $this->faker->randomFloat(2, 0.7, 1.0),
            'processing_status' => 'processed',
            'processed_at' => now(),
            'verified_at' => null,
        ]);
    }

    /**
     * Create a pending document
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'decision' => null,
            'risk_score' => null,
            'processing_status' => 'pending',
            'processed_at' => null,
            'verified_at' => null,
        ]);
    }

    /**
     * Create an expired document
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_of_expiration' => $this->faker->dateTimeBetween('-2 years', '-1 day'),
        ]);
    }

    /**
     * Create a document for a specific country
     */
    public function forCountry(string $countryCode): static
    {
        return $this->state(fn (array $attributes) => [
            'document_country_code' => $countryCode,
        ]);
    }

    /**
     * Create a driver license document
     */
    public function driverLicense(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'Driver License',
            'template_type' => 'driver_license',
        ]);
    }

    /**
     * Create a passport document
     */
    public function passport(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'Passport',
            'template_type' => 'passport',
        ]);
    }
}
