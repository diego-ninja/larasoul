# Larasoul Authentication Guards

This document explains how to use the specialized authentication guards provided by Larasoul for different security levels and verification requirements.

## Overview

Larasoul provides three specialized authentication guards that extend Laravel's authentication system with verification and risk assessment capabilities:

- **VerificationGuard** - Base guard with configurable verification requirements
- **HighSecurityVerificationGuard** - Guard for high-security operations requiring full verification
- **ApiVerificationGuard** - Smart guard for API routes that adapts to middleware requirements

## Configuration

### Guard Registration

Add the guards to your `config/auth.php`:

```php
use Ninja\Larasoul\Http\Helpers\MiddlewareConfig;

'guards' => [
    // ... existing guards
    
    // Add Larasoul verification guards
    ...MiddlewareConfig::getGuardConfigurations(),
    
    // Or register manually:
    'verification' => [
        'driver' => 'verification',
        'provider' => 'users',
        'input_key' => 'api_token',
        'storage_key' => 'api_token', 
        'hash' => 'sha256',
    ],
    
    'high-security' => [
        'driver' => 'high-security-verification',
        'provider' => 'users',
        'input_key' => 'api_token',
        'storage_key' => 'api_token',
        'hash' => 'sha256',
    ],
    
    'api-verification' => [
        'driver' => 'api-verification',
        'provider' => 'users',
        'input_key' => 'api_token',
        'storage_key' => 'api_token',
        'hash' => 'sha256',
    ],
],
```

### Service Provider Registration

Larasoul automatically registers the guard drivers in its service provider:

```php
// Automatically registered in LarasoulServiceProvider
Auth::extend('verification', function ($app, $name, $config) {
    return new VerificationGuard(
        Auth::createUserProvider($config['provider']),
        $app['request'],
        $config['input_key'] ?? 'api_token',
        $config['storage_key'] ?? 'api_token',
        $config['hash'] ?? 'sha256'
    );
});
```

### Guard Configuration

Configure guard behavior in `config/larasoul.php`:

```php
'verification' => [
    'guards' => [
        'Ninja\Larasoul\Auth\Guards\VerificationGuard' => [
            'require_verification' => true,
            'check_risk_level' => true,
            'max_risk_level' => 'medium',
        ],
        'Ninja\Larasoul\Auth\Guards\HighSecurityVerificationGuard' => [
            'require_verification' => true,
            'check_risk_level' => true,
            'max_risk_level' => 'low',
        ],
        'Ninja\Larasoul\Auth\Guards\ApiVerificationGuard' => [
            'require_verification' => false, // Dynamic based on middleware
            'check_risk_level' => false,     // Dynamic based on middleware
            'max_risk_level' => 'medium',
        ],
    ],
],
```

## Guards Overview

### 1. VerificationGuard

The base verification guard provides configurable verification and risk checking.

**Features:**
- Token-based authentication with verification checks
- Configurable verification requirements
- Risk level enforcement
- Verification expiry checking
- Blocking flag detection

**Use Cases:**
- General API authentication with verification
- Admin panels requiring verified users
- Protected areas with configurable security

**Configuration Options:**
- `require_verification` - Require user to be verified
- `check_risk_level` - Enable risk level checking
- `max_risk_level` - Maximum allowed risk level (low/medium/high)

### 2. HighSecurityVerificationGuard

Extended guard for high-security operations requiring full verification and low risk.

**Features:**
- Always requires verification
- Always checks risk level (max: low)
- Requires full verification (all verification types)
- Checks for recent suspicious activity
- Additional security validations

**Use Cases:**
- Financial transactions
- Administrative operations
- Critical data access
- High-value actions

**Additional Checks:**
- Full verification requirement
- Recent suspicious activity detection
- Failed verification attempt monitoring

### 3. ApiVerificationGuard

Smart guard that dynamically adapts requirements based on route middleware.

**Features:**
- Dynamic verification requirements based on middleware
- Route-aware risk level checking
- Automatic configuration from middleware parameters
- API-optimized authentication flow

**Use Cases:**
- API endpoints with varying security requirements
- RESTful services with mixed protection levels
- Microservices with different verification needs

**Dynamic Behavior:**
- Reads `require.verification` middleware to determine verification needs
- Extracts risk level from `require.risk.level:X` middleware
- Adapts security level per route

## Usage Examples

### Basic Authentication with Verification

```php
// Route using verification guard
Route::middleware('auth:verification')->group(function () {
    Route::get('/verified-area', [VerifiedController::class, 'index']);
    Route::post('/verified-action', [VerifiedController::class, 'store']);
});
```

