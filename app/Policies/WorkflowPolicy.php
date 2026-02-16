<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workflow;

class WorkflowPolicy
{
    public function before(User $user, $ability)
    {
        if ($user->hasRole('admin')) {
            return true;
        }
    }

    public function viewAny(User $user): bool { return false; }
    public function view(User $user, Workflow $workflow): bool { return false; }
    public function create(User $user): bool { return false; }
    public function update(User $user, Workflow $workflow): bool { return false; }
    public function delete(User $user, Workflow $workflow): bool { return false; }
}
