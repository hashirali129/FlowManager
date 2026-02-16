<?php

namespace App\Policies;

use App\Models\RequestType;
use App\Models\User;

class RequestTypePolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('admin')) {
             return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }
    public function view(User $user, RequestType $requestType): bool { return true; }
    public function create(User $user): bool { return false; }
    public function update(User $user, RequestType $requestType): bool { return false; }
    public function delete(User $user, RequestType $requestType): bool { return false; }
}
