<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // 'approval_id' => $this->id,
            'step_name' => $this->step->role->name ?? 'Unknown',
            'status' => $this->status,
            'assigned_at' => $this->created_at->toDateTimeString(),
            'assigned_at' => $this->created_at->toDateTimeString(),
            'request' => new RequestResource($this->request),
        ];
    }
}
