<?php
namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        // Create default organization
        $organization = Organization::create([
            'name' => 'Demo Organization',
            'slug' => 'demo-organization',
            'subdomain' => 'demo',
            'email' => 'admin@demo.com',
            'phone' => '+1-555-0100',
            'description' => 'Demo organization for testing and development',
            'settings' => [
                'features' => [
                    'inventory_management' => true,
                    'order_processing' => true,
                    'multi_platform_sync' => true,
                    'analytics' => true,
                    'api_access' => true,
                ],
                'limits' => [
                    'max_users' => 100,
                    'max_products' => 10000,
                    'max_orders_per_month' => 5000,
                ],
                'security' => [
                    'require_mfa' => false,
                    'password_expiry_days' => 90,
                    'max_login_attempts' => 5,
                    'session_timeout_minutes' => 480,
                ],
                'notifications' => [
                    'email_enabled' => true,
                    'sms_enabled' => false,
                    'slack_webhook' => null,
                ],
            ],
            'branding' => [
                'logo_url' => null,
                'primary_color' => '#3B82F6',
                'secondary_color' => '#1E40AF',
                'accent_color' => '#F59E0B',
            ],
            'timezone' => 'America/New_York',
            'currency' => 'USD',
            'status' => 'active',
        ]);

        // Create organization owner
        $owner = User::create([
            'name' => 'Organization Owner',
            'email' => 'owner@demo.com',
            'password' => Hash::make('password'),
            'organization_id' => $organization->id,
            'employee_id' => 'EMP001',
            'department' => 'Management',
            'job_title' => 'CEO',
            'hire_date' => now(),
            'preferences' => [
                'theme' => 'light',
                'language' => 'en',
                'timezone' => 'America/New_York',
                'date_format' => 'Y-m-d',
                'time_format' => '24h',
                'dashboard_layout' => 'default',
            ],
            'notification_settings' => [
                'email' => [
                    'system_updates' => true,
                    'order_notifications' => true,
                    'inventory_alerts' => true,
                    'weekly_reports' => true,
                ],
                'browser' => [
                    'enabled' => true,
                    'order_notifications' => true,
                    'inventory_alerts' => true,
                ],
                'mobile' => [
                    'enabled' => false,
                    'order_notifications' => false,
                    'inventory_alerts' => false,
                ],
            ],
            'email_verified_at' => now(),
        ]);

        // Create personal team for owner
        $personalTeam = Team::create([
            'name' => $owner->name . "'s Team",
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'personal_team' => true,
        ]);

        // Update owner's current team
        $owner->update(['current_team_id' => $personalTeam->id]);

        // Assign organization owner role
        $ownerRole = Role::where('slug', 'organization_owner')->first();
        $owner->assignRole($ownerRole, $organization->id);

        // Create admin user
        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@demo.com',
            'password' => Hash::make('password'),
            'organization_id' => $organization->id,
            'employee_id' => 'EMP002',
            'department' => 'IT',
            'job_title' => 'System Administrator',
            'hire_date' => now(),
            'preferences' => $owner->preferences,
            'notification_settings' => $owner->notification_settings,
            'email_verified_at' => now(),
        ]);

        // Create personal team for admin
        $adminTeam = Team::create([
            'name' => $admin->name . "'s Team",
            'organization_id' => $organization->id,
            'user_id' => $admin->id,
            'personal_team' => true,
        ]);

        $admin->update(['current_team_id' => $adminTeam->id]);

        // Assign organization admin role
        $adminRole = Role::where('slug', 'organization_admin')->first();
        $admin->assignRole($adminRole, $organization->id);

        // Create manager user
        $manager = User::create([
            'name' => 'Sales Manager',
            'email' => 'manager@demo.com',
            'password' => Hash::make('password'),
            'organization_id' => $organization->id,
            'employee_id' => 'EMP003',
            'department' => 'Sales',
            'job_title' => 'Sales Manager',
            'hire_date' => now(),
            'preferences' => $owner->preferences,
            'notification_settings' => $owner->notification_settings,
            'email_verified_at' => now(),
        ]);

        // Create personal team for manager
        $managerTeam = Team::create([
            'name' => $manager->name . "'s Team",
            'organization_id' => $organization->id,
            'user_id' => $manager->id,
            'personal_team' => true,
        ]);

        $manager->update(['current_team_id' => $managerTeam->id]);

        // Assign manager role
        $managerRole = Role::where('slug', 'manager')->first();
        $manager->assignRole($managerRole, $organization->id);

        // Create employee user
        $employee = User::create([
            'name' => 'John Employee',
            'email' => 'employee@demo.com',
            'password' => Hash::make('password'),
            'organization_id' => $organization->id,
            'employee_id' => 'EMP004',
            'department' => 'Operations',
            'job_title' => 'Operations Specialist',
            'hire_date' => now(),
            'preferences' => $owner->preferences,
            'notification_settings' => $owner->notification_settings,
            'email_verified_at' => now(),
        ]);

        // Create personal team for employee
        $employeeTeam = Team::create([
            'name' => $employee->name . "'s Team",
            'organization_id' => $organization->id,
            'user_id' => $employee->id,
            'personal_team' => true,
        ]);

        $employee->update(['current_team_id' => $employeeTeam->id]);

        // Assign employee role
        $employeeRole = Role::where('slug', 'employee')->first();
        $employee->assignRole($employeeRole, $organization->id);

        // Create departmental teams
        $salesTeam = Team::create([
            'name' => 'Sales Team',
            'organization_id' => $organization->id,
            'user_id' => $manager->id,
            'department' => 'Sales',
            'personal_team' => false,
            'settings' => [
                'description' => 'Sales and customer acquisition team',
                'permissions' => ['orders.*', 'analytics.view'],
            ],
        ]);

        $operationsTeam = Team::create([
            'name' => 'Operations Team',
            'organization_id' => $organization->id,
            'user_id' => $admin->id,
            'department' => 'Operations',
            'personal_team' => false,
            'settings' => [
                'description' => 'Daily operations and inventory management',
                'permissions' => ['inventory.*', 'orders.process'],
            ],
        ]);

        // Add team memberships
        $salesTeam->users()->attach($manager->id, ['role' => 'admin']);
        $operationsTeam->users()->attach($employee->id, ['role' => 'member']);
        $operationsTeam->users()->attach($admin->id, ['role' => 'admin']);

        // Create super admin user (platform level)
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@platform.com',
            'password' => Hash::make('superpassword'),
            'organization_id' => null, // Platform level user
            'employee_id' => 'SUPER001',
            'department' => 'Platform',
            'job_title' => 'Platform Administrator',
            'hire_date' => now(),
            'preferences' => $owner->preferences,
            'notification_settings' => $owner->notification_settings,
            'email_verified_at' => now(),
        ]);

        // Create personal team for super admin
        $superAdminTeam = Team::create([
            'name' => $superAdmin->name . "'s Team",
            'organization_id' => $organization->id, // Temporary assignment
            'user_id' => $superAdmin->id,
            'personal_team' => true,
        ]);

        $superAdmin->update(['current_team_id' => $superAdminTeam->id]);

        // Assign super admin role
        $superAdminRole = Role::where('slug', 'super_admin')->first();
        $superAdmin->assignRole($superAdminRole); // No organization restriction
    }
}