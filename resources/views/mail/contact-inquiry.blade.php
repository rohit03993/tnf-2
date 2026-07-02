<x-mail::message>
# New contact message

**Name:** {{ $inquiry['name'] }}

**Email:** {{ $inquiry['email'] }}

@if(filled($inquiry['phone'] ?? null))
**Phone:** {{ $inquiry['phone'] }}
@endif

**Subject:** {{ $inquiry['subject'] }}

**Message:**

{{ $inquiry['message'] }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
