<?php

namespace App\Http\Controllers;

use App\Models\IntegrationCredential;
use App\Models\OAuthToken;
use App\Models\TenantSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;

        // Get all integration credentials
        $integrations = IntegrationCredential::where('team_id', $team->id)
            ->with('oauthTokens')
            ->get()
            ->map(function ($credential) {
                return [
                    'id' => $credential->id,
                    'platform' => $credential->platform,
                    'environment' => $credential->environment,
                    'name' => $credential->name,
                    'is_active' => $credential->is_active,
                    'is_complete' => $credential->isComplete(),
                    'last_verified_at' => $credential->last_verified_at,
                    'has_active_token' => $credential->oauthTokens()
                        ->active()
                        ->where(function ($query) {
                            $query->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now());
                        })
                        ->exists(),
                    'authorization_url' => $credential->isComplete() 
                        ? $credential->getAuthorizationUrl() 
                        : null,
                ];
            });

        // Get general settings
        $generalSettings = TenantSetting::forTeam($team->id)
            ->category('general')
            ->get()
            ->keyBy('key')
            ->map(fn($setting) => $setting->getValue());

        return Inertia::render('Settings/Index', [
            'integrations' => $integrations,
            'generalSettings' => $generalSettings,
            'availablePlatforms' => [
                'ebay' => [
                    'name' => 'eBay',
                    'description' => 'Connect to eBay to manage inventory, orders, and listings',
                    'environments' => ['sandbox', 'production'],
                ],
                'amazon' => [
                    'name' => 'Amazon',
                    'description' => 'Connect to Amazon Seller Central',
                    'environments' => ['production'],
                    'coming_soon' => true,
                ],
                'newegg' => [
                    'name' => 'Newegg',
                    'description' => 'Connect to Newegg Marketplace',
                    'environments' => ['production'],
                    'coming_soon' => true,
                ],
            ],
        ]);
    }

    public function updateGeneralSettings(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
        ]);

        $team = $request->user()->currentTeam;

        foreach ($validated['settings'] as $key => $value) {
            TenantSetting::set($team->id, 'general', $key, $value);
        }

        return back()->with('success', 'Settings updated successfully');
    }
}
