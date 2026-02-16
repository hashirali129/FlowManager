<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function successResponse($data, $code = 200)
    {
        return response()->json([
            'error' => false,
            'data' => $data
        ], $code);
    }

    protected function errorResponse($message, $code = 400)
    {
        return response()->json([
            'error' => true,
            'message' => $message
        ], $code);
    }
}
