<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $teamId = $this->route('team')->id;
        $user = $this->user();
        $isHr = $user->hasRole('hr');

        return [
            'name' => 'sometimes|string|unique:teams,name,' . $teamId,
            'manager_id' => ['sometimes', 'nullable', 'exists:users,id', function ($attribute, $value, $fail) use ($isHr) {
                $manager = \App\Models\User::find($value);
                if ($manager) {
                    if (!$manager->hasRole('manager')) {
                        $fail("User {$manager->name} does not have the 'manager' role.");
                    }
                    // HR Restriction: Can only assign HRs as managers
                    if ($isHr && !$manager->hasRole('hr')) {
                        $fail("HR users can only assign other HRs (with manager role) as team managers.");
                    }
                }
            }],
            'members' => 'nullable|array',
            'members.*' => ['exists:users,id', function ($attribute, $value, $fail) use ($isHr) {
                $member = \App\Models\User::find($value);
                if ($member) {
                    if ($member->team_id !== null && $member->team_id != $this->route('team')->id) {
                        $fail("User {$member->name} already belongs to another team.");
                    }
                    // HR Restriction: Can only add HRs as members
                    if ($isHr && !$member->hasRole('hr')) {
                        $fail("HR users can only add other HRs to their team.");
                    }
                }
            }],
        ];
    }
}
