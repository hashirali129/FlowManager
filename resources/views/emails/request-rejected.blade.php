<x-mail::message>
    # Your Request Has Been Rejected

    Your request has been reviewed and was not approved.

    ## Request Details

    - **Request Type:** {{ $request->requestType->name }}
    - **Submitted On:** {{ $request->created_at->format('M d, Y \a\t h:i A') }}
    - **Rejected By:** {{ $approval->approver->name }} ({{ $approval->step->role->name }})
    - **Rejected On:** {{ $approval->updated_at->format('M d, Y \a\t h:i A') }}
    - **Final Status:** {{ ucfirst($request->status) }}

    @if($approval->comment)
    ### Reason for Rejection
    <x-mail::panel>
        {{ $approval->comment }}
    </x-mail::panel>
    @endif

    If you have questions about this decision, please contact {{ $approval->approver->name }} or your manager.

    <x-mail::button :url="config('app.url')">
        View Request
    </x-mail::button>

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>