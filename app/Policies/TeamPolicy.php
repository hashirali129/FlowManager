<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('admin')) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('manager');
    }

    public function view(User $user, Team $team): bool
    {
        return $user->hasRole('admin') || $user->id === $team->manager_id || $team->members()->where('id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('manager');
    }

    public function update(User $user, Team $team): bool
    {
        return $user->hasRole('admin') || $user->id === $team->manager_id;
    }

    public function delete(User $user, Team $team): bool
    {
        return $user->hasRole('admin') || $user->id === $team->manager_id;
    }
}
