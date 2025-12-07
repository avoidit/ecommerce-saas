<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\OrganizationInvitation;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use App\Notifications\UserInvitationNotification;

class UserService
{
    public function createUser(array $data, ?int $organizationId = null): User
    {
        return DB::transaction(function () use ($data, $organizationId) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'organization_id' => $organizationId,
                'employee_id' => $data['employee_id'] ?? null,
                'phone' => $data['phone'] ?? null,
                'department' => $data['department'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'hire_date' => $data['hire_date'] ?? now(),
                'preferences' => array_merge(
                    (new User())->getDefaultPreferences(),
                    $data['preferences'] ?? []
                ),
                'notification_settings' => array_merge(
                    (new User())->getDefaultNotificationSettings(),
                    $data['notification_settings'] ?? []
                ),
                'is_active' => $data['is_active'] ?? true,
                'password_expires_at' => $data['password_expires_at'] ?? now()->addDays(90),
            ]);

            // Create personal team
            if ($organizationId) {
                $personalTeam = Team::create([
                    'name' => $user->name . "'s Team",
                    'organization_id' => $organizationId,
                    'user_id' => $user->id,
                    'personal_team' => true,
                ]);

                $user->update(['current_team_id' => $personalTeam->id]);
            }

            // Assign default role
            if (isset($data['role_id'])) {
                $role = Role::find($data['role_id']);
                if ($role) {
                    $user->assignRole($role, $organizationId);
                }
            }

            $this->logUserActivity($user, 'user_created', null, $user->toArray());

            return $user;
        });
    }

    public function inviteUser(array $data, User $invitedBy): OrganizationInvitation
    {
        return DB::transaction(function () use ($data, $invitedBy) {
            $invitation = OrganizationInvitation::create([
                'organization_id' => $invitedBy->organization_id,
                'invited_by_user_id' => $invitedBy->id,
                'email' => $data['email'],
                'roles' => $data['roles'] ?? [],
                'expires_at' => now()->addDays(7),
            ]);

            // Send invitation email
            Notification::route('mail', $data['email'])
                ->notify(new UserInvitationNotification($invitation));

            $this->logUserActivity($invitedBy, 'user_invited', null, [
                'invited_email' => $data['email'],
                'roles' => $data['roles'] ?? [],
            ]);

            return $invitation;
        });
    }

    public function acceptInvitation(OrganizationInvitation $invitation, array $userData): User
    {
        if ($invitation->isExpired()) {
            throw new \Exception('Invitation has expired');
        }

        if ($invitation->isAccepted()) {
            throw new \Exception('Invitation has already been accepted');
        }

        return DB::transaction(function () use ($invitation, $userData) {
            // Create user
            $user = $this->createUser(
                array_merge($userData, ['organization_id' => $invitation->organization_id])
            );

            // Assign invited roles
            foreach ($invitation->roles as $roleId) {
                $role = Role::find($roleId);
                if ($role) {
                    $user->assignRole($role, $invitation->organization_id);
                }
            }

            // Mark invitation as accepted
            $invitation->accept();

            $this->logUserActivity($user, 'invitation_accepted', null, [
                'invitation_id' => $invitation->id,
                'invited_by' => $invitation->invitedBy->name,
            ]);

            return $user;
        });
    }

    public function updateUser(User $user, array $data): User
    {
        $oldData = $user->toArray();
        
        $user->update($data);
        
        $this->logUserActivity($user, 'user_updated', $oldData, $user->fresh()->toArray());
        
        return $user->fresh();
    }

    public function deactivateUser(User $user, string $reason = null): bool
    {
        $result = $user->update(['is_active' => false]);
        
        if ($result) {
            $this->logUserActivity($user, 'user_deactivated', null, [
                'reason' => $reason,
                'deactivated_by' => auth()->user()?->name,
            ]);
        }
        
        return $result;
    }

    public function activateUser(User $user): bool
    {
        $result = $user->update(['is_active' => true]);
        
        if ($result) {
            $this->logUserActivity($user, 'user_activated', null, [
                'activated_by' => auth()->user()?->name,
            ]);
        }
        
        return $result;
    }

    public function deleteUser(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            $this->logUserActivity($user, 'user_deleted', $user->toArray(), null);
            
            // Remove from teams
            $user->teams()->detach();
            
            // Remove roles
            $user->roles()->detach();
            
            // Soft delete user
            return $user->delete();
        });
    }

    public function assignRole(User $user, Role $role, ?int $organizationId = null, ?int $teamId = null): void
    {
        $user->assignRole($role, $organizationId, $teamId);
        
        $this->logUserActivity($user, 'role_assigned', null, [
            'role' => $role->name,
            'organization_id' => $organizationId,
            'team_id' => $teamId,
            'assigned_by' => auth()->user()?->name,
        ]);
    }

    public function removeRole(User $user, Role $role, ?int $organizationId = null): void
    {
        $user->removeRole($role, $organizationId);
        
        $this->logUserActivity($user, 'role_removed', null, [
            'role' => $role->name,
            'organization_id' => $organizationId,
            'removed_by' => auth()->user()?->name,
        ]);
    }

    public function changePassword(User $user, string $newPassword): bool
    {
        $result = $user->update([
            'password' => Hash::make($newPassword),
            'password_expires_at' => now()->addDays(90),
        ]);
        
        if ($result) {
            $this->logUserActivity($user, 'password_changed', null, [
                'changed_by' => auth()->user()?->name ?? 'self',
            ]);
        }
        
        return $result;
    }

    public function enableMfa(User $user, string $secret, array $recoveryCodes): bool
    {
        $result = $user->update([
            'mfa_enabled' => true,
            'mfa_secret' => encrypt($secret),
            'mfa_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ]);
        
        if ($result) {
            $this->logUserActivity($user, 'mfa_enabled', null, [
                'enabled_by' => auth()->user()?->name ?? 'self',
            ]);
        }
        
        return $result;
    }

    public function disableMfa(User $user): bool
    {
        $result = $user->update([
            'mfa_enabled' => false,
            'mfa_secret' => null,
            'mfa_recovery_codes' => null,
        ]);
        
        if ($result) {
            $this->logUserActivity($user, 'mfa_disabled', null, [
                'disabled_by' => auth()->user()?->name ?? 'self',
            ]);
        }
        
        return $result;
    }

    public function getUserStats(User $user): array
    {
        return [
            'total_logins' => $user->sessions()->count(),
            'last_login' => $user->last_login_at,
            'active_sessions' => $user->sessions()->where('is_current', true)->count(),
            'teams_count' => $user->teams()->count(),
            'roles_count' => $user->roles()->count(),
            'mfa_enabled' => $user->isMfaEnabled(),
            'password_expires_at' => $user->password_expires_at,
        ];
    }

    private function logUserActivity(User $user, string $event, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::create([
            'organization_id' => $user->organization_id,
            'user_id' => auth()->user()?->id ?? $user->id,
            'event' => $event,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
