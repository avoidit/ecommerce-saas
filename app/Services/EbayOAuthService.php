<?php

namespace App\Services;

use App\Models\IntegrationCredential;
use App\Models\OAuthToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EbayOAuthService
{
    private string $baseUrl;
    private string $apiUrl;

    public function __construct(
        private IntegrationCredential $credential
    ) {
        // OAuth endpoints
        $this->baseUrl = $credential->environment === 'sandbox'
            ? 'https://api.sandbox.ebay.com/identity/v1/oauth2'
            : 'https://api.ebay.com/identity/v1/oauth2';

        // API endpoints  
        $this->apiUrl = $credential->environment === 'sandbox'
            ? 'https://api.sandbox.ebay.com'
            : 'https://api.ebay.com';
    }

    /**
     * Exchange authorization code for access token
     */
    public function exchangeCodeForToken(string $code, ?int $userId = null): OAuthToken
    {
        $response = Http::withBasicAuth(
            $this->credential->decrypted_client_id,
            $this->credential->decrypted_client_secret
        )->asForm()->post($this->baseUrl . '/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => route('integrations.ebay.callback', [
                'environment' => $this->credential->environment,
            ]),
        ]);

        if ($response->failed()) {
            Log::error('eBay OAuth token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to exchange authorization code for token');
        }

        $data = $response->json();

        // Get eBay user info if possible
        $metadata = $this->getUserInfo($data['access_token']);

        // Deactivate any existing tokens for this team/user/platform/environment
        OAuthToken::where('team_id', $this->credential->team_id)
            ->where('platform', 'ebay')
            ->where('environment', $this->credential->environment)
            ->where('user_id', $userId)
            ->update(['is_active' => false]);

        // Create new token
        $token = OAuthToken::create([
            'team_id' => $this->credential->team_id,
            'user_id' => $userId,
            'integration_credential_id' => $this->credential->id,
            'platform' => 'ebay',
            'environment' => $this->credential->environment,
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'expires_at' => now()->addSeconds($data['expires_in']),
            'scopes' => isset($data['scope']) ? explode(' ', $data['scope']) : null,
            'metadata' => $metadata,
            'is_active' => true,
        ]);

        Log::info('eBay OAuth token created', [
            'team_id' => $this->credential->team_id,
            'user_id' => $userId,
            'environment' => $this->credential->environment,
            'expires_at' => $token->expires_at,
        ]);

        return $token;
    }

    /**
     * Refresh an access token
     */
    public function refreshToken(OAuthToken $token): OAuthToken
    {
        if (empty($token->decrypted_refresh_token)) {
            throw new \Exception('No refresh token available');
        }

        $response = Http::withBasicAuth(
            $this->credential->decrypted_client_id,
            $this->credential->decrypted_client_secret
        )->asForm()->post($this->baseUrl . '/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $token->decrypted_refresh_token,
            'scope' => implode(' ', $token->scopes ?? []),
        ]);

        if ($response->failed()) {
            Log::error('eBay OAuth token refresh failed', [
                'token_id' => $token->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            
            // If refresh fails, deactivate the token
            $token->deactivate();
            
            throw new \Exception('Failed to refresh access token');
        }

        $data = $response->json();

        // Update token
        $token->update([
            'access_token' => $data['access_token'],
            'expires_at' => now()->addSeconds($data['expires_in']),
            'last_refreshed_at' => now(),
        ]);

        Log::info('eBay OAuth token refreshed', [
            'token_id' => $token->id,
            'expires_at' => $token->expires_at,
        ]);

        return $token->fresh();
    }

    /**
     * Get eBay user information
     */
    private function getUserInfo(string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->get($this->apiUrl . '/commerce/identity/v1/user/');

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch eBay user info', [
                'error' => $e->getMessage(),
            ]);
        }

        return [];
    }

    /**
     * Verify credentials by attempting to get an application token
     */
    public function verifyCredentials(): bool
    {
        try {
            $response = Http::withBasicAuth(
                $this->credential->decrypted_client_id,
                $this->credential->decrypted_client_secret
            )->asForm()->post($this->baseUrl . '/token', [
                'grant_type' => 'client_credentials',
                'scope' => 'https://api.ebay.com/oauth/api_scope',
            ]);

            if ($response->successful()) {
                $this->credential->update([
                    'last_verified_at' => now(),
                ]);
                return true;
            }

            Log::error('eBay credentials verification failed', [
                'credential_id' => $this->credential->id,
                'status' => $response->status(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('eBay credentials verification error', [
                'credential_id' => $this->credential->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Make an authenticated API request
     */
    public function apiRequest(string $method, string $endpoint, array $data = [], ?OAuthToken $token = null): array
    {
        if (!$token) {
            $token = OAuthToken::getActiveToken(
                $this->credential->team_id,
                'ebay',
                $this->credential->environment
            );

            if (!$token) {
                throw new \Exception('No active eBay token available');
            }
        }

        // Refresh if needed
        if ($token->needsRefresh()) {
            $token = $this->refreshToken($token);
        }

        $token->markAsUsed();

        $url = $this->apiUrl . $endpoint;

        $headers = [
            'Content-Language' => 'en-US',
            'Content-Type' => 'application/json',
        ];

        $response = Http::withToken($token->decrypted_access_token)
            ->withHeaders($headers)
            ->$method($url, $data);

        if ($response->failed()) {
            Log::error('eBay API request failed', [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            
            throw new \Exception('eBay API request failed: ' . $response->body());
        }

        return $response->json() ?? [];
    }

    /**
     * Revoke a token
     */
    public function revokeToken(OAuthToken $token): bool
    {
        try {
            $response = Http::withBasicAuth(
                $this->credential->decrypted_client_id,
                $this->credential->decrypted_client_secret
            )->asForm()->post($this->baseUrl . '/revoke', [
                'token' => $token->decrypted_access_token,
            ]);

            $token->deactivate();

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to revoke eBay token', [
                'token_id' => $token->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
