@extends('emails.layout')

@section('content')
<h2 style="margin-top: 0; color: #1e293b;">New Request Awaiting Your Approval</h2>
<p>A new request has been submitted and requires your attention.</p>

<table class="details-table">
    <tr>
        <th>Request Type</th>
        <td>{{ $request->requestType->name }}</td>
    </tr>
    <tr>
        <th>Submitted By</th>
        <td>{{ $request->user->name }}</td>
    </tr>
    <tr>
        <th>Submitted On</th>
        <td>{{ $request->created_at->format('M d, Y \a\t h:i A') }}</td>
    </tr>
    <tr>
        <th>Status</th>
        <td><span class="badge badge-pending">Pending Approval</span></td>
    </tr>
</table>

@if($request->payload)
<h3 style="color: #475569; font-size: 16px; margin-top: 32px;">Request Information</h3>
<table class="details-table" style="background-color: #f8fafc;">
    @foreach($request->payload as $key => $value)
    <tr>
        <th style="width: 50%;">{{ ucwords(str_replace('_', ' ', $key)) }}</th>
        <td>{{ is_array($value) ? json_encode($value) : $value }}</td>
    </tr>
    @endforeach
</table>
@endif

<div style="text-align: center; margin-top: 32px;">
    <a href="{{ config('app.url') }}" class="button shadow">Review Request</a>
</div>

<p style="margin-top: 32px; font-size: 14px; color: #64748b;">
    Please review and take action on this request at your earliest convenience in the FlowManager dashboard.
</p>
@endsection