@component('mail::message')
# {{ $appName }} - Email Test

Hello,

This is a test email notification from {{ $appName }} ({{ $providerName }}).

If you received this email, your email configuration is working correctly.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
