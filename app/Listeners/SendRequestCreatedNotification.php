<?php

namespace App\Listeners;

use App\Events\RequestCreated;
use App\Mail\RequestCreatedMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendRequestCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(RequestCreated $event): void
    {
        $request = $event->request;
        $approval = $event->approval;
        $stepRole = $approval->step->role->name;

        // Determine who should receive the notification
        if ($stepRole === 'manager') {
            // Notify the requester's manager
            $requester = $request->user;
            $manager = $requester->manager ?? $requester->team?->manager;

            if ($manager && $manager->email) {
                Mail::to($manager->email)->send(new RequestCreatedMail($request, $approval));
            }
        } else {
            // Notify all users with this role
            $users = User::role($stepRole)->get();

            foreach ($users as $user) {
                if ($user->email) {
                    Mail::to($user->email)->send(new RequestCreatedMail($request, $approval));
                }
            }
        }
    }
}
