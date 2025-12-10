<?php

namespace App\Http\Controllers;

use App\Models\IntegrationCredential;
use App\Services\EbayOAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IntegrationController extends Controller
{
    public function store(Request $request)
    {
        // Only team owners can add integrations
        Gate::authorize('update', $request->user()->currentTeam);

        $validated = $request->validate([
            'platform' => 'required|string|in:ebay,amazon,newegg',
            'environment' => 'required|string|in:sandbox,production',
            'name' => 'nullable|string|max:255',
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
        ]);

        $team = $request->user()->currentTeam;

        // Check if credentials already exist
        $existing = IntegrationCredential::where('team_id', $team->id)
            ->where('platform', $validated['platform'])
            ->where('environment', $validated['environment'])
            ->first();

        if ($existing) {
            return back()->withErrors([
                'platform' => 'Credentials for this platform and environment already exist.',
            ]);
        }

        $credential = IntegrationCredential::create([
            'team_id' => $team->id,
            'platform' => $validated['platform'],
            'environment' => $validated['environment'],
            'name' => $validated['name'] ?? ucfirst($validated['platform']) . ' ' . ucfirst($validated['environment']),
            'client_id' => $validated['client_id'],
            'client_secret' => $validated['client_secret'],
            'is_active' => true,
        ]);

        // Verify credentials for eBay
        if ($validated['platform'] === 'ebay') {
            $service = new EbayOAuthService($credential);
            $verified = $service->verifyCredentials();

            if (!$verified) {
                return back()->withErrors([
                    'client_id' => 'Failed to verify credentials. Please check your Client ID and Client Secret.',
                ]);
            }
        }

        return back()->with('success', 'Integration credentials added successfully');
    }

    public function update(Request $request, IntegrationCredential $credential)
    {
        Gate::authorize('update', $request->user()->currentTeam);

        // Ensure credential belongs to current team
        if ($credential->team_id !== $request->user()->currentTeam->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'client_id' => 'sometimes|string',
            'client_secret' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $credential->update($validated);

        // Re-verify if credentials changed
        if (isset($validated['client_id']) || isset($validated['client_secret'])) {
            if ($credential->platform === 'ebay') {
                $service = new EbayOAuthService($credential->fresh());
                $verified = $service->verifyCredentials();

                if (!$verified) {
                    return back()->withErrors([
                        'client_id' => 'Failed to verify updated credentials.',
                    ]);
                }
            }
        }

        return back()->with('success', 'Integration credentials updated successfully');
    }

    public function destroy(Request $request, IntegrationCredential $credential)
    {
        Gate::authorize('update', $request->user()->currentTeam);

        // Ensure credential belongs to current team
        if ($credential->team_id !== $request->user()->currentTeam->id) {
            abort(403);
        }

        // Deactivate all associated tokens
        $credential->oauthTokens()->update(['is_active' => false]);

        $credential->delete();

        return back()->with('success', 'Integration credentials removed successfully');
    }

    public function verifyCredentials(Request $request, IntegrationCredential $credential)
    {
        Gate::authorize('update', $request->user()->currentTeam);

        // Ensure credential belongs to current team
        if ($credential->team_id !== $request->user()->currentTeam->id) {
            abort(403);
        }

        if ($credential->platform === 'ebay') {
            $service = new EbayOAuthService($credential);
            $verified = $service->verifyCredentials();

            if ($verified) {
                return back()->with('success', 'Credentials verified successfully');
            } else {
                return back()->withErrors([
                    'verification' => 'Failed to verify credentials. Please check your Client ID and Client Secret.',
                ]);
            }
        }

        return back()->withErrors([
            'platform' => 'Verification not supported for this platform yet.',
        ]);
    }
}
