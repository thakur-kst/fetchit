<?php

namespace Modules\GmailAccounts\Services;

use Google_Client;
use Google_Service_Gmail;
use Modules\GmailAccounts\Models\GmailAccount;
use Modules\DBCore\Models\Core\User;
use Illuminate\Support\Facades\Log;

/**
 * Gmail OAuth Service
 * 
 * Handles Gmail OAuth flow and token management.
 */
class GmailOAuthService
{
    private Google_Client $client;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect'));
        $this->client->addScope('https://www.googleapis.com/auth/gmail.readonly');
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
    }

    /**
     * Get authorization URL for Gmail OAuth
     * 
     * @return array ['authUrl' => string, 'state' => string]
     */
    public function getAuthorizationUrl(): array
    {
        $state = bin2hex(random_bytes(16));
        $this->client->setState($state);
        
        return [
            'authUrl' => $this->client->createAuthUrl(),
            'state' => $state,
        ];
    }

    /**
     * Exchange authorization code for tokens
     * 
     * @param string $code Authorization code
     * @return array|null Token data or null on failure
     */
    public function exchangeCodeForTokens(string $code): ?array
    {
        try {
            $accessToken = $this->client->fetchAccessTokenWithAuthCode($code);
            
            if (isset($accessToken['error'])) {
                Log::error('Error exchanging code for tokens', ['error' => $accessToken['error']]);
                return null;
            }

            return $accessToken;
        } catch (\Exception $e) {
            Log::error('Exception exchanging code for tokens', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Refresh access token
     * 
     * @param GmailAccount $account
     * @return array|null New token data or null on failure
     */
    public function refreshAccessToken(GmailAccount $account): ?array
    {
        try {
            $this->client->setAccessToken([
                'access_token' => $account->access_token,
                'refresh_token' => $account->refresh_token,
                'expires_in' => $account->token_expires_at ? $account->token_expires_at->diffInSeconds(now()) : 3600,
            ]);

            $this->client->refreshToken($account->refresh_token);
            $newToken = $this->client->getAccessToken();

            if (isset($newToken['error'])) {
                Log::error('Error refreshing token', ['error' => $newToken['error']]);
                return null;
            }

            return $newToken;
        } catch (\Exception $e) {
            Log::error('Exception refreshing access token', [
                'error' => $e->getMessage(),
                'account_id' => $account->id,
            ]);
            return null;
        }
    }

    /**
     * Get user info from Google
     * 
     * @param string $accessToken
     * @return array|null User info or null on failure
     */
    public function getUserInfo(string $accessToken): ?array
    {
        try {
            $this->client->setAccessToken($accessToken);
            $oauth2 = new \Google_Service_Oauth2($this->client);
            $userInfo = $oauth2->userinfo->get();

            return [
                'email' => $userInfo->getEmail(),
                'name' => $userInfo->getName(),
                'picture' => $userInfo->getPicture(),
            ];
        } catch (\Exception $e) {
            Log::error('Error getting user info from Google', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
