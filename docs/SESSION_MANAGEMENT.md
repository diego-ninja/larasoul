# Verisoul Session Management

This documentation explains how to automatically capture and use the Verisoul session ID generated in the frontend for backend API calls.

## Configuration

Add these environment variables to your `.env` file:

```env
# Enable session ID capture
VERISOUL_SESSION_CAPTURE_ENABLED=true

# Endpoint to receive session ID from frontend
VERISOUL_SESSION_ENDPOINT=/verisoul/session

# Automatic session ID sending
VERISOUL_AUTO_SEND_SESSION=true
```

## Automatic Operation

When frontend integration is enabled:

1. **JavaScript SDK loads** in the frontend
2. **Verisoul generates a session ID** automatically
3. **JavaScript captures the session ID** and sends it to backend via AJAX
4. **Laravel stores the session ID** in cache and session
5. **Backend can use the session ID** for API calls

## Backend Usage

### Get Current Session ID

```php
use Ninja\Larasoul\Services\VerisoulSessionManager;

$sessionManager = app(VerisoulSessionManager::class);

// For current authenticated user
$sessionId = $sessionManager->getCurrentSessionId();

// For specific user
$sessionId = $sessionManager->getCurrentSessionId($userId);
```

### Use in Controllers

```php
use Ninja\Larasoul\Services\VerificationService;
use Ninja\Larasoul\Services\VerisoulApiClientBuilder;

class VerificationController extends Controller
{
    public function verify(Request $request)
    {
        $builder = app(VerisoulApiClientBuilder::class);
        
        // Check for active session
        if (!$builder->hasActiveSession()) {
            return response()->json([
                'error' => 'No Verisoul session found. Please refresh the page.'
            ], 400);
        }
        
        // Use client with automatic session ID
        $idClient = $builder->createIdCheckClient();
        $response = $idClient->verify($request->all());
        
        // Session ID is already included automatically
        return response()->json($response);
    }
}
```

### Integration with VerificationService

```php
use Ninja\Larasoul\Services\VerificationService;

$verificationService = app(VerificationService::class);

// Create verification using captured session ID
$verification = $verificationService->createVerificationFromCapturedSession(auth()->user());

if (!$verification) {
    // No session ID available
    return redirect()->back()->with('error', 'Session expired. Please refresh.');
}
```

## API Endpoints

### Store Session ID
```
POST /verisoul/session
```

Automatic payload from JavaScript:
```json
{
    "session_id": "vs_session_xxx",
    "project_id": "your_project_id",
    "user_agent": "Mozilla/5.0...",
    "timestamp": "2024-01-01T12:00:00Z"
}
```

### Get Current Session
```
GET /verisoul/session
```
Requires authentication.

### Clear Session
```
DELETE /verisoul/session  
```
Requires authentication.

## JavaScript Events

The frontend fires custom events:

```javascript
// Listen for session ID capture
window.addEventListener('verisoul:session-captured', function(event) {
    console.log('Session ID captured:', event.detail.sessionId);
    
    // Session ID is also available globally
    console.log('Global session ID:', window.verisoulSessionId);
});

// Manually capture session if needed
window.captureVerisoulSession();
```

## Storage

Session IDs are stored in:

1. **Laravel Session** (`verisoul_session_id`)
2. **Cache** (by user ID and by session ID)
3. **Global JS Variable** (`window.verisoulSessionId`)

## Lifetime

- **Cache TTL**: 1 hour by default
- **Laravel Session**: Until session expires
- **Automatic cleanup**: Cache handles TTL automatically

## Verification Middleware

You can create custom middleware to require session ID:

```php
class RequireVerisoulSession
{
    public function handle($request, Closure $next)
    {
        $sessionManager = app(VerisoulSessionManager::class);
        
        if (!$sessionManager->hasSessionId()) {
            return response()->json([
                'error' => 'Verisoul session required'
            ], 400);
        }
        
        return $next($request);
    }
}
```

## Debugging

To check session status:

```php
// Get complete session data
$sessionData = $sessionManager->getSessionData();

// Get session metadata
$builder = app(VerisoulApiClientBuilder::class);
$metadata = $builder->getSessionMetadata();

// In frontend, check console
console.log('Verisoul session:', window.verisoulSessionId);
```

## Common Use Cases

### 1. Identity Verification
```php
public function verifyIdentity(Request $request)
{
    $builder = app(VerisoulApiClientBuilder::class);
    $client = $builder->createIdCheckClient();
    
    // Session ID is included automatically
    return $client->verifyDocument($request->file('document'));
}
```

### 2. Face Verification
```php
public function verifyFace(Request $request) 
{
    $builder = app(VerisoulApiClientBuilder::class);
    $client = $builder->createFaceMatchClient();
    
    // Session ID is included automatically
    return $client->verifyFace($request->file('selfie'));
}
```

### 3. Complete Workflow
```php
public function startVerification()
{
    // 1. Check active session
    $builder = app(VerisoulApiClientBuilder::class);
    
    if (!$builder->hasActiveSession()) {
        return response()->json(['error' => 'Please refresh page'], 400);
    }
    
    // 2. Create verification record
    $verification = app(VerificationService::class)
        ->createVerificationFromCapturedSession(auth()->user());
    
    // 3. Start process
    return response()->json([
        'verification_id' => $verification->id,
        'session_metadata' => $builder->getSessionMetadata()
    ]);
}
```

This integration makes session ID usage completely transparent for the developer.