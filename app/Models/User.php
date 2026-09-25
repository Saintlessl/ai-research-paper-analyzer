<?php

namespace App\Models;

use App\Enums\RoleName;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'expertise',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function getActiveRoleOverride(): ?string
    {
        if (request()->hasSession() && session()->has('active_role_override')) {
            // ONLY apply if the user ACTUALLY has SuperAdmin in DB!
            $actualSuper = $this->roles()->where('name', RoleName::SuperAdmin->value)->exists();
            if ($actualSuper) {
                return session('active_role_override');
            }
        }
        return null;
    }

    public function hasRole(RoleName|string $role): bool
    {
        $name = $role instanceof RoleName ? $role->value : $role;

        if ($override = $this->getActiveRoleOverride()) {
            return $override === $name;
        }

        return $this->roles()->where('name', $name)->exists();
    }

    /**
     * @return list<RoleName>
     */
    public function roleNames(): array
    {
        if ($override = $this->getActiveRoleOverride()) {
            return [RoleName::from($override)];
        }

        return $this->resolvedRoles()
            ->map(fn (Role $role): RoleName => $role->name)
            ->values()
            ->all();
    }

    public function hasPermissionTo(string $permission): bool
    {
        if ($this->hasRole(RoleName::SuperAdmin)) {
            return true;
        }

        if ($override = $this->getActiveRoleOverride()) {
            return Role::where('name', $override)->whereHas('permissions', function ($query) use ($permission) {
                $query->where('name', $permission);
            })->exists();
        }

        return $this->roles()->whereHas('permissions', function ($query) use ($permission) {
            $query->where('name', $permission);
        })->exists();
    }

    public function primaryRole(): ?RoleName
    {
        $roleNames = $this->roleNames();

        foreach ([RoleName::SuperAdmin, RoleName::Admin, RoleName::Reviewer, RoleName::Researcher] as $role) {
            if (in_array($role, $roleNames, true)) {
                return $role;
            }
        }

        return null;
    }

    /**
     * @return array{upload_papers: bool, review_papers: bool, manage_system: bool}
     */
    public function capabilities(): array
    {
        $roleNames = $this->roleNames();
        $isSuperAdmin = in_array(RoleName::SuperAdmin, $roleNames, true);
        $isAdmin = in_array(RoleName::Admin, $roleNames, true) || $isSuperAdmin;

        return [
            'upload_papers' => $isAdmin || in_array(RoleName::Researcher, $roleNames, true),
            'review_papers' => $isAdmin || in_array(RoleName::Reviewer, $roleNames, true),
            'manage_system' => $isAdmin,
        ];
    }

    /**
     * @return Collection<int, Role>
     */
    private function resolvedRoles(): Collection
    {
        return $this->relationLoaded('roles')
            ? $this->roles
            : $this->roles()->get();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
