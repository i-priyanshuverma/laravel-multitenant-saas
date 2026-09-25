<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Traits\TenantScoped;
use App\Notifications\ResetPasswordNotification;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, HasRoles, Notifiable, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'avatar_url',
        'is_super_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
        'is_super_admin' => false,
        'avatar_url' => null,
    ];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isSuperAdmin();
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function isOwner(): bool
    {
        return $this->role === UserRole::Owner || $this->role === 'owner';
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin || $this->role === 'admin';
    }

    public function isElevated(): bool
    {
        return $this->isOwner() || $this->isAdmin();
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        /** @var Tenant|null $tenant */
        $tenant = $this->tenant;

        $this->notify(new ResetPasswordNotification($token, $tenant));
    }
}
