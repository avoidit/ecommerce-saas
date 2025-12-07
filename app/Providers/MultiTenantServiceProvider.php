<?php

namespace App\Providers;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;

class MultiTenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->setupGlobalScopes();
    }

    private function setupGlobalScopes(): void
    {
        // Add global scope to automatically filter by organization for models that support it
        $models = [
            \App\Models\User::class,
            \App\Models\Team::class,
            \App\Models\AuditLog::class,
        ];

        foreach ($models as $model) {
            $model::addGlobalScope('organization', function (Builder $builder) {
                $organization = app('current_organization');
                
                if ($organization && $builder->getModel()->getTable() !== 'organizations') {
                    $builder->where('organization_id', $organization->id);
                }
            });
        }
    }
}