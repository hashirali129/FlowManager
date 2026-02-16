<?php

namespace App\Listeners;

use App\Events\RequestApproved;
use App\Mail\RequestApprovedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendRequestApprovedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(RequestApproved $event): void
    {
        Mail::to($event->request->user->email)
            ->send(new RequestApprovedMail($event->request, $event->approval));
    }
}
