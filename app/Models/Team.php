<?php

namespace App\Models;

use Laravel\Jetstream\Team as JetstreamTeam;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends JetstreamTeam
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'organization_id',
        'department',
        'settings',
        'personal_team',
    ];

    protected $casts = [
        'settings' => 'array',
        'personal_team' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    // Scope helpers
    public function scopeForOrganization($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopePersonal($query)
    {
        return $query->where('personal_team', true);
    }

    public function scopeRegular($query)
    {
        return $query->where('personal_team', false);
    }
}
