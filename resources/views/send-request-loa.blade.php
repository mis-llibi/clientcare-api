<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  {{-- <title>Document</title> --}}
</head>

<body>
  Hi <b>{{ $name }}</b>{{ $dependent !== null ? " and " : ""}}<b>{{ $dependent !== null ? " $dependent" : ""}}</b>,
  <br /><br />

  {!! $statusRemarks !!}
  <br /><br />

  {{-- <img src="https://llibi-storage.sgp1.cdn.digitaloceanspaces.com/Self-service/Images/infographics-eloa.jpg" alt="Infographic" width="400"> --}}
  {{-- <img src="{{ config('app.storage_cdn') }}/Self-service/Images/infographics-eloa.jpg" alt="Infographic" width="400"> --}}

  @if($is_accept_eloa)
    <img src="{{ config('app.DO_ENDPOINT') }}/Self-service/Images/infographics-eloa.jpg" alt="Infographic" width="400">
    <br /><br />
  @endif









  For further inquiry and assistance, feel free to contact us through our 24/7 Client Care Hotline.<br /><br />
  <b>LACSON AND LACSON INSURANCE BROKERS, INC.</b><br />
  Manila Line: (02) 8236-6492<br />
  Mobile number for Calls Only:<br />
  0917-8055424<br />
  0917-8855424<br />
  0919-0749433<br />

  Email: clientcare@llibi.com<br /><br />

  Your reference number is <b>{{ $ref }}</b>.
  <br />
  <br />

  <span style="font-weight: bold; color: red;">
    CONFIDENTIALITY STATEMENT
  </span>
  <br>
  {{-- <span style="font-style: italic;">
    This email contains confidential and sensitive personal information and is intended only for the recipient who
    made
    the LOA request. If we sent this to you by error, please let us know and destroy all copies of this email and
    attachments. Do not reproduce or disseminate any part of this email and its attachments to any unintended
    recipients.
  </span> --}}

  <span style="font-style: italic;">
    This email may contain personal sensitive information and is intended for the recipients to which it is addressed
    only. If you are not the recipient to whom this email is addressed, reproduction or dissemination of any part of
    this email is strictly prohibited. If you received this in error, please contact sender and immediately delete this
    email and any attachments.
  </span>
  <br />
  <br />
  {!! $feedbackLink !!}

  <hr>
  <footer>
    <div>
      <img src="https://llibi.app/images/lacson-logo.png" alt="LLIBI LOGO" width="200">
    </div>
    <div>
      <small style="color: gray;">&#9400; {{ date('Y') }} Lacson & Lacson Insurance Brokers, Inc. | 15th Floor
        Burgundy Corporate Tower 252 Sen. Gil Puyat Ave., Makati City, 1200</small>
    </div>
    <div>
      <small style="font-weight: bold; text-transform: uppercase;">This is an auto-generated Email. Does not support
        replies.</small>
    </div>
  </footer>
</body>

</html>
