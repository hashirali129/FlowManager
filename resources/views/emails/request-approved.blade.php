@extends('emails.layout')

@section('content')
<div style="text-align: center; margin-bottom: 24px;">
    <span class="badge badge-success" style="font-size: 14px; padding: 4px 12px;">Request Approved</span>
</div>

<h2 style="margin-top: 0; color: #1e293b; text-align: center;">Great News!</h2>
<p style="text-align: center; font-size: 16px;">Your request has been officially <strong>approved</strong>.</p>

<table class="details-table" style="margin-top: 32px;">
    <tr>
        <th>Request Type</th>
        <td>{{ $request->requestType->name }}</td>
    </tr>
    <tr>
        <th>Approved On</th>
        <td>{{ now()->format('M d, Y \a\t h:i A') }}</td>
    </tr>
    <tr>
        <th>Reference ID</th>
        <td>#RQ-{{ str_pad($request->id, 5, '0', STR_PAD_LEFT) }}</td>
    </tr>
</table>

<div style="background-color: #f0fdf4; border-left: 4px solid #22c55e; padding: 16px; margin-top: 24px; border-radius: 4px;">
    <p style="margin: 0; color: #166534; font-size: 14px;"><strong>Approval Message:</strong> The workflow for this request is now complete. You can view any associated documents or comments in the system.</p>
</div>

<div style="text-align: center; margin-top: 32px;">
    <a href="{{ config('app.url') }}" class="button" style="background-color: #16a34a !important;">View Request Details</a>
</div>

<p style="margin-top: 32px; font-size: 14px; text-align: center; color: #64748b;">
    Thank you for using FlowManager.
</p>
@endsection