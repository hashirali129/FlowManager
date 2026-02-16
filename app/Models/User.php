<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'manager_id',
        'team_id',
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
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function subordinates()
    {
        return $this->hasMany(User::class, 'manager_id');
    }


    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    public function approvals()
    {
        return $this->hasMany(RequestApproval::class, 'approved_by');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Check if the user is a direct manager or 2nd-level manager of the given subordinate.
     */
    public function isManagerOf(User $subordinate): bool
    {
        // 1. Direct Manager
        if ($subordinate->manager_id === $this->id) {
            return true;
        }

        // 2. Manager's Manager (Skip-level)
        if ($subordinate->manager && $subordinate->manager->manager_id === $this->id) {
            return true;
        }

        return false;
    }
}
