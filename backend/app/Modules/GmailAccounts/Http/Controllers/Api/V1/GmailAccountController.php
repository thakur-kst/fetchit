<?php

namespace Modules\GmailAccounts\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\GmailAccounts\Http\Resources\GmailAccountResource;
use Modules\GmailAccounts\Models\GmailAccount;
use Modules\GmailAccounts\Services\GmailOAuthService;
use Modules\GmailAccounts\Services\GmailTokenService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Dedoc\Scramble\Attributes\Response;
/**
 * Gmail Account Controller
 *
 * Handles Gmail account linking, OAuth flow, and token management.
 *
 * @tags Gmail
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
     * Returns the URL to redirect the user to for Gmail OAuth consent. Requires Bearer token.
     *
     * @operationId gmailGetAuthUrl
     * @tags Gmail
     * @response 200 {"success": true, "data": {"url": "https://accounts.google.com/o/oauth2/v2/auth?client_id=123456789.apps.googleusercontent.com&redirect_uri=https://example.com/callback&response_type=code&scope=https://www.googleapis.com/auth/gmail.readonly&access_type=offline&state=abc123"}}
     * @response 500 {"success": false, "error": "Failed to generate authorization URL"}
     */
    #[Response(200, 'Gmail authorization URL generated successfully', type: 'array{success: bool, data: array{authUrl: string, state: string}}')]
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
     * Returns all Gmail accounts linked to the authenticated user.
     *
     * @operationId gmailAccountsIndex
     * @tags Gmail
     * @response 200 {"success": true, "data": [{"id": "660e8400-e29b-41d4-a716-446655440001", "email": "user@gmail.com", "displayName": "John Doe", "pictureUrl": "https://lh3.googleusercontent.com/a/default-user", "isActive": true, "tokenType": "Bearer", "scope": "https://www.googleapis.com/auth/gmail.readonly", "tokenExpiresAt": "2024-01-16T12:00:00Z", "lastSyncedAt": "2024-01-15T10:00:00Z", "createdAt": "2024-01-01T08:00:00Z"}]}
     * @response 500 {"success": false, "error": "Failed to list Gmail accounts"}
     */
    #[Response(200, 'Gmail accounts listed successfully', type: 'array{success: bool, data: array{id: string, email: string, displayName: string, pictureUrl: string, isActive: bool, tokenType: string, scope: string, tokenExpiresAt: string, lastSyncedAt: string, createdAt: string}}')]
    public function index(Request $request): JsonResponse
    {
        try {
            $accounts = GmailAccount::where('user_id', $request->user()->uuid)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => GmailAccountResource::collection($accounts),
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
     * Exchanges OAuth authorization code for tokens and links the Gmail account. Body: code, scope, email, displayName (optional), pictureUrl (optional).
     *
     * @operationId gmailAccountsStore
     * @tags Gmail
     * @response 200 {"success": true, "message": "Gmail account linked successfully", "data": {"id": "660e8400-e29b-41d4-a716-446655440001", "email": "user@gmail.com", "displayName": "John Doe", "isActive": true, "createdAt": "2024-01-01T08:00:00Z"}}
     * @response 201 {"success": true, "message": "Gmail account linked successfully", "data": {"id": "660e8400-e29b-41d4-a716-446655440001", "email": "user@gmail.com", "displayName": "John Doe", "isActive": true, "createdAt": "2024-01-01T08:00:00Z"}}
     * @response 400 {"success": false, "error": "Failed to exchange authorization code"}
     * @response 500 {"success": false, "error": "Failed to link Gmail account"}
     */
    #[Response(200, 'Gmail account linked successfully', type: 'array{success: bool, message: string, data: array{id: string, email: string, displayName: string, isActive: bool, createdAt: string}}')]
    #[Response(201, 'Gmail account linked successfully', type: 'array{success: bool, message: string, data: array{id: string, email: string, displayName: string, isActive: bool, createdAt: string}}')]
    #[Response(400, 'Failed to exchange authorization code', type: 'array{success: bool, error: string}')]
    #[Response(500, 'Failed to link Gmail account', type: 'array{success: bool, error: string}')]
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
                        'data' => new GmailAccountResource($existingAccount),
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
                    'data' => new GmailAccountResource($account),
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
     * Unlinks and deletes the Gmail account for the authenticated user.
     *
     * @operationId gmailAccountsDestroy
     * @tags Gmail
     * @response 200 {"success": true, "message": "Gmail account unlinked successfully", "data": {"id": "660e8400-e29b-41d4-a716-446655440001"}}
     * @response 404 {"success": false, "error": "Gmail account not found"}
     * @response 500 {"success": false, "error": "Failed to unlink Gmail account"}
     */
    #[Response(200, 'Gmail account unlinked successfully', type: 'array{success: bool, message: string, data: array{id: string}}')]
    #[Response(404, 'Gmail account not found', type: 'array{success: bool, error: string}')]
    #[Response(500, 'Failed to unlink Gmail account', type: 'array{success: bool, error: string}')]
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
     * Ensures the Gmail account has a valid access token, refreshing if needed. Returns current token info.
     *
     * @operationId gmailAccountsRefresh
     * @tags Gmail
     * @response 200 {"success": true, "data": {"accessToken": "ya29.a0AfH6SMC...", "expiresIn": 3600, "tokenType": "Bearer", "expiresAt": "2024-01-16T12:00:00Z"}}
     * @response 400 {"success": false, "error": "Failed to refresh token"}
     * @response 404 {"success": false, "error": "Gmail account not found"}
     * @response 500 {"success": false, "error": "Failed to refresh token"}
     */
    #[Response(200, 'Gmail token refreshed successfully', type: 'array{success: bool, data: array{accessToken: string, expiresIn: int, tokenType: string, expiresAt: string}}')]
    #[Response(400, 'Failed to refresh token', type: 'array{success: bool, error: string}')]
    #[Response(404, 'Gmail account not found', type: 'array{success: bool, error: string}')]
    #[Response(500, 'Failed to refresh token', type: 'array{success: bool, error: string}')]
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