### High Security Operations

```php
// Route requiring high security guard
Route::middleware('auth:high-security')->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
    Route::post('/financial-transfer', [FinancialController::class, 'transfer']);
    Route::delete('/delete-account', [AccountController::class, 'destroy']);
});
```

### Smart API Authentication

```php
// API routes with adaptive security
Route::middleware('auth:api-verification')->group(function () {
    // Basic API access (no additional requirements)
    Route::get('/profile', [ProfileController::class, 'show']);
    
    // Requires verification (detected from middleware)
    Route::middleware('require.verification')->group(function () {
        Route::put('/profile', [ProfileController::class, 'update']);
    });
    
    // Requires low risk (detected from middleware)
    Route::middleware('require.risk.level:low')->group(function () {
        Route::post('/transfer', [TransferController::class, 'store']);
    });
});
```

### Programmatic Guard Selection

```php
use Illuminate\Support\Facades\Auth;

class SecurityController extends Controller
{
    public function sensitiveOperation(Request $request)
    {
        // Switch to high security guard for this operation
        Auth::shouldUse('high-security');
        
        $user = Auth::user(); // Will enforce high security checks
        
        // Proceed with sensitive operation
        return $this->performSensitiveAction($user);
    }
    
    public function adaptiveApiEndpoint(Request $request)
    {
        // Use API guard that adapts to middleware
        Auth::shouldUse('api-verification');
        
        $user = Auth::user(); // Security level based on route middleware
        
        return response()->json(['user' => $user]);
    }
}
```

## Authentication Flow

### 1. Token Extraction
Guards extract tokens from multiple sources in order:
1. Query parameter (`?api_token=xxx`)
2. Request input (`api_token` in POST body)
3. Bearer token (`Authorization: Bearer xxx`)
4. HTTP basic auth password

### 2. User Retrieval
- Hash the token using specified algorithm (default: sha256)
- Retrieve user by hashed token from user provider
- Return null if no user found

### 3. Verification Checks
If user is found, perform verification checks:

1. **Verification Status Check**
   - Skip if verification disabled in config
   - Check if user has verification trait
   - Verify user is verified (if required)
   - Check verification expiry

2. **Risk Level Check**
   - Check user's current risk level
   - Compare against maximum allowed for guard
   - Throw exception if risk too high

3. **Additional Checks** (varies by guard)
   - Blocking flags detection
   - Full verification requirement
   - Suspicious activity monitoring

### 4. Exception Handling
Guards throw specific exceptions:
- `VerificationRequiredException` - User not verified or verification expired
- `HighRiskUserException` - User risk level too high or blocking flags present

## Exception Handling

### Catching Guard Exceptions

```php
// In app/Exceptions/Handler.php
public function render($request, Exception $exception)
{
    if ($exception instanceof \Ninja\Larasoul\Exceptions\VerificationRequiredException) {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'verification_required',
                'message' => 'User verification is required',
                'verification_url' => route('verification.start')
            ], 401);
        }
        
        return redirect()->route('verification.start');
    }
    
    if ($exception instanceof \Ninja\Larasoul\Exceptions\HighRiskUserException) {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'high_risk_user',
                'message' => 'UserAccount flagged for security review',
                'contact_support' => route('support.contact')
            ], 403);
        }
        
        return redirect()->route('account.security-review');
    }
    
    return parent::render($request, $exception);
}
```

### Custom Exception Responses

```php
// Custom middleware for handling guard exceptions
class HandleGuardExceptions
{
    public function handle($request, Closure $next)
    {
        try {
            return $next($request);
        } catch (VerificationRequiredException $e) {
            return $this->handleVerificationRequired($request, $e);
        } catch (HighRiskUserException $e) {
            return $this->handleHighRiskUser($request, $e);
        }
    }
    
    private function handleVerificationRequired($request, $exception)
    {
        Log::info('Verification required for user', [
            'user_id' => $exception->getUser()?->id,
            'route' => $request->route()?->getName(),
            'ip' => $request->ip()
        ]);
        
        return response()->json([
            'error' => 'verification_required',
            'user_id' => $exception->getUser()?->id,
            'verification_types_needed' => $exception->getMissingVerifications()
        ], 401);
    }
}
```

## Testing Guards

### Unit Testing

