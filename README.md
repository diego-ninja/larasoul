# Larasoul

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)](https://php.net/)
[![Laravel Version](https://img.shields.io/badge/laravel-%5E12.0-red)](https://laravel.com/)

> ⚠️ **Development Notice**: This package is currently in active development and may not be ready for production use. APIs and features may change without notice. Some features may or are incomplete or under review. Please use with caution and provide feedback on issues. As recommendation, use the `dev-main` branch for the latest features and fixes.

Larasoul is a Laravel package that provides seamless integration with the Verisoul biometric verification API. It supports identity verification through face matching, ID document verification, liveness detection, and comprehensive risk assessment.

## Features

### Core Verification Services
- **Face Matching**: 1:1 biometric face verification with liveness detection
- **ID Document Verification**: Verify driver's licenses, passports, and national IDs
- **Phone Verification**: Validate phone numbers with carrier and line type information
- **Session Management**: Authenticated and unauthenticated session handling
- **Account Management**: Create, update, and manage user accounts with Verisoul

### Risk Assessment & Security
- **Risk Profiling**: Comprehensive risk assessment with configurable thresholds
- **Security Levels**: Multi-tier security requirements (none, basic, standard, premium, enterprise)
- **Risk Signals**: Detailed risk signal collection and analysis
- **Fraud Detection**: Real-time fraud attempt detection and prevention
- **Manual Review**: Automated flagging for manual review when needed

### Laravel Integration
- **Eloquent Models**: `RiskProfile` and `UserVerification` models with relationships
- **Traits**: `HasRiskProfile` and `HasUserVerifications` for easy user model integration
- **Middleware**: Route protection based on verification status and risk levels
- **Events**: Comprehensive event system for verification lifecycle
- **Facades**: Simple API access through Laravel facades

### Frontend Integration
- **JavaScript SDK**: Official Verisoul SDK integration with automatic script loading
- **Vue.js Support**: Composables, plugins, and components for Vue applications
- **React Support**: Hooks, contexts, and TypeScript definitions for React applications
- **Livewire Support**: Alpine.js stores and Livewire traits for seamless integration

## Requirements

- PHP 8.2 or higher
- Laravel 12.0 or higher
- Valid Verisoul API credentials

## Installation

Install the package via Composer:

```bash
composer require diego-ninja/larasoul
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="larasoul-config"
```

Run the migrations:

```bash
php artisan migrate
```

## Configuration

Configure your environment variables in `.env`:

```env
# Required
VERISOUL_API_KEY=your_api_key_here
VERISOUL_ENVIRONMENT=sandbox # or production

# Optional
VERISOUL_ENABLED=true
VERISOUL_TIMEOUT=30
VERISOUL_RETRY_ATTEMPTS=3
VERISOUL_RETRY_DELAY=1000

# Frontend Integration (optional)
VERISOUL_FRONTEND_ENABLED=true
VERISOUL_PROJECT_ID=your_project_id
VERISOUL_AUTO_INJECT=false
VERISOUL_SESSION_CAPTURE_ENABLED=true
```

## Basic Usage

### Setup User Model

Add the traits to your User model:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Ninja\Larasoul\Traits\HasRiskProfile;
use Ninja\Larasoul\Traits\HasUserVerifications;

class User extends Authenticatable
{
    use HasRiskProfile, HasUserVerifications;
    
    // Your existing model code...
}
```

### API Client Usage

```php
use Ninja\Larasoul\Facades\Verisoul;

// Account operations
$account = Verisoul::account()->getAccount('account_id');
$sessions = Verisoul::account()->getAccountSessions('account_id');

// Face matching
$session = Verisoul::faceMatch()->session();
$result = Verisoul::faceMatch()->verify('session_id', 'account_id');

// ID document verification
$session = Verisoul::idCheck()->session();
$result = Verisoul::idCheck()->verify('session_id', 'account_id');

// Phone verification
$result = Verisoul::phone()->verify('+1234567890', 'account_id');

// Session management
$authSession = Verisoul::session()->authenticate('session_id', 'account_id');
$unauthSession = Verisoul::session()->unauthenticated('session_id');
```

### Risk Assessment

```php
// Check user risk profile
$user = User::find(1);
$riskProfile = $user->getRiskProfile();

if ($riskProfile) {
    echo "Risk Level: " . $riskProfile->risk_level->value;
    echo "Risk Score: " . $riskProfile->risk_score->value;
    echo "Decision: " . $riskProfile->decision->value;
}

// Check specific verifications
$faceVerification = $user->getVerification(VerificationType::Face);
$phoneVerification = $user->getVerification(VerificationType::Phone);
```

### Middleware Protection

Protect routes with verification requirements:

```php
// In your routes file
Route::middleware(['auth', 'require.verification'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

Route::middleware(['auth', 'require.face.verification'])->group(function () {
    Route::get('/secure-area', [SecureController::class, 'index']);
});

Route::middleware(['auth', 'require.risk.level:medium'])->group(function () {
    Route::get('/high-security', [HighSecurityController::class, 'index']);
});
```

## API Endpoints

### Account Management
- `getAccount(string $accountId)` - Get account details
- `getAccountSessions(string $accountId)` - Get account sessions
- `getLinkedAccounts(string $accountId)` - Get linked accounts
- `updateAccount(string $accountId, array $data)` - Update account
- `deleteAccount(string $accountId)` - Delete account

### Face Matching
- `session(?string $referringSessionId = null)` - Start face match session
- `verify(string $sessionId, string $accountId)` - Verify face match
- `verifyIdentity(string $sessionId, string $accountId)` - 1:1 identity verification
- `enroll(string $sessionId, string $accountId)` - Enroll account for face matching

### ID Document Verification
- `session(?string $referringSessionId = null)` - Start ID check session
- `verify(string $sessionId, string $accountId)` - Verify ID document
- `enroll(string $sessionId, string $accountId)` - Enroll account for ID verification

### Phone Verification
- `verify(string $phoneNumber, string $accountId)` - Verify phone number

### Session Management
- `authenticate(string $sessionId, string $accountId)` - Authenticate session
- `unauthenticated(string $sessionId)` - Evaluate unauthenticated session
- `getSession(string $sessionId)` - Get session details

## Events

The package dispatches several events during the verification lifecycle:

- `UserRiskVerificationStarted` - When verification begins
- `UserRiskVerificationCompleted` - When verification completes successfully
- `UserRiskVerificationFailed` - When verification fails
- `UserRiskVerificationExpired` - When verification expires
- `HighRiskUserDetected` - When high-risk user is detected
- `FraudAttemptDetected` - When fraud attempt is detected
- `ManualReviewRequired` - When manual review is required

## Frontend Integration

### Vue.js Example

```vue
<template>
  <div>
    <button @click="startFaceVerification" :disabled="!isReady">
      Start Face Verification
    </button>
    <p v-if="sessionId">Session ID: {{ sessionId }}</p>
  </div>
</template>

<script setup>
import { useVerisoul } from '@/composables/useVerisoul'

const { isReady, sessionId, startVerification } = useVerisoul()

const startFaceVerification = async () => {
  const result = await startVerification('face')
  console.log('Verification result:', result)
}
</script>
```

### React Example

```tsx
import { useVerisoul } from './hooks/useVerisoul'

function VerificationComponent() {
  const { isReady, sessionId, startVerification } = useVerisoul()
  
  const handleFaceVerification = async () => {
    const result = await startVerification('face')
    console.log('Verification result:', result)
  }
  
  return (
    <div>
      <button onClick={handleFaceVerification} disabled={!isReady}>
        Start Face Verification
      </button>
      {sessionId && <p>Session ID: {sessionId}</p>}
    </div>
  )
}
```

## Error Handling

The package includes comprehensive error handling:

```php
use Ninja\Larasoul\Exceptions\VerisoulApiException;
use Ninja\Larasoul\Exceptions\VerisoulConnectionException;
use Ninja\Larasoul\Exceptions\VerificationRequiredException;

try {
    $result = Verisoul::faceMatch()->verify($sessionId, $accountId);
} catch (VerisoulApiException $e) {
    // Handle API errors (4xx, 5xx responses)
    $errorDetails = $e->getErrorDetails();
    Log::error('Verisoul API Error', $errorDetails);
} catch (VerisoulConnectionException $e) {
    // Handle connection errors
    Log::error('Verisoul Connection Error', ['message' => $e->getMessage()]);
} catch (VerificationRequiredException $e) {
    // Handle verification requirement errors
    return redirect()->route('verification.required');
}
```

## Configuration Options

Key configuration options in `config/larasoul.php`:

```php
return [
    'verisoul' => [
        'api_key' => env('VERISOUL_API_KEY'),
        'environment' => env('VERISOUL_ENVIRONMENT', 'sandbox'),
        'timeout' => env('VERISOUL_TIMEOUT', 30),
        'retry_attempts' => env('VERISOUL_RETRY_ATTEMPTS', 3),
        'liveness' => [
            'face_match' => ['enabled' => true],
            'id_check' => ['enabled' => true],
        ],
        'frontend' => [
            'enabled' => env('VERISOUL_FRONTEND_ENABLED', false),
            'project_id' => env('VERISOUL_PROJECT_ID'),
            'session_capture' => ['enabled' => true],
        ],
    ],
    'verification' => [
        'risk_thresholds' => [
            'low' => 0.25,
            'medium' => 0.5,
            'high' => 0.75,
            'critical' => 0.9,
        ],
        'auto_actions' => [
            'suspend_high_risk' => false,
            'approve_low_risk' => true,
        ],
    ],
];
```

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/diego-ninja/larasoul).

## Security

If you discover any security vulnerabilities, please send an email to yosoy@diego.ninja instead of using the issue tracker.