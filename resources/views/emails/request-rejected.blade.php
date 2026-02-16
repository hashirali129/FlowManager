@extends('emails.layout')

@section('content')
<div style="text-align: center; margin-bottom: 24px;">
    <span class="badge badge-danger" style="font-size: 14px; padding: 4px 12px;">Request Rejected</span>
</div>

<h2 style="margin-top: 0; color: #1e293b; text-align: center;">Update on Your Request</h2>
<p style="text-align: center; font-size: 16px;">Unfortunately, your request has been <strong>rejected</strong> by the reviewer.</p>

<table class="details-table" style="margin-top: 32px;">
    <tr>
        <th>Request Type</th>
        <td>{{ $request->requestType->name }}</td>
    </tr>
    <tr>
        <th>Rejected On</th>
        <td>{{ now()->format('M d, Y \a\t h:i A') }}</td>
    </tr>
    <tr>
        <th>Reference ID</th>
        <td>#RQ-{{ str_pad($request->id, 5, '0', STR_PAD_LEFT) }}</td>
    </tr>
</table>

<div style="background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 16px; margin-top: 24px; border-radius: 4px;">
    <p style="margin: 0; color: #991b1b; font-size: 14px;"><strong>Rejection Comment:</strong></p>
    <p style="margin: 8px 0 0 0; color: #b91c1c; font-style: italic;">"{{ $approval->comments ?? 'No specific reason provided' }}"</p>
</div>

<div style="text-align: center; margin-top: 32px;">
    <a href="{{ config('app.url') }}" class="button" style="background-color: #dc2626 !important;">View Full Details</a>
</div>

<p style="margin-top: 32px; font-size: 14px; color: #64748b; text-align: center;">
    You can review the feedback and submit a new request after addressing the comments.
</p>

<p style="margin-top: 24px; font-size: 14px; text-align: center; color: #94a3b8;">
    Thank you for your understanding.
</p>
@endsection