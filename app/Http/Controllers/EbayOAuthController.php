<?php

namespace App\Http\Controllers;

use App\Models\IntegrationCredential;
use App\Models\OAuthToken;
use App\Services\EbayOAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EbayOAuthController extends Controller
{
    public function callback(Request $request, string $environment)
    {
        try {
            // Validate the state parameter
            $state = decrypt($request->state);
            
            if (!isset($state['team_id']) || !isset($state['credential_id'])) {
                throw new \Exception('Invalid state parameter');
            }

            // Verify timestamp is recent (within 10 minutes)
            if (isset($state['timestamp']) && now()->timestamp - $state['timestamp'] > 600) {
                throw new \Exception('OAuth state expired');
            }

            // Get the credential
            $credential = IntegrationCredential::findOrFail($state['credential_id']);

            // Verify the user has access to this team
            if (!$request->user()->belongsToTeam($credential->team)) {
                abort(403, 'You do not have access to this team');
            }

            // Check if this should be a user-level token
            $userId = $request->query('user_level') === 'true' 
                ? $request->user()->id 
                : null;

            // Handle error response from eBay
            if ($request->has('error')) {
                Log::warning('eBay OAuth error', [
                    'error' => $request->error,
                    'error_description' => $request->error_description,
                ]);

                return redirect()->route('settings.index')
                    ->withErrors(['ebay' => 'eBay authorization failed: ' . $request->error_description]);
            }

            // Validate authorization code
            if (!$request->has('code')) {
                throw new \Exception('No authorization code received');
            }

            // Exchange code for token
            $service = new EbayOAuthService($credential);
            $token = $service->exchangeCodeForToken($request->code, $userId);

            $message = $userId 
                ? 'eBay connected successfully to your account'
                : 'eBay connected successfully to your team';

            return redirect()->route('settings.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('eBay OAuth callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('settings.index')
                ->withErrors(['ebay' => 'Failed to connect to eBay: ' . $e->getMessage()]);
        }
    }

    public function disconnect(Request $request, string $environment)
    {
        $team = $request->user()->currentTeam;

        // Determine if disconnecting user-level or team-level
        $userId = $request->query('user_level') === 'true' 
            ? $request->user()->id 
            : null;

        $token = OAuthToken::where('team_id', $team->id)
            ->where('platform', 'ebay')
            ->where('environment', $environment)
            ->where('user_id', $userId)
            ->active()
            ->first();

        if (!$token) {
            return back()->withErrors(['ebay' => 'No active eBay connection found']);
        }

        // Get the credential to revoke the token
        $credential = $token->integrationCredential;
        if ($credential) {
            $service = new EbayOAuthService($credential);
            $service->revokeToken($token);
        } else {
            // Just deactivate if credential is missing
            $token->deactivate();
        }

        $message = $userId 
            ? 'eBay disconnected from your account'
            : 'eBay disconnected from your team';

        return back()->with('success', $message);
    }

    public function refreshToken(Request $request, string $environment)
    {
        $team = $request->user()->currentTeam;

        $userId = $request->query('user_level') === 'true' 
            ? $request->user()->id 
            : null;

        $token = OAuthToken::where('team_id', $team->id)
            ->where('platform', 'ebay')
            ->where('environment', $environment)
            ->where('user_id', $userId)
            ->active()
            ->first();

        if (!$token) {
            return back()->withErrors(['ebay' => 'No active eBay token found']);
        }

        $credential = $token->integrationCredential;
        if (!$credential) {
            return back()->withErrors(['ebay' => 'Integration credentials not found']);
        }

        try {
            $service = new EbayOAuthService($credential);
            $service->refreshToken($token);

            return back()->with('success', 'eBay token refreshed successfully');
        } catch (\Exception $e) {
            Log::error('Failed to manually refresh eBay token', [
                'token_id' => $token->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['ebay' => 'Failed to refresh token: ' . $e->getMessage()]);
        }
    }
}
