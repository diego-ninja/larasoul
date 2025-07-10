# Larasoul Middleware Usage Guide

This guide explains how to properly use Larasoul verification middlewares, including programmatic configuration and helper classes.

## Prerequisites

Ensure your User model uses the `HasVerificationProfile` trait:

```php
use Ninja\Larasoul\Traits\HasRiskProfile;

class User extends Authenticatable
{
    use HasRiskProfile;
    
    // ... rest of the model
}
```

## Available Middlewares

### 1. RequireVerification
Base middleware that verifies the user is authenticated and verified.

```php
// In routes/web.php
Route::group(['middleware' => 'require.verification'], function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

// With custom redirect parameter
Route::get('/profile', [ProfileController::class, 'show'])
    ->middleware('require.verification:verification.custom');
```

### 2. RequireDocumentVerification
Requires document verification in addition to basic verification.

```php
Route::get('/kyc-required', [KycController::class, 'index'])
    ->middleware('require.document');
```

### 3. RequireFaceVerification  
Requires face verification in addition to basic verification.

```php
Route::get('/secure-area', [SecureController::class, 'index'])
    ->middleware('require.face');
```

### 4. RequirePhoneVerification
Requires phone verification in addition to basic verification.

```php
Route::get('/phone-required', [PhoneController::class, 'index'])
    ->middleware('require.phone');
```

### 5. RequireVerificationType
Generic middleware for specific verification types.

```php
// Equivalent to RequireDocumentVerification
Route::get('/documents', [DocumentController::class, 'index'])
    ->middleware('require.verification.type:document');

// Multiple types can require multiple middleware
Route::get('/full-verification', [FullController::class, 'index'])
    ->middleware(['require.verification.type:document', 'require.verification.type:face']);
```

### 6. RequireVerificationLevel
Requires a specific verification level.

```php
// Basic level (phone only)
Route::get('/basic', [BasicController::class, 'index'])
    ->middleware('require.verification.level:basic');

// Standard level (phone + face)
Route::get('/standard', [StandardController::class, 'index'])
    ->middleware('require.verification.level:standard');

// Premium level (phone + face + document)
Route::get('/premium', [PremiumController::class, 'index'])
    ->middleware('require.verification.level:premium');

// High value level (full verification)
Route::get('/high-value', [HighValueController::class, 'index'])
    ->middleware('require.verification.level:high_value');
```

### 7. RequireRiskLevel
Requires the user doesn't exceed a maximum risk level.

```php
// Low risk users only
Route::get('/sensitive', [SensitiveController::class, 'index'])
    ->middleware('require.risk.level:low');

// Low or medium risk users
Route::get('/standard', [StandardController::class, 'index'])
    ->middleware('require.risk.level:medium');

// With custom redirect for high risk users
Route::get('/protected', [ProtectedController::class, 'index'])
    ->middleware('require.risk.level:medium,account.risk-review');
```

## Programmatic Middleware Usage

### VerificationMiddleware Helper

For dynamic middleware building, use the `VerificationMiddleware` helper:

```php
use Ninja\Larasoul\Http\Helpers\VerificationMiddleware;

// Basic verification
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(VerificationMiddleware::verification());

// With custom redirect
Route::get('/profile', [ProfileController::class, 'show'])
    ->middleware(VerificationMiddleware::verification('custom.verification'));

// Document verification
Route::get('/kyc', [KycController::class, 'index'])
    ->middleware(VerificationMiddleware::document());

// Face verification
Route::get('/selfie', [SelfieController::class, 'index'])
    ->middleware(VerificationMiddleware::face());

// Phone verification
Route::get('/sms', [SmsController::class, 'index'])
    ->middleware(VerificationMiddleware::phone());

// Risk level with redirect
Route::get('/sensitive', [SensitiveController::class, 'index'])
    ->middleware(VerificationMiddleware::riskLevel('low', 'risk.review'));

// Verification level
Route::get('/premium', [PremiumController::class, 'index'])
    ->middleware(VerificationMiddleware::verificationLevel('premium'));

// Verification type
Route::get('/documents', [DocumentController::class, 'index'])
    ->middleware(VerificationMiddleware::verificationType('document'));
```

### VerificationRoutes Helper

For route groups with predefined middleware combinations:

