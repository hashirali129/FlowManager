<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeamResource;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function __construct(
        protected TeamService $teamService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Team::class);
        $perPage = min((int) $request->query('per_page', 15), 100);
        $teams = $this->teamService->getTeamsForUser($request->user(), $perPage);
        return $this->successResponse(TeamResource::collection($teams));
    }

    public function store(StoreTeamRequest $request)
    {
        $this->authorize('create', Team::class);
        $validated = $request->validated();

        if (!$request->user()->hasRole('admin') || !isset($validated['manager_id'])) {
            $validated['manager_id'] = $request->user()->id;
        }

        $team = $this->teamService->createTeam($validated);
        return $this->successResponse(new TeamResource($team), 201);
    }

    public function show($id)
    {
        try {
            $team = $this->teamService->getTeamById($id);
            $this->authorize('view', $team);
            return $this->successResponse(new TeamResource($team));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Team not found.', 404);
        }
    }

    public function update(UpdateTeamRequest $request, $id)
    {
        try {
            $team = Team::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Team not found.', 404);
        }

        $this->authorize('update', $team);
        $validated = $request->validated();

        if (!$request->user()->hasRole('admin') && isset($validated['manager_id'])) {
            $validated['manager_id'] = $request->user()->id;
        }

        $updatedTeam = $this->teamService->updateTeam($team, $validated);
        return $this->successResponse(new TeamResource($updatedTeam));
    }

    public function destroy($id)
    {
        try {
            $team = Team::findOrFail($id);
            $this->authorize('delete', $team);
            $this->teamService->deleteTeam($team);
            return $this->successResponse(null, 204);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Team not found.', 404);
        }
    }

    public function removeMember(Request $request, $teamId, $userId)
    {
        try {
            $team = Team::findOrFail($teamId);
            $userToRemove = User::findOrFail($userId);

            // Authorization
            // Admin can remove anyone.
            // Manager can remove ONLY from their own team.
            if (!$request->user()->hasRole('admin')) {
                if ($team->manager_id !== $request->user()->id) {
                    return $this->errorResponse('You are not authorized to remove members from this team.', 403);
                }
            }

            // Check if user belongs to this team
            if ($userToRemove->team_id !== $team->id) {
                return $this->errorResponse('User does not belong to this team.', 400);
            }

            // Perform Removal
            $userToRemove->update(['team_id' => null, 'manager_id' => null]);

            return $this->successResponse(['message' => 'User removed from team successfully.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Team or User not found.', 404);
        }
    }
}
