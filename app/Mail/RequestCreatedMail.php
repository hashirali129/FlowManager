<?php

namespace App\Mail;

use App\Models\Request;
use App\Models\RequestApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequestCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Request $request,
        public RequestApproval $approval
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Request Awaiting Your Approval',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.request-created',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
