<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        
        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'team_id' => $user->team_id,
            'roles' => $user->roles()->select('name')->get()->makeHidden('pivot'),
        ]);
    }
}
