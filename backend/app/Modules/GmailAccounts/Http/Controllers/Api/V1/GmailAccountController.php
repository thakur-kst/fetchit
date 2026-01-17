<?php

namespace Modules\GmailAccounts\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\GmailAccounts\Models\GmailAccount;
use Modules\GmailAccounts\Services\GmailOAuthService;
use Modules\GmailAccounts\Services\GmailTokenService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Gmail Account Controller
 * 
 * Handles Gmail account linking and management.
 */
class GmailAccountController extends Controller
{
    public function __construct(
        private GmailOAuthService $oauthService,
        private GmailTokenService $tokenService
    ) {
    }

    /**
     * Get Gmail OAuth authorization URL
     * 
     * GET /api/gmail/auth/authorize
     */
    public function getAuthorizationUrl(Request $request): JsonResponse
    {
        try {
            $authData = $this->oauthService->getAuthorizationUrl();

            return response()->json([
                'success' => true,
                'data' => $authData,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting Gmail authorization URL', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate authorization URL',
            ], 500);
        }
    }

    /**
     * List user's Gmail accounts
     * 
     * GET /api/gmail/accounts
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $accounts = GmailAccount::where('user_id', $request->user()->uuid)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $accounts->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'email' => $account->email,
                        'displayName' => $account->display_name,
                        'pictureUrl' => $account->picture_url,
                        'isActive' => $account->is_active,
                        'tokenType' => $account->token_type,
                        'scope' => $account->scope,
                        'tokenExpiresAt' => $account->token_expires_at?->toIso8601String(),
                        'lastSyncedAt' => $account->last_synced_at?->toIso8601String(),
                        'createdAt' => $account->created_at->toIso8601String(),
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            Log::error('Error listing Gmail accounts', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to list Gmail accounts',
            ], 500);
        }
    }

    /**
     * Link Gmail account
     * 
     * POST /api/gmail/accounts
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'scope' => 'required|string',
            'email' => 'required|email',
            'displayName' => 'nullable|string|max:255',
            'pictureUrl' => 'nullable|url',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                // Exchange code for tokens
                $tokenData = $this->oauthService->exchangeCodeForTokens($request->code);

                if (!$tokenData) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Failed to exchange authorization code',
                    ], 400);
                }

                // Check if account already exists
                $existingAccount = GmailAccount::where('user_id', $request->user()->uuid)
                    ->where('email', $request->email)
                    ->first();

                if ($existingAccount) {
                    // Update existing account
                    $existingAccount->update([
                        'access_token' => $tokenData['access_token'],
                        'refresh_token' => $tokenData['refresh_token'] ?? $existingAccount->refresh_token,
                        'token_type' => $tokenData['token_type'] ?? 'Bearer',
                        'scope' => $request->scope,
                        'token_expires_at' => isset($tokenData['expires_in']) 
                            ? now()->addSeconds($tokenData['expires_in']) 
                            : now()->addHour(),
                        'display_name' => $request->displayName,
                        'picture_url' => $request->pictureUrl,
                        'is_active' => true,
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Gmail account linked successfully',
                        'data' => [
                            'id' => $existingAccount->id,
                            'email' => $existingAccount->email,
                            'displayName' => $existingAccount->display_name,
                            'isActive' => $existingAccount->is_active,
                            'createdAt' => $existingAccount->created_at->toIso8601String(),
                        ],
                    ], 200);
                }

                // Create new account
                $account = GmailAccount::create([
                    'user_id' => $request->user()->uuid,
                    'email' => $request->email,
                    'display_name' => $request->displayName,
                    'picture_url' => $request->pictureUrl,
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? null,
                    'token_type' => $tokenData['token_type'] ?? 'Bearer',
                    'scope' => $request->scope,
                    'token_expires_at' => isset($tokenData['expires_in']) 
                        ? now()->addSeconds($tokenData['expires_in']) 
                        : now()->addHour(),
                    'is_active' => true,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Gmail account linked successfully',
                    'data' => [
                        'id' => $account->id,
                        'email' => $account->email,
                        'displayName' => $account->display_name,
                        'isActive' => $account->is_active,
                        'createdAt' => $account->created_at->toIso8601String(),
                    ],
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error('Error linking Gmail account', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to link Gmail account',
            ], 500);
        }
    }

    /**
     * Delete Gmail account
     * 
     * DELETE /api/gmail/accounts/{accountId}
     */
    public function destroy(Request $request, string $accountId): JsonResponse
    {
        try {
            $account = GmailAccount::where('id', $accountId)
                ->where('user_id', $request->user()->uuid)
                ->firstOrFail();

            $account->delete();

            return response()->json([
                'success' => true,
                'message' => 'Gmail account unlinked successfully',
                'data' => [
                    'id' => $accountId,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Gmail account not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting Gmail account', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to unlink Gmail account',
            ], 500);
        }
    }

    /**
     * Refresh Gmail access token
     * 
     * POST /api/gmail/accounts/{accountId}/refresh
     */
    public function refreshToken(Request $request, string $accountId): JsonResponse
    {
        try {
            $account = GmailAccount::where('id', $accountId)
                ->where('user_id', $request->user()->uuid)
                ->firstOrFail();

            $valid = $this->tokenService->ensureValidToken($account);

            if (!$valid) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to refresh token',
                ], 400);
            }

            $account->refresh();

            return response()->json([
                'success' => true,
                'data' => [
                    'accessToken' => $account->access_token,
                    'expiresIn' => $account->token_expires_at?->diffInSeconds(now()) ?? 3600,
                    'tokenType' => $account->token_type,
                    'expiresAt' => $account->token_expires_at?->toIso8601String(),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Gmail account not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error refreshing Gmail token', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to refresh token',
            ], 500);
        }
    }
}
