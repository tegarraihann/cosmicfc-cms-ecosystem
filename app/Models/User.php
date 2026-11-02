<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'pending_email',
        'email_verified_at',
        'email_change_token',
        'email_change_expires_at',
        'must_change_email',
        'invitation_token',
        'invitation_expires_at',
        'invited_at',
        'role',
        'role_id',
        'is_active',
        'last_login_at',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_change_token',
        'invitation_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_change_expires_at' => 'datetime',
            'must_change_email' => 'boolean',
            'invitation_expires_at' => 'datetime',
            'invited_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->isSuperAdmin() || $this->roleRelation?->slug === 'admin' || $this->role === UserRole::Admin;
    }

    public function isSuperAdmin(): bool
    {
        return $this->roleRelation?->slug === 'superadmin' || $this->role === UserRole::SuperAdmin;
    }

    public function roleRelation(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return (bool) ($this->roleRelation?->is_active
            && $this->roleRelation->permissions()->where('slug', $permission)->exists());
    }
}
