<?php

// database/seeders/RoleSeeder.php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super_admin',
                'description' => 'Platform-wide administrator with full access to all organizations',
                'scope' => 'platform',
                'is_system' => true,
                'permissions' => [
                    // Platform management
                    'platform.manage',
                    'platform.view_analytics',
                    'platform.manage_organizations',
                    'platform.manage_billing',
                    
                    // All organization permissions
                    'organization.*',
                    'users.*',
                    'teams.*',
                    'inventory.*',
                    'orders.*',
                    'analytics.*',
                    'settings.*',
                    'integrations.*',
                ]
            ],
            [
                'name' => 'Organization Owner',
                'slug' => 'organization_owner',
                'description' => 'Owner of the organization with full control',
                'scope' => 'organization',
                'is_system' => true,
                'permissions' => [
                    // Organization management
                    'organization.manage',
                    'organization.view',
                    'organization.update_settings',
                    'organization.manage_billing',
                    'organization.manage_subscription',
                    
                    // User management
                    'users.create',
                    'users.view',
                    'users.update',
                    'users.delete',
                    'users.manage_roles',
                    'users.invite',
                    
                    // Team management
                    'teams.create',
                    'teams.view',
                    'teams.update',
                    'teams.delete',
                    'teams.manage_members',
                    
                    // Inventory management
                    'inventory.create',
                    'inventory.view',
                    'inventory.update',
                    'inventory.delete',
                    'inventory.import',
                    'inventory.export',
                    'inventory.manage_categories',
                    
                    // Order management
                    'orders.create',
                    'orders.view',
                    'orders.update',
                    'orders.delete',
                    'orders.process',
                    'orders.refund',
                    'orders.manage_shipping',
                    
                    // Analytics and reporting
                    'analytics.view',
                    'analytics.export',
                    'reports.view',
                    'reports.create',
                    'reports.export',
                    
                    // Settings
                    'settings.view',
                    'settings.update',
                    'settings.manage_integrations',
                    'settings.manage_api_keys',
                    
                    // Integrations
                    'integrations.view',
                    'integrations.manage',
                    'integrations.connect',
                    'integrations.sync',
                ]
            ],
            [
                'name' => 'Organization Admin',
                'slug' => 'organization_admin',
                'description' => 'Administrator with user management and settings access',
                'scope' => 'organization',
                'is_system' => true,
                'permissions' => [
                    // Limited organization management
                    'organization.view',
                    'organization.update_settings',
                    
                    // User management
                    'users.create',
                    'users.view',
                    'users.update',
                    'users.invite',
                    'users.manage_roles',
                    
                    // Team management
                    'teams.create',
                    'teams.view',
                    'teams.update',
                    'teams.manage_members',
                    
                    // Inventory management
                    'inventory.create',
                    'inventory.view',
                    'inventory.update',
                    'inventory.delete',
                    'inventory.import',
                    'inventory.export',
                    'inventory.manage_categories',
                    
                    // Order management
                    'orders.view',
                    'orders.update',
                    'orders.process',
                    'orders.manage_shipping',
                    
                    // Analytics and reporting
                    'analytics.view',
                    'reports.view',
                    'reports.create',
                    'reports.export',
                    
                    // Settings
                    'settings.view',
                    'settings.update',
                    'settings.manage_integrations',
                    
                    // Integrations
                    'integrations.view',
                    'integrations.manage',
                    'integrations.sync',
                ]
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Department or team manager with operational access',
                'scope' => 'organization',
                'is_system' => true,
                'permissions' => [
                    // Basic organization access
                    'organization.view',
                    
                    // Limited user management
                    'users.view',
                    'users.invite',
                    
                    // Team management
                    'teams.view',
                    'teams.manage_members',
                    
                    // Inventory management
                    'inventory.create',
                    'inventory.view',
                    'inventory.update',
                    'inventory.import',
                    'inventory.export',
                    
                    // Order management
                    'orders.create',
                    'orders.view',
                    'orders.update',
                    'orders.process',
                    'orders.manage_shipping',
                    
                    // Analytics and reporting
                    'analytics.view',
                    'reports.view',
                    'reports.create',
                    
                    // Basic settings
                    'settings.view',
                    
                    // Integrations
                    'integrations.view',
                    'integrations.sync',
                ]
            ],
            [
                'name' => 'Employee',
                'slug' => 'employee',
                'description' => 'Regular employee with basic operational access',
                'scope' => 'organization',
                'is_system' => true,
                'permissions' => [
                    // Basic organization access
                    'organization.view',
                    
                    // Basic user access
                    'users.view',
                    
                    // Team access
                    'teams.view',
                    
                    // Inventory management
                    'inventory.create',
                    'inventory.view',
                    'inventory.update',
                    
                    // Order management
                    'orders.create',
                    'orders.view',
                    'orders.update',
                    'orders.process',
                    
                    // Basic analytics
                    'analytics.view',
                    'reports.view',
                    
                    // View settings
                    'settings.view',
                    
                    // View integrations
                    'integrations.view',
                ]
            ],
            [
                'name' => 'View Only',
                'slug' => 'view_only',
                'description' => 'Read-only access to assigned areas',
                'scope' => 'organization',
                'is_system' => true,
                'permissions' => [
                    // Basic organization access
                    'organization.view',
                    
                    // View users
                    'users.view',
                    
                    // View teams
                    'teams.view',
                    
                    // View inventory
                    'inventory.view',
                    
                    // View orders
                    'orders.view',
                    
                    // View analytics
                    'analytics.view',
                    'reports.view',
                    
                    // View settings
                    'settings.view',
                    
                    // View integrations
                    'integrations.view',
                ]
            ]
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['slug' => $roleData['slug'], 'scope' => $roleData['scope']],
                $roleData
            );
        }
    }
}
