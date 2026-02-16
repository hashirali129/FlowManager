<?php

namespace App\Services;

use App\Models\Request;
use App\Models\RequestType;
use App\Models\User;
use App\Events\DocumentsUploaded;
use Illuminate\Support\Facades\Log;

class RequestService
{
    public function __construct(
        protected WorkflowEngine $workflowEngine
    ) {}

    public function getUserRequests(User $user)
    {
        return $user->requests()
            ->with(['requestType', 'approvals.step.role', 'approvals.approver', 'documents'])
            ->latest()
            ->get();
    }

    public function getRequestById(int $id)
    {
        return Request::with(['requestType', 'approvals.step.role', 'approvals.approver', 'user', 'documents'])
            ->findOrFail($id);
    }

    public function createRequest(User $user, RequestType $requestType, array $payload, $files = null): Request
    {
        $newRequest = $this->workflowEngine->initiateRequest($user, $requestType, $payload);

        if ($files) {
            $this->handleFileUploads($newRequest, $files);
        }

        $newRequest->load(['requestType', 'approvals.step.role', 'documents']);

        return $newRequest;
    }

    protected function handleFileUploads(Request $request, $files)
    {
        Log::info('Documents found in request - storing locally first', ['count' => count($files)]);

        $fileMetadata = [];

        foreach ($files as $file) {
            try {
                // Store locally in a temp folder
                $tmpPath = $file->store('temp-uploads', 'local');

                $fileMetadata[] = [
                    'tmp_path' => $tmpPath,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ];

                Log::info("File stored locally at: $tmpPath");
            } catch (\Exception $e) {
                Log::error("Failed to store file locally: " . $e->getMessage());
            }
        }

        if (!empty($fileMetadata)) {
            // Fire event for background S3 upload
            event(new DocumentsUploaded($request, $fileMetadata));
            Log::info("Fired DocumentsUploaded event for background S3 upload.");
        }
    }
}
