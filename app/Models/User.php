<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_active',
        'preferences',
        'avatar',
        'password_changed_at',
        'force_password_change'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'is_active' => 'boolean',
        'force_password_change' => 'boolean',
        'preferences' => 'array',
    ];

    // Relationships
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Check if user has permission
    public function hasPermission($permission)
    {
        if (!$this->role) {
            return false;
        }

        return $this->role->hasPermission($permission);
    }

    // Check if user has any of the given permissions
    public function hasAnyPermission(array $permissions)
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    // Get avatar URL
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar && Storage::disk('public')->exists($this->avatar)) {
            return Storage::url($this->avatar);
        }

        // Generate initials avatar as fallback
        $initials = collect(explode(' ', $this->name))->map(function ($name) {
            return strtoupper(substr($name, 0, 1));
        })->implode('');

        return "https://ui-avatars.com/api/?name=" . urlencode($initials) .
            "&background=3b82f6&color=fff&size=100";
    }

    // Get role name
    public function getRoleNameAttribute()
    {
        return $this->role ? $this->role->display_name : 'No Role';
    }

    // Get status badge class
    public function getStatusBadgeClassAttribute()
    {
        return $this->is_active ? 'badge-success' : 'badge-error';
    }


    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithRole($query)
    {
        return $query->with('role');
    }

    // Delete avatar when user is deleted
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($user) {
            if ($user->avatar && Storage::exists($user->avatar)) {
                Storage::delete($user->avatar);
            }
        });
    }
}
