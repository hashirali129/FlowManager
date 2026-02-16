<?php

namespace App\Services;

use App\Models\Request;
use App\Models\RequestApproval;
use App\Models\User;

class ApprovalService
{
    public function __construct(
        protected WorkflowEngine $workflowEngine
    ) {}

    public function getPendingApprovals(User $user, int $perPage = 15)
    {
        $roleNames = $user->getRoleNames();

        if ($user->hasRole('admin')) {
            return RequestApproval::where('status', 'pending')
                ->with(['request.requestType', 'request.user', 'request.documents', 'step.role'])
                ->paginate($perPage);
        }

        return RequestApproval::where('status', 'pending')
            ->whereHas('step.role', function ($query) use ($roleNames) {
                $query->whereIn('name', $roleNames);
            })
            ->with(['request.requestType', 'request.user', 'request.documents', 'step.role'])
            ->paginate($perPage);
    }

    public function processApprovalAction(Request $request, User $approver, string $action, ?string $comment = null): Request
    {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $updatedRequest = $this->workflowEngine->processApproval($request, $approver, $status, $comment);

        $updatedRequest->load(['requestType', 'approvals.step.role', 'approvals.approver', 'user', 'documents']);

        return $updatedRequest;
    }
}
