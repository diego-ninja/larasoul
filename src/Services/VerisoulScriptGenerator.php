<?php

namespace Ninja\Larasoul\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final readonly class VerisoulScriptGenerator
{
    public function __construct(private Request $request) {}

    /**
     * Generate Verisoul script for automatic injection
     */
    public function generateAutoInjectScript(): string
    {
        if (! $this->isEnabled()) {
            return '<!-- Verisoul: Disabled -->';
        }

        return $this->buildScript();
    }

    /**
     * Generate script for head section
     */
    public function generateForHead(): string
    {
        if (! $this->isEnabled()) {
            return '<!-- Verisoul: Disabled -->';
        }

        return $this->buildScript();
    }

    /**
     * Generate script with custom options
     */
    public function generate(array $options = []): string
    {
        if (! $this->isEnabled()) {
            return '<!-- Verisoul: Disabled -->';
        }

        return $this->buildScript($options);
    }

    /**
     * Generate script with session data
     */
    public function generateSessionScript(array $sessionData = []): string
    {
        if (! $this->isEnabled()) {
            return '<!-- Verisoul: Disabled -->';
        }

        return $this->buildScript(['sessionData' => $sessionData]);
    }

    /**
     * Check if Verisoul frontend is enabled
     */
    private function isEnabled(): bool
    {
        return config('larasoul.verisoul.frontend.enabled', false) &&
               ! empty(config('larasoul.verisoul.frontend.project_id'));
    }

