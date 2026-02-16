<?php

namespace App\Events;

use App\Models\Request;
use App\Models\RequestApproval;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RequestCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Request $request,
        public RequestApproval $approval
    ) {}
}
