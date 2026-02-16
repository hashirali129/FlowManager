<?php

namespace App\Policies;

use App\Models\Request;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; 
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Request $request): bool
    {
        // 1. Owner
        if ($user->id === $request->user_id) {
            return true;
        }

        // 2. Admin
        if ($user->hasRole('admin')) {
            return true;
        }

        // 3. Current Approver
        if ($request->status === 'pending' && $request->currentStep) {
             $requiredRole = $request->currentStep->role; 
             if ($requiredRole && $user->hasRole($requiredRole->name)) {
                 return true;
             }
        }
        
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; 
    }

    /**
     * Determine whether the user can update the model (Approve/Reject).
     */
    public function update(User $user, Request $request): bool
    {
        if ($request->status !== 'pending') {
            return false;
        }

        if ($user->hasRole('admin')) {
             return true; 
        }

        $requiredRole = $request->currentStep->role;
        return $requiredRole && $user->hasRole($requiredRole->name);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Request $request): bool
    {
        return $user->hasRole('admin');
    }
}