    /**
     * Build the Verisoul script using official loader
     */
    private function buildScript(array $options = []): string
    {
        $config = $this->getConfig($options);
        $configJson = json_encode($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        return $this->getOfficialLoader($config).$this->getWrapperScript($config, $configJson);
    }

    /**
     * Get the official Verisoul loader script
     */
    private function getOfficialLoader(array $config): string
    {
        $asyncAttr = $config['async'] ? 'async' : '';

        return "<script {$asyncAttr} src=\"{$config['scriptUrl']}\" verisoul-project-id=\"{$config['projectId']}\"></script>
<script>!function(e){if(e.Verisoul)return;const r=[],t={},o=new Proxy(t,{get:(e,o)=>o in t?t[o]:(...e)=>new Promise(((t,n)=>r.push([o,e,t,n]))),set:(e,r,o)=>(t[r]=o,!0)});e.Verisoul=o;const n=()=>{Object.keys(t).length&&r.splice(0).forEach((([e,r,o,n])=>{try{Promise.resolve(t[e](...r)).then(o,n)}catch(e){n(e)}}))},c=document.querySelector(\"script[verisoul-project-id]\"),s=()=>r.splice(0).forEach((([,,,e])=>e(new Error(\"Failed to load Verisoul SDK\"))));if(!c)return void s();c.addEventListener(\"load\",n,{once:!0}),c.addEventListener(\"error\",(()=>{clearInterval(i),s()}),{once:!0});const i=setInterval((()=>{Object.keys(t).length&&(clearInterval(i),n())}),40)}(window);</script>";
    }

    /**
     * Get the wrapper script for Laravel integration
     */
    private function getWrapperScript(array $config, string $configJson): string
    {
        return "<script>
(function() {
    const config = {$configJson};
    let verisoulReady = false;
    let sessionCache = null;
    let sessionCacheTime = 0;
    const SESSION_CACHE_DURATION = 30000; // 30 seconds cache
    
    console.log('[Verisoul] Initializing Laravel wrapper with config:', {
        projectId: config.projectId,
        environment: config.environment,
        autoSend: config.autoSend,
        scriptUrl: config.scriptUrl
    });
    
    // Send session to backend
    function sendSessionToBackend(sessionId) {
        console.log('[Verisoul] Sending session to backend:', {
            sessionId: sessionId,
            endpoint: config.sessionEndpoint,
            projectId: config.projectId,
            userId: config.userId
        });
        
        fetch(config.sessionEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                session_id: sessionId,
                project_id: config.projectId,
                user_agent: navigator.userAgent,
                timestamp: new Date().toISOString(),
                user_id: config.userId
            })
        })
        .then(response => {
            console.log('[Verisoul] Backend response status:', response.status);
            if (response.ok) {
                console.log('[Verisoul] Session successfully sent to backend');
                window.dispatchEvent(new CustomEvent('verisoul:session-captured', {
                    detail: { sessionId: sessionId }
                }));
            } else {
                console.warn('[Verisoul] Backend rejected session:', response.status, response.statusText);
            }
            return response.text();
        })
        .then(responseText => {
            console.log('[Verisoul] Backend response body:', responseText);
        })
        .catch(error => {
            console.warn('[Verisoul] Failed to send session to backend:', error);
            window.dispatchEvent(new CustomEvent('verisoul:error', {
                detail: { error: error.message, type: 'session_capture_failed' }
            }));
        });
    }
    
    // Wait for Verisoul to be ready
    function waitForVerisoul() {
        return new Promise((resolve, reject) => {
            let attempts = 0;
            const maxAttempts = 100; // 10 seconds max
            
            console.log('[Verisoul] Waiting for Verisoul SDK to be ready...');
            
            const check = () => {
                console.log('[Verisoul] Check attempt', attempts + 1, 'of', maxAttempts);
                console.log('[Verisoul] window.Verisoul exists:', !!window.Verisoul);
                console.log('[Verisoul] window.Verisoul.session exists:', !!(window.Verisoul && window.Verisoul.session));
                
                if (window.Verisoul && typeof window.Verisoul.session === 'function') {
                    console.log('[Verisoul] SDK is ready!');
                    verisoulReady = true;
                    resolve();
                    return;
                }
                
                attempts++;
                if (attempts >= maxAttempts) {
                    console.error('[Verisoul] SDK not ready after 10 seconds, giving up');
                    reject(new Error('Verisoul SDK not ready after 10 seconds'));
                    return;
                }
                
                setTimeout(check, 100);
            };
            
            check();
        });
    }
    
    // Initialize when Verisoul is ready
    waitForVerisoul().then(() => {
        console.log('[Verisoul] SDK initialization complete');
        
        // Test initial session call
        console.log('[Verisoul] Testing initial session call...');
        window.Verisoul.session().then(result => {
            console.log('[Verisoul] Initial session call result:', result);
        }).catch(error => {
            console.error('[Verisoul] Initial session call failed:', error);
        });
        
        // Dispatch ready event
        window.dispatchEvent(new CustomEvent('verisoul:ready', {
            detail: { ready: true }
        }));
        console.log('[Verisoul] Ready event dispatched');
        
        // Get initial session if auto-send is enabled
        if (config.autoSend) {
            console.log('[Verisoul] Auto-send enabled, getting initial session...');
            window.Verisoul.session().then(({session_id}) => {
                console.log('[Verisoul] Auto-send session received:', session_id);
                sendSessionToBackend(session_id);
            }).catch(error => {
                console.warn('[Verisoul] Auto-send failed to get initial session:', error);
            });
        } else {
            console.log('[Verisoul] Auto-send disabled');
        }
    }).catch(error => {
        console.error('[Verisoul] Failed to initialize Verisoul:', error);
        window.dispatchEvent(new CustomEvent('verisoul:error', {
            detail: { 
                error: error.message,
                type: 'initialization_failed'
            }
        }));
    });
    
    // Laravel wrapper SDK
    window.verisoulSDK = {
        // Get a fresh session ID (expires after 24 hours)
        async getSession(forceNew = false) {
            console.log('[Verisoul] getSession() called with forceNew:', forceNew);
            
            if (!verisoulReady) {
                console.log('[Verisoul] SDK not ready, waiting...');
                await waitForVerisoul();
            }
            
            // If forcing new session, reinitialize first
            if (forceNew) {
                console.log('[Verisoul] Forcing new session, reinitializing...');
                try {
                    await window.Verisoul.reinitialize();
                    console.log('[Verisoul] Reinitialize completed for new session');
                } catch (error) {
                    console.warn('[Verisoul] Failed to reinitialize for new session:', error);
                }
            }
            
            try {
                console.log('[Verisoul] Calling window.Verisoul.session()...');
                const result = await window.Verisoul.session();
                console.log('[Verisoul] Session result:', result);
                
                const sessionId = result.session_id;
                console.log('[Verisoul] Extracted session_id:', sessionId);
                
                if (!sessionId) {
                    throw new Error('No session_id in response');
                }
                
                // Check if this is the same session ID as before
                if (sessionCache === sessionId && !forceNew) {
                    console.warn('[Verisoul] Same session ID returned, this might indicate caching:', sessionId);
                    console.warn('[Verisoul] Consider using getSession(true) to force a new session');
                }
                
                sessionCache = sessionId;
                sessionCacheTime = Date.now();
                
                return sessionId;
            } catch (error) {
                console.error('[Verisoul] Failed to get session:', error);
                throw error;
            }
        },
        
        // Reinitialize session (for logout/account switching)
        async reinitialize() {
            console.log('[Verisoul] reinitialize() called');
            
            if (!verisoulReady) {
                console.log('[Verisoul] SDK not ready, waiting...');
                await waitForVerisoul();
            }
            
            try {
                console.log('[Verisoul] Calling window.Verisoul.reinitialize()...');
                await window.Verisoul.reinitialize();
                console.log('[Verisoul] Reinitialize successful');
                
                // Clear session cache
                sessionCache = null;
                sessionCacheTime = 0;
                console.log('[Verisoul] Session cache cleared');
                
                window.dispatchEvent(new CustomEvent('verisoul:reinitialized', {
                    detail: { success: true }
                }));
            } catch (error) {
                console.error('[Verisoul] Failed to reinitialize:', error);
                window.dispatchEvent(new CustomEvent('verisoul:error', {
                    detail: { error: error.message, type: 'reinitialize_failed' }
                }));
                throw error;
            }
        },
        
        // Check if Verisoul is ready
        isReady: function() {
            const ready = verisoulReady;
            console.log('[Verisoul] isReady() called, returning:', ready);
            return ready;
        },
        
        // Helper to get session and send to backend
        async captureSession() {
            console.log('[Verisoul] captureSession() called');
            try {
                const sessionId = await this.getSession();
                console.log('[Verisoul] Got session for capture:', sessionId);
                sendSessionToBackend(sessionId);
                return sessionId;
            } catch (error) {
                console.error('[Verisoul] Failed to capture session:', error);
                throw error;
            }
        },
        
        // Debug helper to check SDK state
        debug: function() {
            return {
                verisoulReady: verisoulReady,
                hasVerisoul: !!window.Verisoul,
                hasSession: !!(window.Verisoul && window.Verisoul.session),
                config: config,
                sessionCache: sessionCache,
                sessionCacheTime: sessionCacheTime
            };
        }
    };
    
    // Debug info
    console.log('[Verisoul] Laravel wrapper initialized');
    console.log('[Verisoul] Use window.verisoulSDK.debug() to check state');
})();
</script>";
    }

    /**
     * Get configuration for the script
     */
    private function getConfig(array $options = []): array
    {
        $environment = config('larasoul.verisoul.environment', 'sandbox');
        $envPath = $environment === 'production' ? 'prod' : 'sandbox';

        return array_merge([
            'projectId' => config('larasoul.verisoul.frontend.project_id'),
            'environment' => $environment,
            'scriptUrl' => "https://js.verisoul.ai/{$envPath}/bundle.js",
            'async' => config('larasoul.verisoul.frontend.async_loading', true),
            'autoSend' => config('larasoul.verisoul.frontend.session_capture.auto_send', true),
            'sessionEndpoint' => config('larasoul.verisoul.frontend.session_capture.endpoint', '/verisoul/session'),
            'csrfToken' => csrf_token(),
            'userId' => Auth::id(),
        ], $options);
    }
}