```php
use Ninja\Larasoul\Http\Helpers\VerificationRoutes;

// Basic verified routes
VerificationRoutes::verified(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/profile', [ProfileController::class, 'show']);
});

// Low risk routes
VerificationRoutes::lowRisk(function () {
    Route::get('/financial', [FinancialController::class, 'index']);
    Route::post('/transfer', [TransferController::class, 'store']);
});

// Premium verification routes
VerificationRoutes::premium(function () {
    Route::get('/premium-features', [PremiumController::class, 'index']);
    Route::post('/high-value-action', [PremiumController::class, 'action']);
});

// High security routes
VerificationRoutes::highSecurity(function () {
    Route::get('/admin', [AdminController::class, 'index']);
    Route::delete('/critical-action', [AdminController::class, 'delete']);
});

// Specific verification requirements
VerificationRoutes::requireDocument(function () {
    Route::get('/kyc-dashboard', [KycController::class, 'dashboard']);
});

VerificationRoutes::requireFace(function () {
    Route::get('/biometric-area', [BiometricController::class, 'index']);
});

VerificationRoutes::requirePhone(function () {
    Route::get('/phone-verified-area', [PhoneController::class, 'index']);
});

// Custom risk levels
VerificationRoutes::requireRiskLevel('low', function () {
    Route::get('/ultra-secure', [UltraSecureController::class, 'index']);
});

// Custom verification levels
VerificationRoutes::requireLevel('high_value', function () {
    Route::get('/high-value-transactions', [TransactionController::class, 'index']);
});
```

## Kernel Registration

### Automatic Registration

Larasoul automatically registers middleware using the `MiddlewareConfig` helper. Add to your `app/Http/Kernel.php`:

```php
use Ninja\Larasoul\Http\Helpers\MiddlewareConfig;

protected $routeMiddleware = [
    // ... other middlewares
    
    // Add Larasoul middlewares
    ...MiddlewareConfig::getRouteMiddleware(),
];

protected $middlewareGroups = [
    'web' => [
        // ... existing middleware
    ],
    
    'api' => [
        // ... existing middleware
    ],
    
    // Add Larasoul middleware groups
    ...MiddlewareConfig::getMiddlewareGroups(),
];
```

### Manual Registration

Alternatively, register manually:

```php
protected $routeMiddleware = [
    // ... other middlewares
    
    // Larasoul middlewares
    'require.verification' => \Ninja\Larasoul\Http\Middleware\RequireVerification::class,
    'require.document' => \Ninja\Larasoul\Http\Middleware\RequireDocumentVerification::class,
    'require.face' => \Ninja\Larasoul\Http\Middleware\RequireFaceVerification::class,
    'require.phone' => \Ninja\Larasoul\Http\Middleware\RequirePhoneVerification::class,
    'require.verification.type' => \Ninja\Larasoul\Http\Middleware\RequireVerificationType::class,
    'require.verification.level' => \Ninja\Larasoul\Http\Middleware\RequireVerificationLevel::class,
    'require.risk.level' => \Ninja\Larasoul\Http\Middleware\RequireRiskLevel::class,
];
```

## Predefined Middleware Groups

The `MiddlewareConfig` helper provides predefined groups:

```php
// Access predefined groups
$groups = MiddlewareConfig::getMiddlewareGroups();

// Available groups:
// 'verified' => Basic verification required
// 'low-risk' => Basic verification + low risk requirement  
// 'premium-verified' => Premium verification + medium risk max
// 'high-security' => High value verification + low risk max

// Use in routes
Route::group(['middleware' => 'verified'], function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

Route::group(['middleware' => 'high-security'], function () {
    Route::get('/admin', [AdminController::class, 'index']);
    Route::get('/financial', [FinancialController::class, 'index']);
});
```

## Level Configuration

Define verification levels in `config/larasoul.php`:

```php
'verification' => [
    'requirements' => [
        'basic' => ['phone'],
        'standard' => ['phone', 'face'],
        'premium' => ['phone', 'face', 'document'],
        'high_value' => ['phone', 'face', 'document', 'identity'],
    ],
    
    'routes' => [
        'document' => 'verification.document',
        'face' => 'verification.face',
        'phone' => 'verification.phone',
        'identity' => 'verification.identity',
    ],
],
```

## Middleware Responses

### JSON Responses (API)
For AJAX/API requests, middlewares return JSON:

```json
{
    "message": "Face verification is required to access this resource",
    "error": "face_verification_required",
    "verification_url": "https://app.com/verification/face",
    "current_verifications": ["phone"],
    "missing_requirements": ["face"]
}
```

### HTML Responses (Web)
For web requests, middlewares redirect with session data:

```php
// In your verification view
@if(session('verification_message'))
    <div class="alert alert-warning">
        {{ session('verification_message') }}
    </div>
@endif

@if(session('missing_requirements'))
    <ul>
        @foreach(session('missing_requirements') as $requirement)
            <li>{{ ucfirst($requirement) }} verification required</li>
        @endforeach
    </ul>
@endif
```

