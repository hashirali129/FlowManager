<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'request_id' => $this->id,
            'requester' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'type' => $this->requestType->name,
            'status' => $this->status,
            'current_step' => $this->current_step ? $this->current_step->role->name : 'Completed',
            'submitted_at' => $this->created_at->toDateTimeString(),
            'last_updated' => $this->updated_at->toDateTimeString(),
            'payload' => $this->payload,
            'history' => $this->approvals->map(function ($approval) {
                return [
                    'step' => $approval->step->role->name ?? 'Unknown',
                    'status' => $approval->status,
                    'approver' => $approval->approver ? $approval->approver->name : null,
                    'comment' => $approval->comment,
                    'processed_at' => $approval->approved_at ? $approval->approved_at->toDateTimeString() : null,
                ];
            }),
            
            'documents' => $this->documents->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'file_name' => $doc->file_name,
                    'file_type' => $doc->file_type,
                    'file_size' => $doc->file_size,
                    'url' => \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl(
                        $doc->file_path,
                        now()->addDays(3)
                    ),
                ];
            }),
        ];
    }
}
