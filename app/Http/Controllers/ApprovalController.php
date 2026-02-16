<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActionRequestRequest;
use App\Http\Resources\ApprovalResource;
use App\Http\Resources\RequestResource;
use App\Models\Request as RequestModel;
use App\Services\ApprovalService;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function __construct(
        protected ApprovalService $approvalService
    ) {}

    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 15), 100);
        $pendingApprovals = $this->approvalService->getPendingApprovals($request->user(), $perPage);
        return $this->successResponse(ApprovalResource::collection($pendingApprovals));
    }

    public function action(ActionRequestRequest $request, $id)
    {
        try {
            $requestModel = RequestModel::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Request not found.', 404);
        }

        if ($request->user()->cannot('update', $requestModel)) {
            return $this->errorResponse('You are not authorized to approve or reject this request.', 403);
        }

        $validated = $request->validated();

        try {
            $updatedRequest = $this->approvalService->processApprovalAction(
                $requestModel,
                $request->user(),
                $validated['action'],
                $validated['comment'] ?? null
            );

            return $this->successResponse(new RequestResource($updatedRequest));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
