<?php

namespace Ninja\Larasoul\Enums;

enum DocumentSignalStatus: string
{
    // Barcode Status
    case BarcodeValid = 'barcode_valid';
    case BarcodeInvalid = 'barcode_invalid';
    case BarcodeNotPresent = 'barcode_not_present';
    case BarcodeUnreadable = 'barcode_unreadable';
    case BarcodeRequestedButNotFound = 'barcode_requested_but_not_found';

    // Face Status
    case FaceValid = 'face_valid';
    case FaceInvalid = 'face_invalid';
    case FaceNotDetected = 'face_not_detected';
    case FaceBlurry = 'face_blurry';
    case FaceObscured = 'face_obscured';
    case LikelyOriginalFace = 'likely_original_face';

    // Text Status
    case TextValid = 'text_valid';
    case TextInvalid = 'text_invalid';
    case TextUnreadable = 'text_unreadable';
    case TextPartiallyReadable = 'text_partially_readable';
    case LikelyOriginalText = 'likely_original_text';

    // Digital Spoof Status
    case NotSpoof = 'not_spoof';
    case LikelySpoof = 'likely_spoof';
    case DefinitelySpoof = 'definitely_spoof';
    case Unknown = 'unknown';

    // Full ID Captured Status
    case FullIdCaptured = 'full_id_captured';
    case PartialIdCaptured = 'partial_id_captured';
    case IdNotCaptured = 'id_not_captured';

    // ID Validity Status
    case ValidId = 'valid_id';
    case InvalidId = 'invalid_id';
    case ExpiredId = 'expired_id';
    case SuspiciousId = 'suspicious_id';
    case LikelyPhysicalId = 'likely_physical_id';
    case FullIdDetected = 'full_id_detected';

    /**
     * Get all possible status values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Create from string value with validation
     */
    public static function fromString(string $value): ?self
    {
        return self::tryFrom($value);
    }

    /**
     * Get statuses by quality level
     */
    public static function getByQualityLevel(string $level): array
    {
        return array_filter(self::cases(), fn ($status) => $status->getQualityLevel() === $level);
    }

    /**
     * Get valid statuses
     */
    public static function getValidStatuses(): array
    {
        return array_filter(self::cases(), fn ($status) => $status->isValid());
    }

    /**
     * Get invalid statuses
     */
    public static function getInvalidStatuses(): array
    {
        return array_filter(self::cases(), fn ($status) => $status->isInvalid());
    }

    /**
     * Get display name for status
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            // Barcode statuses
            self::BarcodeValid => 'Valid Barcode',
            self::BarcodeInvalid => 'Invalid Barcode',
            self::BarcodeNotPresent => 'No Barcode Present',
            self::BarcodeUnreadable => 'Unreadable Barcode',

            // Face statuses
            self::FaceValid => 'Valid Face',
            self::FaceInvalid => 'Invalid Face',
            self::FaceNotDetected => 'Face Not Detected',
            self::FaceBlurry => 'Blurry Face',
            self::FaceObscured => 'Obscured Face',

            // Text statuses
            self::TextValid => 'Valid Text',
            self::TextInvalid => 'Invalid Text',
            self::TextUnreadable => 'Unreadable Text',
            self::TextPartiallyReadable => 'Partially Readable Text',

            // Digital spoof statuses
            self::NotSpoof => 'Not a Spoof',
            self::LikelySpoof => 'Likely Spoof',
            self::DefinitelySpoof => 'Definitely Spoof',
            self::Unknown => 'Unknown',

            // Full ID captured statuses
            self::FullIdCaptured => 'Full ID Captured',
            self::PartialIdCaptured => 'Partial ID Captured',
            self::IdNotCaptured => 'ID Not Captured',

            // ID validity statuses
            self::ValidId => 'Valid ID',
            self::InvalidId => 'Invalid ID',
            self::ExpiredId => 'Expired ID',
            self::SuspiciousId => 'Suspicious ID',
        };
    }

    /**
     * Check if status indicates success
     */
    public function isValid(): bool
    {
        return match ($this) {
            self::BarcodeValid,
            self::FaceValid,
            self::TextValid,
            self::NotSpoof,
            self::FullIdCaptured,
            self::ValidId => true,
            default => false,
        };
    }

    /**
     * Check if status indicates failure
     */
    public function isInvalid(): bool
    {
        return match ($this) {
            self::BarcodeInvalid,
            self::FaceInvalid,
            self::TextInvalid,
            self::DefinitelySpoof,
            self::IdNotCaptured,
            self::InvalidId => true,
            default => false,
        };
    }

    /**
     * Check if status is inconclusive
     */
    public function isInconclusive(): bool
    {
        return match ($this) {
            self::BarcodeNotPresent,
            self::BarcodeUnreadable,
            self::FaceNotDetected,
            self::FaceBlurry,
            self::FaceObscured,
            self::TextUnreadable,
            self::TextPartiallyReadable,
            self::LikelySpoof,
            self::Unknown,
            self::PartialIdCaptured,
            self::ExpiredId,
            self::SuspiciousId => true,
            default => false,
        };
    }

    /**
     * Get quality level of the status
     */
    public function getQualityLevel(): string
    {
        return match ($this) {
            self::BarcodeValid,
            self::FaceValid,
            self::TextValid,
            self::NotSpoof,
            self::FullIdCaptured,
            self::ValidId => 'high',

            self::TextPartiallyReadable,
            self::PartialIdCaptured,
            self::LikelySpoof,
            self::ExpiredId => 'medium',

            self::BarcodeInvalid,
            self::FaceInvalid,
            self::TextInvalid,
            self::FaceBlurry,
            self::FaceObscured,
            self::TextUnreadable,
            self::SuspiciousId,
            self::InvalidId => 'low',

            default => 'unknown',
        };
    }
}
