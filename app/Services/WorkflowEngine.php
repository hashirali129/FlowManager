<?php

namespace App\Services;

use App\Models\Request;
use App\Models\RequestType;
use App\Models\User;
use App\Models\RequestApproval;
use App\Events\RequestCreated;
use App\Events\RequestApproved;
use App\Events\RequestRejected;
use Illuminate\Support\Facades\DB;
use Exception;

class WorkflowEngine
{
    /**
     * Initiate a new request and assign it to the first step of the workflow.
     */
    public function initiateRequest(User $user, RequestType $requestType, array $payload): Request
    {
        return DB::transaction(function () use ($user, $requestType, $payload) {
            // 1. Create the request
            $request = Request::create([
                'user_id' => $user->id,
                'request_type_id' => $requestType->id,
                'status' => 'pending',
                'current_step_order' => 1, // Start at step 1
                'payload' => $payload,
            ]);

            // 2. Validate workflow exists
            $workflow = $requestType->workflow;
            if (!$workflow || $workflow->steps->isEmpty()) {
                // If no workflow, auto-approve? or throw error? 
                // For now, let's assume all requests must have a workflow.
                throw new Exception("No workflow defined for this request type.");
            }

            // 3. Create initial pending approval record for the first step
            $firstStep = $workflow->steps->sortBy('step_order')->first();

            // FALLBACK LOGIC: If step is 'manager' but user has no manager -> assign to HR
            $stepIdToAssign = $firstStep->id;

            if ($firstStep->role->name === 'manager') {
                // Check if user has a manager (direct or via team)
                if (!$user->manager_id && (!$user->team || !$user->team->manager_id)) {

                    // Logic: If user has no manager, find 'hr' step.
                    // This creates a "Peer Review" or "HR Override" flow.

                    $hrStep = $workflow->steps->filter(function ($step) {
                        return $step->role->name === 'hr';
                    })->first();

                    if ($hrStep) {
                        $stepIdToAssign = $hrStep->id;
                        // Update current step on request to match
                        $request->update(['current_step_order' => $hrStep->step_order]);
                    }
                }
            }

            $approval = RequestApproval::create([
                'request_id' => $request->id,
                'workflow_step_id' => $stepIdToAssign,
                'status' => 'pending',
            ]);

            // Fire event to notify approvers
            event(new RequestCreated($request, $approval));

            return $request;
        });
    }

    /**
     * Process an approval or rejection for a request.
     */
    public function processApproval(Request $request, User $approver, string $status, ?string $comment = null): Request
    {
        if ($request->status !== 'pending') {
            throw new Exception("Request is already finalized.");
        }

        $currentStep = $request->currentStep();
        if (!$currentStep) {
            throw new Exception("Current step not found for request.");
        }

        \Illuminate\Support\Facades\Log::info("Processing Approval", [
            'approver_id' => $approver->id,
            'approver_roles' => $approver->getRoleNames(),
            'step_role' => $currentStep->role->name,
            'requester_id' => $request->user_id,
            'is_manager_of' => $approver->isManagerOf($request->user),
            'has_role_check' => $approver->hasRole($currentStep->role->name),
        ]);

        // Validate if approver has the required role OR is in the management hierarchy OR is admin
        if (!$approver->hasRole($currentStep->role->name) && !$approver->isManagerOf($request->user) && !$approver->hasRole('admin')) {
            throw new Exception("User does not have the required role or managerial authority to approve this step.");
        }

        // Prevent Self-Approval
        if ($approver->id === $request->user_id) {
            throw new Exception("You cannot approve your own request.");
        }

        return DB::transaction(function () use ($request, $currentStep, $approver, $status, $comment) {
            // 1. Update the approval record
            $approval = RequestApproval::where('request_id', $request->id)
                ->where('workflow_step_id', $currentStep->id)
                ->where('status', 'pending')
                ->firstOrFail();

            $approval->update([
                'approved_by' => $approver->id,
                'status' => $status,
                'comment' => $comment,
                'approved_at' => now(),
            ]);

            // 2. Handle Decision
            if ($status === 'rejected') {
                $request->update(['status' => 'rejected']);

                // Fire event to notify requester
                event(new RequestRejected($request, $approval));

                return $request;
            }

            // 3. If Approved, check for next step
            $workflow = $request->requestType->workflow;

            if (!$workflow) {
                // If no workflow is linked, maybe it's auto-approved? 
                // For now, let's treat it as end of process.
                $request->update(['status' => 'approved', 'current_step_order' => null]);
                return $request;
            }

            $nextStep = $workflow->steps()
                ->where('step_order', '>', $currentStep->step_order)
                ->orderBy('step_order')
                ->first();

            if ($nextStep) {

                // FALLBACK LOGIC for Next Step
                $stepIdToAssign = $nextStep->id;
                $nextStepOrder = $nextStep->step_order;

                if ($nextStep->role->name === 'manager') {
                    // Check requester manager
                    $requester = $request->user;
                    if (!$requester->manager_id && (!$requester->team || !$requester->team->manager_id)) {

                        // HR Fallback: If requester is HR and has no manager, assign to HR role (Peer Review)
                        // This logic applies to ANY user with no manager, but specifically requested for HR.

                        // Find HR step
                        $hrStep = $request->requestType->workflow->steps->filter(function ($step) {
                            return $step->role->name === 'hr';
                        })->first();

                        if ($hrStep) {
                            $stepIdToAssign = $hrStep->id;
                            $nextStepOrder = $hrStep->step_order;
                        }
                    }
                }

                // Move to next step
                $request->update(['current_step_order' => $nextStepOrder]);

                // Create pending approval for next step
                $nextApproval = RequestApproval::create([
                    'request_id' => $request->id,
                    'workflow_step_id' => $stepIdToAssign,
                    'status' => 'pending',
                ]);

                // Fire events to notify
                event(new RequestApproved($request, $approval));
                event(new RequestCreated($request, $nextApproval));
            } else {
                // No more steps -> Fully Approved
                $request->update(['status' => 'approved', 'current_step_order' => null]);

                // Fire event to notify requester
                event(new RequestApproved($request, $approval));
            }

            return $request;
        });
    }
}
