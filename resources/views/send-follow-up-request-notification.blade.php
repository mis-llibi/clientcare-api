@extends('components.email-base')

@php
$emailTitle = 'Client Care Portal Notification';
$preheaderText = 'Client Care Portal Notification';
@endphp

@section('content')

<!-- Greeting -->
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
    <tr>
        <td style="font-family: 'Roboto', Arial, Helvetica, sans-serif; color: #333333; font-size: 15px; line-height: 22px; padding-bottom: 8px;">
            <p style="margin: 0;">Hi Client Care Team,</p>
        </td>
    </tr>
</table>

<!-- Introduction -->
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
    <tr>
        <td style="font-family: 'Roboto', Arial, Helvetica, sans-serif; color: #333333; font-size: 15px; line-height: 22px; padding-bottom: 8px;">
            <p style="margin: 0;">Please be informed that member <strong>{{ $patientName }}</strong> has already made 5 consecutive follow-up requests for the past 25 minutes regarding his LOA request.</p>
        </td>
    </tr>
</table>
@endsection