# Verisoul Frontend Integration

This documentation explains how to integrate the official Verisoul JavaScript SDK into your Laravel application using Blade templates. The integration uses the official Verisoul loader with a Laravel wrapper for enhanced functionality.

## Configuration

Add the following environment variables to your `.env` file:

```env
# Enable frontend integration
VERISOUL_FRONTEND_ENABLED=true

# Your Verisoul project ID
VERISOUL_PROJECT_ID=your_project_id_here

# Enable automatic script injection in all views
VERISOUL_AUTO_INJECT=false

# Use async loading (recommended)
VERISOUL_ASYNC_LOADING=true

# Environment (production or sandbox)
VERISOUL_ENVIRONMENT=sandbox

# Session capture settings
VERISOUL_SESSION_CAPTURE_ENABLED=true
VERISOUL_AUTO_SEND_SESSION=true
VERISOUL_SESSION_ENDPOINT=/verisoul/session
```

## Automatic Blade Template Integration

### Automatic Script Injection

When `VERISOUL_AUTO_INJECT=true`, the Verisoul script will be automatically injected into all views via a View Composer.

#### Including in Your Layout

Add this to your main layout file (e.g., `resources/views/layouts/app.blade.php`):

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your App</title>
    
    {!! $verisoulScript !!}
</head>
<body>
    <!-- Your content -->
</body>
</html>
```

### Manual Integration

For manual control, set `VERISOUL_AUTO_INJECT=false` and use Blade directives:

#### Basic Usage

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your App</title>
    
    @verisoulHead
</head>
<body>
    <!-- Your content -->
</body>
</html>
```

#### Custom Configuration

```html
@verisoul([
    'project_id' => 'custom_project_id',
    'environment' => 'production',
    'async' => false
])
```

#### With Session Data

```html
@verisoulSession([
    'user_id' => auth()->id(),
    'session_id' => session()->getId(),
    'verification_status' => auth()->user()->getVerificationStatus(),
    'is_verified' => auth()->user()->isVerified()
])
```

## Session ID Capture

The Verisoul integration automatically initializes and captures sessions as follows:

### Session Initialization Process

1. **Script Loading**: The Verisoul SDK is loaded from `js.verisoul.ai`
2. **Session Creation**: Verisoul automatically creates a session when the SDK loads with your project ID
3. **User Association**: If a user is logged in, the session is associated with their user ID
4. **Session Ready**: The `onReady` event fires with the `sessionId`
5. **Auto-Capture**: The session ID is automatically sent to your backend endpoint

### When Sessions Are Created

- **Authenticated Users**: When a logged-in user visits any page with the Verisoul script
- **Anonymous Users**: When any visitor (not logged in) visits a page with the Verisoul script  
- **Page Loads**: A new session is created on each page load (unless session persistence is configured)

### Session Capture Flow

```javascript
// 1. SDK loads from js.verisoul.ai with project ID
const script = document.createElement('script');
script.src = 'https://js.verisoul.ai/sandbox/bundle.js';
script.setAttribute('verisoul-project-id', 'your_project_id');

// 2. Wait for Verisoul to be available
while (!window.Verisoul) {
    await new Promise(resolve => setTimeout(resolve, 100));
}

// 3. Get session from Verisoul
const {session_id} = await window.Verisoul.session();

// 4. Automatically send to backend
sendSessionToBackend(session_id);
```

### Automatically Sent Data

- `session_id`: The Verisoul session ID
- `project_id`: The configured project ID
- `user_agent`: Browser user agent
- `timestamp`: Capture timestamp
- `user_id`: Authenticated user ID (if exists)

### Controller to Handle Sessions

Create a controller to handle session capture:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VerisoulSessionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'project_id' => 'nullable|string',
            'user_agent' => 'nullable|string',
            'timestamp' => 'required|date',
            'user_id' => 'nullable|integer',
        ]);
        
        // Store session data (database, cache, etc.)
        \Log::info('Verisoul session captured', $validated);
        
        // Here you can store in database or process the data
        // according to your application needs
        
        return response()->json([
            'success' => true,
            'message' => 'Session captured successfully'
        ]);
    }
}
```

### Register the Route

Add the route in `routes/web.php`:

```php
Route::post('/verisoul/session', [App\Http\Controllers\VerisoulSessionController::class, 'store'])
    ->middleware(['web', 'csrf']);
