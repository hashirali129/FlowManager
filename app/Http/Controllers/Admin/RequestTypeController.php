<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequestTypeRequest;
use App\Http\Requests\UpdateRequestTypeRequest;
use App\Http\Resources\RequestTypeResource;
use App\Models\RequestType;
use Illuminate\Http\Request;

class RequestTypeController extends Controller
{
    public function __construct()
    {
        // Debugging Middleware Registration
        // Note: User will still be NULL here because middleware hasn't run yet.
        // But we can see if 'auth:sanctum' is attached.
        // dd('Constructor Run', 'User (Expected Null):', auth()->user(), 'Middleware:', $this->getMiddleware());

        // $this->authorizeResource(RequestType::class, 'request_type');
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', RequestType::class);
        $perPage = min((int) $request->query('per_page', 15), 100);
        return $this->successResponse(RequestTypeResource::collection(RequestType::paginate($perPage)));
    }

    public function store(StoreRequestTypeRequest $request)
    {
        $this->authorize('create', RequestType::class);
        $validated = $request->validated();
        $requestType = RequestType::create($validated);
        return $this->successResponse(new RequestTypeResource($requestType), 201);
    }

    public function show($id)
    {
        try {
            $requestType = RequestType::with('workflow.steps')->findOrFail($id);
            $this->authorize('view', $requestType);
            return $this->successResponse(new RequestTypeResource($requestType));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Request Type not found.', 404);
        }
    }

    public function update(UpdateRequestTypeRequest $request, $id)
    {
        try {
            $requestType = RequestType::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Request Type not found.', 404);
        }

        $this->authorize('update', $requestType);

        $validated = $request->validated();
        $requestType->update($validated);
        return $this->successResponse(new RequestTypeResource($requestType));
    }

    public function destroy($id)
    {
        try {
            $requestType = RequestType::findOrFail($id);
            $this->authorize('delete', $requestType);
            $requestType->delete();
            return $this->successResponse(null, 204);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Request Type not found.', 404);
        }
    }
}
