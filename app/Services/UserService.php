<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function getAllUsers(int $perPage = 15)
    {
        return User::with(['roles', 'team', 'manager'])->paginate($perPage);
    }

    public function getUserById(int $id)
    {
        return User::with(['roles', 'team', 'manager'])->findOrFail($id);
    }

    public function createUser(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'team_id' => $data['team_id'] ?? null,
            'manager_id' => $data['manager_id'] ?? null,
        ]);

        if (isset($data['role'])) {
            $user->assignRole($data['role']);
        }

        return $user->load(['roles', 'team', 'manager']);
    }

    public function updateUser(User $user, array $data): User
    {
        $user->update([
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'team_id' => $data['team_id'] ?? $user->team_id,
            'manager_id' => $data['manager_id'] ?? $user->manager_id,
        ]);

        if (isset($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return $user->load(['roles', 'team', 'manager']);
    }

    public function deleteUser(User $user): void
    {
        $user->delete();
    }
}
