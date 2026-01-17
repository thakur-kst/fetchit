<?php

namespace Modules\GmailAccounts\Services;

use Modules\GmailAccounts\Models\GmailAccount;
use Illuminate\Support\Facades\Log;

/**
 * Gmail Token Service
 * 
 * Handles Gmail token refresh and validation.
 */
class GmailTokenService
{
    public function __construct(
        private GmailOAuthService $oauthService
    ) {
    }

    /**
     * Ensure access token is valid, refresh if needed
     * 
     * @param GmailAccount $account
     * @return bool True if token is valid
     */
    public function ensureValidToken(GmailAccount $account): bool
    {
        if (!$account->needsTokenRefresh()) {
            return true;
        }

        if (!$account->refresh_token) {
            Log::warning('No refresh token available for Gmail account', [
                'account_id' => $account->id,
            ]);
            return false;
        }

        $newToken = $this->oauthService->refreshAccessToken($account);

        if (!$newToken) {
            return false;
        }

        $account->update([
            'access_token' => $newToken['access_token'],
            'token_expires_at' => isset($newToken['expires_in']) 
                ? now()->addSeconds($newToken['expires_in']) 
                : now()->addHour(),
        ]);

        if (isset($newToken['refresh_token'])) {
            $account->update(['refresh_token' => $newToken['refresh_token']]);
        }

        return true;
    }
}
