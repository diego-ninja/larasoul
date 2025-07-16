<?php

namespace Ninja\Larasoul\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Ninja\Larasoul\Services\VerisoulSessionManager;

class SessionController extends Controller
{
    public function __construct(
        private readonly VerisoulSessionManager $sessionManager
    ) {}

    /**
     * Store Verisoul session ID from frontend
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string|min:10|max:255',
            'project_id' => 'nullable|string',
            'user_agent' => 'nullable|string',
            'ip_address' => 'nullable|ip',
        ]);

        $sessionId = $request->input('session_id');
        $userId = Auth::id();

        try {
            // Store the session ID
            $this->sessionManager->storeSessionId(
                sessionId: $sessionId,
                userId: $userId,
                metadata: [
                    'project_id' => $request->input('project_id'),
                    'user_agent' => $request->input('user_agent', $request->userAgent()),
                    'ip_address' => $request->input('ip_address', $request->ip()),
                    'laravel_session_id' => Session::getId(),
                    'created_at' => now()->toISOString(),
                ]
            );

            Session::put(config('larasoul.session.verisoul_session_id'), $sessionId);

            return response()->json([
                'success' => true,
                'message' => 'Verisoul session ID stored successfully',
                'session_id' => $sessionId,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to store session ID',
                'error' => app()->hasDebugModeEnabled() ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