```

## Script Structure

The generated script includes two parts:

### 1. Official Verisoul Loader
```html
<!-- Official Verisoul script tag -->
<script async src="https://js.verisoul.ai/sandbox/bundle.js" verisoul-project-id="your-project-id"></script>

<!-- Official proxy loader (minified) -->
<script>
!function(e){if(e.Verisoul)return;const r=[],t={},o=new Proxy(t,{get:(e,o)=>o in t?t[o]:(...e)=>new Promise(((t,n)=>r.push([o,e,t,n]))),set:(e,r,o)=>(t[r]=o,!0)});e.Verisoul=o;const n=()=>{Object.keys(t).length&&r.splice(0).forEach((([e,r,o,n])=>{try{Promise.resolve(t[e](...r)).then(o,n)}catch(e){n(e)}}))},c=document.querySelector("script[verisoul-project-id]"),s=()=>r.splice(0).forEach((([,,,e])=>e(new Error("Failed to load Verisoul SDK"))));if(!c)return void s();c.addEventListener("load",n,{once:!0}),c.addEventListener("error",(()=>{clearInterval(i),s()}),{once:!0});const i=setInterval((()=>{Object.keys(t).length&&(clearInterval(i),n())}),40)}(window);
</script>
```

### 2. Laravel Wrapper
```html
<!-- Laravel wrapper for additional functionality -->
<script>
(function() {
    // Session capture, events, and helper functions
    window.verisoulSDK = {
        async getSession() { /* Get fresh session ID */ },
        async reinitialize() { /* Reinitialize for logout */ },
        isReady() { /* Check if ready */ },
        async captureSession() { /* Capture and send to backend */ }
    };
})();
</script>
```

## JavaScript SDK

Once the script is loaded, you can access the Verisoul SDK through the global object `window.verisoulSDK`:

### Check if Ready

```javascript
if (window.verisoulSDK && window.verisoulSDK.isReady()) {
    console.log('Verisoul is ready');
    
    // Get a fresh session ID (generates new ID each time)
    const sessionId = await window.verisoulSDK.getSession();
    console.log('Session ID:', sessionId);
}
```

### Get Session ID

**Important:** Each call to `getSession()` generates a new session ID. This follows Verisoul's best practices:

```javascript
// Get a fresh session ID right before making a server request
const sessionId = await window.verisoulSDK.getSession();

// Send to your verification endpoint
fetch('/verify-user', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    },
    body: JSON.stringify({
        session_id: sessionId,
        // other verification data
    })
});
```

### Reinitialize for Logout

When users logout or switch accounts, reinitialize the session:

```javascript
// On logout
async function logout() {
    try {
        await window.verisoulSDK.reinitialize();
        console.log('Verisoul session reinitialized');
    } catch (error) {
        console.error('Failed to reinitialize Verisoul:', error);
    }
}
```

### Capture Session Manually

If you need to send the session ID to your backend manually:

```javascript
// This gets a fresh session ID and sends it to your backend
try {
    const sessionId = await window.verisoulSDK.captureSession();
    console.log('Session captured:', sessionId);
} catch (error) {
    console.error('Failed to capture session:', error);
}
```

### Direct Verisoul API Usage

You can also use the official Verisoul API directly:

```javascript
// Direct access to Verisoul SDK
if (window.Verisoul) {
    // Get session (official API)
    const {session_id} = await window.Verisoul.session();
    
    // Reinitialize (official API)
    await window.Verisoul.reinitialize();
}
```

## SDK Events

The SDK emits custom events that you can listen to:

### Available Events

- `verisoul:ready` - Fired when the Verisoul SDK is ready
- `verisoul:session-captured` - Fired when the session is sent to the backend
- `verisoul:verification-complete` - Fired when a verification is completed
- `verisoul:verification-error` - Fired when there's an error in verification
- `verisoul:error` - Fired on general errors

### Listening to Events

```javascript
// Listen for when Verisoul is ready
window.addEventListener('verisoul:ready', function(event) {
    console.log('Verisoul ready with session ID:', event.detail.sessionId);
    
    // Enable verification buttons
    document.querySelectorAll('.verification-button').forEach(btn => {
        btn.disabled = false;
    });
});

// Listen for when verification completes
window.addEventListener('verisoul:verification-complete', function(event) {
    console.log('Verification completed:', event.detail);
    
    const result = event.detail;
    if (result.decision === 'Real') {
        showSuccessMessage('Verification successful');
    } else {
        showErrorMessage('Verification failed: ' + result.decision);
    }
});

