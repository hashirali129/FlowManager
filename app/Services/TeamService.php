<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;

class TeamService
{
    public function getTeamsForUser(User $user, int $perPage = 15)
    {
        if ($user->hasRole('admin')) {
            return Team::with(['manager', 'members'])->paginate($perPage);
        }

        // If user is a manager, show only their team
        if ($user->hasRole('manager')) {
            return Team::with(['manager', 'members'])
                ->where('manager_id', $user->id)
                ->paginate($perPage);
        }

        return collect();
    }

    public function getTeamById(int $id)
    {
        return Team::with(['manager', 'members'])->findOrFail($id);
    }

    public function createTeam(array $data): Team
    {
        $team = Team::create([
            'name' => $data['name'],
            'manager_id' => $data['manager_id'],
        ]);

        if (isset($data['member_ids'])) {
            User::whereIn('id', $data['member_ids'])->update(['team_id' => $team->id]);
        }

        return $team->load(['manager', 'members']);
    }

    public function updateTeam(Team $team, array $data): Team
    {
        $team->update([
            'name' => $data['name'] ?? $team->name,
            'manager_id' => $data['manager_id'] ?? $team->manager_id,
        ]);

        if (isset($data['member_ids'])) {
            // Remove old members
            User::where('team_id', $team->id)->update(['team_id' => null]);
            // Add new members
            User::whereIn('id', $data['member_ids'])->update(['team_id' => $team->id]);
        }

        return $team->load(['manager', 'members']);
    }

    public function deleteTeam(Team $team): void
    {
        // Remove team association from users
        User::where('team_id', $team->id)->update(['team_id' => null]);
        $team->delete();
    }
}