## Combining Middlewares

You can combine multiple middlewares for complex requirements:

```php
// Requires full verification AND low risk
Route::get('/ultra-secure', [UltraSecureController::class, 'index'])
    ->middleware([
        'require.verification.level:premium',
        'require.risk.level:low'
    ]);

// Using helpers for complex combinations
Route::get('/complex', [ComplexController::class, 'index'])
    ->middleware([
        VerificationMiddleware::verificationLevel('premium'),
        VerificationMiddleware::riskLevel('low', 'risk.review')
    ]);

// Requires specific types
Route::get('/custom-verification', [CustomController::class, 'index'])
    ->middleware([
        VerificationMiddleware::document(),
        VerificationMiddleware::face()
    ]);
```

## Guard Configurations

The `MiddlewareConfig` also provides guard configurations for authentication:

```php
// In config/auth.php
'guards' => [
    // ... existing guards
    
    // Add Larasoul verification guards
    ...MiddlewareConfig::getGuardConfigurations(),
];

// Available guards:
// 'verification' => Basic verification guard
// 'high-security' => High security verification guard
// 'api-verification' => API verification guard
```

## Error Handling

Middlewares throw specific exceptions you can catch:

```php
// In app/Exceptions/Handler.php
public function render($request, Exception $exception)
{
    if ($exception instanceof \Ninja\Larasoul\Exceptions\VerificationRequiredException) {
        return response()->view('auth.verification-required', [], 403);
    }
    
    if ($exception instanceof \Ninja\Larasoul\Exceptions\HighRiskUserException) {
        return response()->view('auth.high-risk-blocked', [], 403);
    }
    
    return parent::render($request, $exception);
}
```

## Testing

For tests, you can mock verification status:

```php
public function test_verified_user_can_access_protected_route()
{
    $user = User::factory()->create();
    
    // Mock verification status
    $user->shouldReceive('isVerified')->andReturn(true);
    $user->shouldReceive('hasDocumentVerification')->andReturn(true);
    
    $this->actingAs($user)
        ->get('/protected')
        ->assertSuccessful();
}

public function test_middleware_helper_builds_correct_middleware()
{
    $middleware = VerificationMiddleware::verification('custom.route');
    
    $this->assertEquals(
        'Ninja\Larasoul\Http\Middleware\RequireVerification:custom.route',
        $middleware
    );
}
```

## Troubleshooting

### Trait Not Found
If you see errors about `HasVerificationProfile trait`, ensure:
1. Import the trait in your User model
2. Run Larasoul migrations
3. Verify config is published

### Routes Not Found
Define verification routes in your application:
```php
Route::get('/verification/start', [VerificationController::class, 'start'])->name('verification.start');
Route::get('/verification/document', [VerificationController::class, 'document'])->name('verification.document');
Route::get('/verification/face', [VerificationController::class, 'face'])->name('verification.face');
Route::get('/verification/phone', [VerificationController::class, 'phone'])->name('verification.phone');
```

### Helper Classes Not Working
Ensure helpers are autoloaded:
```php
// In composer.json
"autoload": {
    "psr-4": {
        "Ninja\\Larasoul\\": "src/"
    }
}
```

Then run: `composer dump-autoload`

## Advanced Usage Examples

### Dynamic Middleware Assignment

```php
use Ninja\Larasoul\Http\Helpers\VerificationMiddleware;

class RouteServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Dynamic middleware based on config
        $securityLevel = config('app.security_level', 'standard');
        
        Route::group([
            'middleware' => $this->getSecurityMiddleware($securityLevel)
        ], function () {
            require base_path('routes/secure.php');
        });
    }
    
    private function getSecurityMiddleware(string $level): array
    {
        return match($level) {
            'high' => [
                'auth',
                VerificationMiddleware::verificationLevel('premium'),
                VerificationMiddleware::riskLevel('low')
            ],
            'medium' => [
                'auth',
                VerificationMiddleware::verification()
            ],
            default => ['auth']
        };
    }
}
```

### Conditional Middleware Application

```php
use Ninja\Larasoul\Http\Helpers\VerificationRoutes;

// Apply different security based on feature flags
if (config('features.enhanced_security')) {
    VerificationRoutes::highSecurity(function () {
        Route::resource('transactions', TransactionController::class);
    });
} else {
    VerificationRoutes::verified(function () {
        Route::resource('transactions', TransactionController::class);
    });
}
```

This comprehensive guide covers all middleware functionality including the helper classes for programmatic configuration and route grouping.