// Listen for errors
window.addEventListener('verisoul:error', function(event) {
    console.error('Verisoul error:', event.detail.error);
    showErrorMessage('Verisoul error: ' + event.detail.error);
});

// Listen for when session is captured
window.addEventListener('verisoul:session-captured', function(event) {
    console.log('Session captured:', event.detail.sessionId);
});
```

## Route Exclusions

By default, the script won't be injected on:
- API routes (`api/*`)
- Admin routes (`admin/*`)
- Debug bar routes (`_debugbar/*`)

You can customize exclusions in `config/larasoul.php`:

```php
'frontend' => [
    'excluded_routes' => [
        'api/*',
        'admin/*',
        'webhook/*',
        'health-check',
    ],
],
```

## Available Blade Directives

### @verisoulHead
Generates the optimal script for head section with async loading.

### @verisoul($options)
Generates script with custom options.

Parameters:
- `project_id`: Override project ID
- `environment`: Override environment (production/sandbox)
- `async`: Enable/disable async loading

### @verisoulSession($sessionData)
Generates script with session initialization.

Safe session data keys:
- `user_id`
- `session_id`
- `verification_status`
- `risk_level`
- `is_verified`

## Programmatic Usage

You can also use the script generator service directly:

```php
use Ninja\Larasoul\Services\VerisoulScriptGenerator;

$generator = app(VerisoulScriptGenerator::class);

// Basic script
$script = $generator->generateForHead();

// Custom options
$script = $generator->generate([
    'project_id' => 'custom_id',
    'async' => false
]);

// With session data
$script = $generator->generateSessionScript([
    'user_id' => auth()->id()
]);
```

## Configuration Reference

Key configuration options in `config/larasoul.php`:

```php
'frontend' => [
    'enabled' => env('VERISOUL_FRONTEND_ENABLED', false),
    'project_id' => env('VERISOUL_PROJECT_ID'),
    'async_loading' => env('VERISOUL_ASYNC_LOADING', true),
    'auto_inject' => env('VERISOUL_AUTO_INJECT', false),
    'session_capture' => [
        'enabled' => env('VERISOUL_SESSION_CAPTURE_ENABLED', true),
        'endpoint' => env('VERISOUL_SESSION_ENDPOINT', '/verisoul/session'),
        'auto_send' => env('VERISOUL_AUTO_SEND_SESSION', true),
    ],
    'excluded_routes' => [
        'api/*',
        'admin/*',
        '_debugbar/*',
    ],
],
```

## Integration with Other Frameworks

For applications using **Vue.js, React, Alpine.js, Livewire, or any other JavaScript framework**, you can integrate Verisoul by:

### 1. Using the Global SDK

The `window.verisoulSDK` object is available once Verisoul is loaded:

```javascript
// Vue.js example
export default {
    data() {
        return {
            verisoulReady: false,
            sessionId: null
        }
    },
    mounted() {
        this.initVerisoul();
    },
    methods: {
        initVerisoul() {
            window.addEventListener('verisoul:ready', (event) => {
                this.verisoulReady = true;
                this.sessionId = event.detail.sessionId;
            });
        },
        async startVerification() {
            if (!window.verisoulSDK?.isReady()) return;
            
            try {
                const result = await window.verisoulSDK.startVerification('face');
                // Handle result
            } catch (error) {
                // Handle error
            }
        }
    }
}
```

### 2. React Hook Example

```javascript
import { useState, useEffect } from 'react';

function useVerisoul() {
    const [isReady, setIsReady] = useState(false);
    const [sessionId, setSessionId] = useState(null);
    
    useEffect(() => {
        const handleReady = (event) => {
            setIsReady(true);
            setSessionId(event.detail.sessionId);
        };
        
        window.addEventListener('verisoul:ready', handleReady);
        return () => window.removeEventListener('verisoul:ready', handleReady);
    }, []);
    
    const startVerification = async (type) => {
        if (!window.verisoulSDK?.isReady()) {
            throw new Error('Verisoul not ready');
        }
        return await window.verisoulSDK.startVerification(type);
    };
    
    return { isReady, sessionId, startVerification };
}
```

### 3. Alpine.js Integration

```html
<div x-data="verisoulComponent()">
    <div x-show="!ready">Loading Verisoul...</div>
    <div x-show="ready">
        <p>Session ID: <span x-text="sessionId"></span></p>
        <button @click="startVerification('face')" :disabled="!ready">
            Start Face Verification
        </button>
    </div>
</div>

<script>
function verisoulComponent() {
    return {
        ready: false,
        sessionId: null,
        
        init() {
            window.addEventListener('verisoul:ready', (event) => {
                this.ready = true;
                this.sessionId = event.detail.sessionId;
            });
        },
        
        async startVerification(type) {
            if (!window.verisoulSDK?.isReady()) return;
            
            try {
                const result = await window.verisoulSDK.startVerification(type);
                console.log('Verification result:', result);
            } catch (error) {
                console.error('Verification error:', error);
            }
        }
    }
}
</script>
```

### 4. Livewire Integration

```php
class VerificationComponent extends Component
{
    public $verisoulReady = false;
    public $sessionId = null;

    protected $listeners = ['verisoulReady', 'verisoulVerificationComplete'];

    public function verisoulReady($sessionId)
    {
        $this->verisoulReady = true;
        $this->sessionId = $sessionId;
    }

    public function startVerification()
    {
        $this->dispatchBrowserEvent('start-verisoul-verification', [
            'type' => 'face'
        ]);
    }

    public function render()
    {
        return view('livewire.verification-component');
    }
}
```

```html
<!-- livewire.verification-component -->
<div>
    @if(!$verisoulReady)
        <div>Loading Verisoul...</div>
    @else
        <p>Session ID: {{ $sessionId }}</p>
        <button wire:click="startVerification">Start Verification</button>
    @endif
</div>

<script>
document.addEventListener('livewire:load', function () {
    // Listen for Verisoul ready
    window.addEventListener('verisoul:ready', function(event) {
        Livewire.emit('verisoulReady', event.detail.sessionId);
    });
    
    // Listen for Livewire verification start
    window.addEventListener('start-verisoul-verification', function(event) {
        if (window.verisoulSDK?.isReady()) {
            window.verisoulSDK.startVerification(event.detail.type);
        }
    });
});
</script>
```

## Security Considerations

- Only non-sensitive user data is included in session scripts
- Scripts are only injected for HTML responses (not AJAX/JSON)
- All user data is properly escaped before output
- CSRF tokens are automatically included in session submissions
- API keys are never exposed to the frontend

## Troubleshooting

### Script Not Loading
1. Check that `VERISOUL_FRONTEND_ENABLED=true`
2. Verify `VERISOUL_PROJECT_ID` is set
3. Ensure route is not in excluded list
4. Check browser console for JavaScript errors

### Ad Blocker / Privacy Extension Issues
The most common issue is ad blockers or privacy extensions blocking the Verisoul script.

**Error:** `net::ERR_BLOCKED_BY_CLIENT` when loading `https://js.verisoul.ai/sandbox/bundle.js`

**Solutions:**
1. **Whitelist the domain:** Add `js.verisoul.ai` to your ad blocker's whitelist
2. **Disable blocking extensions temporarily** to test if they're causing the issue
3. **Check browser settings** for any content blocking features
4. **Test in incognito mode** (with extensions disabled) to confirm the issue

**Common ad blockers and how to whitelist:**
- **uBlock Origin:** Click the extension icon → Click the power button to disable on the site
- **AdBlock Plus:** Click the extension icon → "Don't run on this domain"
- **Privacy Badger:** Click the extension icon → Set js.verisoul.ai to "Allow"
- **Ghostery:** Click the extension icon → Whitelist the site

**For developers:**
You can detect if the script is being blocked and show a user-friendly message:

```javascript
window.addEventListener('verisoul:error', function(event) {
    if (event.detail.type === 'script_load_failed') {
        // Show user-friendly message about ad blockers
        const message = `
            Verisoul verification is blocked by an ad blocker or privacy extension.
            Please whitelist this site or disable blocking extensions to continue.
        `;
        
        // Display in your UI
        document.getElementById('verification-error').innerHTML = message;
        document.getElementById('verification-error').style.display = 'block';
    }
});
```

### Auto-Injection Not Working
1. Verify `VERISOUL_AUTO_INJECT=true`
2. Check that you're including `{!! $verisoulScript !!}` in your layout
3. Ensure you're accessing HTML pages (not API endpoints)

### Session Capture Issues
1. Verify the session endpoint route is registered
2. Check that CSRF middleware is properly configured
3. Ensure the backend controller is handling requests correctly
4. Check Laravel logs for session submission errors

### Environment Issues
- Use `sandbox` for testing
- Use `production` for live applications
- Ensure environment matches your Verisoul project configuration
- Different project IDs may be required for different environments