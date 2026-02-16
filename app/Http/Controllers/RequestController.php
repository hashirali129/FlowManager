<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequestRequest;
use App\Http\Resources\RequestResource;
use App\Models\Request as RequestModel;
use App\Models\RequestType;
use App\Services\RequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RequestController extends Controller
{
    public function __construct(
        protected RequestService $requestService
    ) {}

    public function index(Request $request)
    {
        $requests = $this->requestService->getUserRequests($request->user());
        return $this->successResponse(RequestResource::collection($requests));
    }

    public function store(StoreRequestRequest $request)
    {
        if ($request->user()->cannot('create', RequestModel::class)) {
            return $this->errorResponse('You are not authorized to create a request.', 403);
        }

        $validated = $request->validated();

        Log::info('Request Content Type: ' . $request->header('Content-Type'));
        Log::info('Request All Input:', $request->all());
        Log::info('Request Files:', $request->allFiles());

        $requestType = RequestType::find($validated['request_type_id']);
        if (!$requestType) {
            return $this->errorResponse('Request Type not found.', 404);
        }

        try {
            $files = $request->hasFile('documents') ? $request->file('documents') : null;
            $newRequest = $this->requestService->createRequest(
                $request->user(),
                $requestType,
                $validated['payload'],
                $files
            );

            return $this->successResponse(new RequestResource($newRequest), 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $requestModel = $this->requestService->getRequestById($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Request not found.', 404);
        }

        if ($request->user()->cannot('view', $requestModel)) {
            return $this->errorResponse('You are not authorized to view this request.', 403);
        }

        return $this->successResponse(new RequestResource($requestModel));
    }
}
