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
            <p style="margin: 0;">Dear HR,</p>
        </td>
    </tr>
</table>

<!-- Content -->
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
    <tr>
        <td style="font-family: 'Roboto', Arial, Helvetica, sans-serif; color: #333333; font-size: 15px; line-height: 22px; padding-bottom: 8px;">
            <p style="margin-bottom: 2rem;">Member {{ ucwords(strtolower($name)) }} is requesting LOA. Kindly proceed to the LLIBI HR Portal for approval.</p>
            <p style="margin: 0;">Lacson and Lacson Insurance Brokers, Inc.</p>
        </td>
    </tr>
</table>
@endsection
