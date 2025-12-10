<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class IntegrationCredential extends Model
{

    protected $table = 'integration_credentials';

    protected $fillable = [
        'team_id',
        'platform',
        'environment',
        'name',
        'client_id',
        'client_secret',
        'config',
        'is_active',
        'last_verified_at',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'last_verified_at' => 'datetime',
    ];

    protected $hidden = [
        'client_secret',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Encrypt credentials before saving
        static::saving(function ($credential) {
            if ($credential->isDirty('client_id') && !empty($credential->client_id)) {
                try {
                    // Only encrypt if not already encrypted
                    Crypt::decryptString($credential->client_id);
                } catch (\Exception $e) {
                    $credential->client_id = Crypt::encryptString($credential->client_id);
                }
            }
            
            if ($credential->isDirty('client_secret') && !empty($credential->client_secret)) {
                try {
                    // Only encrypt if not already encrypted
                    Crypt::decryptString($credential->client_secret);
                } catch (\Exception $e) {
                    $credential->client_secret = Crypt::encryptString($credential->client_secret);
                }
            }
        });
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function oauthTokens(): HasMany
    {
        return $this->hasMany(OAuthToken::class);
    }

    /**
     * Get decrypted client ID
     */
    public function getDecryptedClientIdAttribute(): string
    {
        try {
            return Crypt::decryptString($this->client_id);
        } catch (\Exception $e) {
            // If already decrypted or invalid, return as is
            return $this->client_id;
        }
    }

    /**
     * Get decrypted client secret
     */
    public function getDecryptedClientSecretAttribute(): string
    {
        try {
            return Crypt::decryptString($this->client_secret);
        } catch (\Exception $e) {
            // If already decrypted or invalid, return as is
            return $this->client_secret;
        }
    }

    /**
     * Check if credentials are valid (have both client_id and client_secret)
     */
    public function isComplete(): bool
    {
        return !empty($this->client_id) && !empty($this->client_secret);
    }

    /**
     * Get the OAuth authorization URL for this platform
     */
    public function getAuthorizationUrl(): ?string
    {
        return match($this->platform) {
            'ebay' => $this->getEbayAuthUrl(),
            'amazon' => null, // To be implemented
            'newegg' => null, // To be implemented
            default => null,
        };
    }

    /**
     * Get eBay authorization URL
     */
    private function getEbayAuthUrl(): string
    {
        $authBaseUrl = $this->environment === 'sandbox'
            ? 'https://auth.sandbox.ebay.com/oauth2/authorize'
            : 'https://auth.ebay.com/oauth2/authorize';

        $scopes = implode(' ', [
            'https://api.ebay.com/oauth/api_scope/sell.inventory',
            'https://api.ebay.com/oauth/api_scope/sell.fulfillment',
            'https://api.ebay.com/oauth/api_scope/sell.account',
            'https://api.ebay.com/oauth/api_scope/sell.marketing',
            'https://api.ebay.com/oauth/api_scope/sell.analytics.readonly',
            'https://api.ebay.com/oauth/api_scope/sell.finances',
            'https://api.ebay.com/oauth/api_scope/sell.payment.dispute',
            'https://api.ebay.com/oauth/api_scope/commerce.identity.readonly',
        ]);

        $params = http_build_query([
            'client_id' => $this->decrypted_client_id,
            'response_type' => 'code',
            'redirect_uri' => route('integrations.ebay.callback', ['environment' => $this->environment]), // Use route() instead of hardcoded
            'scope' => $scopes,
            'state' => encrypt([
                'team_id' => $this->team_id,
                'credential_id' => $this->id,
                'timestamp' => now()->timestamp,
            ]),
        ]);

        return $authBaseUrl . '?' . $params;
    }
    
    /**
     * Scope for active credentials
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for a specific platform
     */
    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope for a specific environment
     */
    public function scopeEnvironment($query, string $environment)
    {
        return $query->where('environment', $environment);
    }
}
