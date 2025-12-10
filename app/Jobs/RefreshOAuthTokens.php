<?php

namespace App\Jobs;

use App\Models\OAuthToken;
use App\Models\IntegrationCredential;
use App\Services\EbayOAuthService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshOAuthTokens implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        Log::info('Starting OAuth token refresh job');

        // Get all tokens that need refresh
        $tokens = OAuthToken::needsRefresh()->get();

        Log::info('Found tokens to refresh', ['count' => $tokens->count()]);

        foreach ($tokens as $token) {
            try {
                $this->refreshToken($token);
            } catch (\Exception $e) {
                Log::error('Failed to refresh token', [
                    'token_id' => $token->id,
                    'platform' => $token->platform,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Completed OAuth token refresh job');
    }

    private function refreshToken(OAuthToken $token): void
    {
        $credential = $token->integrationCredential;

        if (!$credential || !$credential->is_active) {
            Log::warning('Cannot refresh token - credential inactive or missing', [
                'token_id' => $token->id,
            ]);
            return;
        }

        match($token->platform) {
            'ebay' => $this->refreshEbayToken($token, $credential),
            // Add other platforms here as they're implemented
            default => Log::warning('Unknown platform for token refresh', [
                'platform' => $token->platform,
                'token_id' => $token->id,
            ]),
        };
    }

    private function refreshEbayToken(OAuthToken $token, IntegrationCredential $credential): void
    {
        $service = new EbayOAuthService($credential);
        $service->refreshToken($token);

        Log::info('Successfully refreshed eBay token', [
            'token_id' => $token->id,
            'team_id' => $token->team_id,
        ]);
    }
}
