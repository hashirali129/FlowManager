<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkflowRequest;
use App\Http\Requests\UpdateWorkflowRequest;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Http\Resources\WorkflowResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkflowController extends Controller
{
    public function __construct()
    {
        // $this->authorizeResource(Workflow::class, 'workflow');
    }

    public function index()
    {
        $this->authorize('viewAny', Workflow::class);
        $workflows = Workflow::with(['requestType', 'steps.role'])->get();
        return $this->successResponse(WorkflowResource::collection($workflows));
    }

    public function store(StoreWorkflowRequest $request)
    {
        $this->authorize('create', Workflow::class);
        $validated = $request->validated();

        return DB::transaction(function () use ($validated) {
            $workflow = Workflow::create([
                'request_type_id' => $validated['request_type_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            foreach ($validated['steps'] as $stepData) {
                WorkflowStep::create([
                    'workflow_id' => $workflow->id,
                    'role_id' => $stepData['role_id'],
                    'step_order' => $stepData['step_order'],
                ]);
            }

            return $this->successResponse(new WorkflowResource($workflow->load(['steps.role', 'requestType'])), 201);
        });
    }

    public function show($id)
    {
        try {
            $workflow = Workflow::with(['requestType', 'steps.role'])->findOrFail($id);
            $this->authorize('view', $workflow);
            return $this->successResponse(new WorkflowResource($workflow));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Workflow not found.', 404);
        }
    }

    public function update(UpdateWorkflowRequest $request, $id)
    {
        try {
            $workflow = Workflow::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Workflow not found.', 404);
        }

        $this->authorize('update', $workflow);

        $validated = $request->validated();

        return DB::transaction(function () use ($workflow, $validated) {
            $workflow->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            // Simple sync: Delete all old steps and recreate
            $workflow->steps()->delete();

            foreach ($validated['steps'] as $stepData) {
                WorkflowStep::create([
                    'workflow_id' => $workflow->id,
                    'role_id' => $stepData['role_id'],
                    'step_order' => $stepData['step_order'],
                ]);
            }

            return $this->successResponse(new WorkflowResource($workflow->load(['steps.role', 'requestType'])));
        });
    }

    public function destroy($id)
    {
        try {
            $workflow = Workflow::findOrFail($id);
            $this->authorize('delete', $workflow);
            $workflow->delete();
            return $this->successResponse(null, 204);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Workflow not found.', 404);
        }
    }
}
