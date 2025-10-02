<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>

    <p style="font-weight: normal;">
        Hi <b>{{ $name }}</b>
        <br><br>

        You have successfully submitted your request for LOA.<br><br>

        Our Client Care will respond to your request within {{ $within }} minutes.<br><br>

        Your reference number is <b>{{ $ref }}</b><br><br>

        <b>This is an auto-generated Email. Doesn’t support replies and calls.</b>
    </p>

    <p>For further inquiry and assistance, feel free to contact us through our 24/7 Client Care Hotline.<br /><br />
        <b>LACSON AND LACSON INSURANCE BROKERS, INC.</b><br />
        Manila Line: (02) 8236-6492<br />
        Mobile number for Calls Only:<br />
        0917-8055424<br />
        0917-8855424<br />
        0919-0749433<br />

        Email: clientcare@llibi.com<br /><br />
    </p>

    <span style="font-weight: bold; color: red;">
        CONFIDENTIALITY STATEMENT
    </span>
    <br />

    <span style="font-style: italic;">
      This email may contain personal sensitive information and is intended for the recipients to which it is addressed
      only. If you are not the recipient to whom this email is addressed, reproduction or dissemination of any part of
      this email is strictly prohibited. If you received this in error, please contact sender and immediately delete this
      email and any attachments.
    </span>

    <br />
    <br />
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