```php
use Ninja\Larasoul\Auth\Guards\VerificationGuard;
use Tests\TestCase;

class VerificationGuardTest extends TestCase
{
    public function test_verified_user_can_authenticate()
    {
        $user = User::factory()->create();
        $user->markAsVerified();
        
        $token = 'test-token';
        $user->api_token = hash('sha256', $token);
        $user->save();
        
        $request = Request::create('/test', 'GET', ['api_token' => $token]);
        $guard = new VerificationGuard(
            $this->app['auth']->createUserProvider('users'),
            $request
        );
        
        $this->assertEquals($user->id, $guard->user()->id);
    }
    
    public function test_unverified_user_cannot_authenticate()
    {
        $user = User::factory()->create();
        // Don't verify user
        
        $token = 'test-token';
        $user->api_token = hash('sha256', $token);
        $user->save();
        
        $request = Request::create('/test', 'GET', ['api_token' => $token]);
        $guard = new VerificationGuard(
            $this->app['auth']->createUserProvider('users'),
            $request
        );
        
        $this->expectException(VerificationRequiredException::class);
        $guard->user();
    }
}
```

### Feature Testing

```php
class GuardIntegrationTest extends TestCase
{
    public function test_high_security_guard_blocks_medium_risk_user()
    {
        $user = User::factory()->create();
        $user->markAsFullyVerified();
        $user->setRiskLevel('medium');
        
        $this->actingAs($user, 'high-security')
            ->get('/admin')
            ->assertStatus(403);
    }
    
    public function test_api_guard_adapts_to_middleware()
    {
        $user = User::factory()->create();
        
        // Route without verification middleware
        $this->actingAs($user, 'api-verification')
            ->get('/api/public')
            ->assertSuccessful();
        
        // Route with verification middleware should fail
        $this->actingAs($user, 'api-verification')
            ->get('/api/verified')
            ->assertStatus(401);
    }
}
```

## Advanced Configuration

### Custom Guard Behavior

```php
// Extend guards for custom behavior
class ComplianceVerificationGuard extends HighSecurityVerificationGuard
{
    protected function performVerificationChecks(Authenticatable $user): void
    {
        parent::performVerificationChecks($user);
        
        // Additional compliance checks
        if (method_exists($user, 'hasValidComplianceDocuments')) {
            if (!$user->hasValidComplianceDocuments()) {
                throw new ComplianceRequiredException($user);
            }
        }
        
        // Check geographic restrictions
        if ($this->isRestrictedLocation()) {
            throw new GeographicRestrictionException();
        }
    }
}
```

### Dynamic Risk Level Configuration

```php
// Service provider boot method
public function boot()
{
    // Configure guards based on environment
    $riskConfig = match(app()->environment()) {
        'production' => ['max_risk_level' => 'low'],
        'staging' => ['max_risk_level' => 'medium'],
        'testing' => ['max_risk_level' => 'high'],
        default => ['max_risk_level' => 'medium']
    };
    
    config(['larasoul.verification.guards' => array_merge(
        config('larasoul.verification.guards', []),
        ['Ninja\Larasoul\Auth\Guards\VerificationGuard' => $riskConfig]
    )]);
}
```

## Security Considerations

### Token Security
- Always use HTTPS in production
- Hash tokens with strong algorithms (sha256 minimum)
- Implement token rotation for high-security applications
- Consider token expiration for additional security

### Risk Assessment
- Monitor failed authentication attempts
- Log suspicious activity patterns
- Implement rate limiting for authentication endpoints
- Regular review of risk level configurations

### Verification Requirements
- Keep verification requirements proportional to risk
- Provide clear user guidance for verification processes
- Implement graceful degradation for partially verified users
- Monitor verification completion rates

## Troubleshooting

### Common Issues

**Guard Not Working**
1. Verify guard is registered in `config/auth.php`
2. Check service provider is loaded
3. Ensure configuration is published

**Verification Checks Failing**
1. Verify user model has `HasVerificationProfile` trait
2. Check verification configuration in `config/larasoul.php`
3. Ensure user verification status is correct

**Token Authentication Issues**
1. Verify token hashing algorithm matches
2. Check token is being sent correctly
3. Ensure user provider configuration is correct

### Debug Mode

Enable debug logging for guards:

```php
// In config/larasoul.php
'debug' => [
    'guards' => true,
    'verification_checks' => true,
],
```

This will log detailed information about guard authentication and verification processes.

## Performance Considerations

- Guards perform verification checks on every authentication
- Consider caching verification status for high-traffic applications
- Monitor database queries from verification checks
- Implement efficient indexing on user verification fields

The Larasoul authentication guards provide a robust foundation for implementing verification-aware authentication in your Laravel applications, with flexible configuration options and built-in security best practices.