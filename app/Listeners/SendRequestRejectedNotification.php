<?php

namespace App\Listeners;

use App\Events\RequestRejected;
use App\Mail\RequestRejectedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendRequestRejectedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(RequestRejected $event): void
    {
        Mail::to($event->request->user->email)
            ->send(new RequestRejectedMail($event->request, $event->approval));
    }
}
