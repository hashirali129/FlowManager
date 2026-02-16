<x-mail::message>
    # Your Request Has Been Approved ✓

    Good news! Your request has been approved.

    ## Request Details

    - **Request Type:** {{ $request->requestType->name }}
    - **Submitted On:** {{ $request->created_at->format('M d, Y \a\t h:i A') }}
    - **Approved By:** {{ $approval->approver->name }} ({{ $approval->step->role->name }})
    - **Approved On:** {{ $approval->updated_at->format('M d, Y \a\t h:i A') }}
    - **Current Status:** {{ ucfirst($request->status) }}

    @if($approval->comment)
    ### Approver's Comment
    > {{ $approval->comment }}
    @endif

    @if($request->status === 'pending')
    <x-mail::panel>
        **Next Step:** Your request is pending review at the next approval step.
    </x-mail::panel>
    @else
    <x-mail::panel>
        **Completed:** Your request has been fully approved and is now complete!
    </x-mail::panel>
    @endif

    <x-mail::button :url="config('app.url')">
        View Request
    </x-mail::button>

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>