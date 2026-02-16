<x-mail::message>
    # New Request Awaiting Your Approval

    A new request has been submitted and requires your attention.

    ## Request Details

    - **Request Type:** {{ $request->requestType->name }}
    - **Submitted By:** {{ $request->user->name }}
    - **Submitted On:** {{ $request->created_at->format('M d, Y \a\t h:i A') }}
    - **Current Step:** {{ $approval->step->role->name }} Approval

    @if($request->payload)
    ### Request Information
    @foreach($request->payload as $key => $value)
    - **{{ ucwords(str_replace('_', ' ', $key)) }}:** {{ is_array($value) ? json_encode($value) : $value }}
    @endforeach
    @endif

    <x-mail::button :url="config('app.url')">
        Review Request
    </x-mail::button>

    Please review and take action on this request at your earliest convenience.

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>