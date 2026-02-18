{{--
  LLIBI Email Design System - Base Template
  Universal email template compatible with all major email clients
  Includes: Outlook (2016+), Gmail, Yahoo, Apple Mail, Hotmail
--}}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $emailTitle ?? 'Test Follow-up Request Notification' }}</title>


    <style type="text/css">
        /* Email client resets */
        body,
        table,
        td,
        p,
        a,
        li,
        blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table,
        td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            outline: none;
            text-decoration: none;
        }

        /* Prevent Gmail and Yahoo from showing download message */
        .ReadMsgBody {
            width: 100%;
        }

        .ExternalClass {
            width: 100%;
        }

        .ExternalClass,
        .ExternalClass p,
        .ExternalClass span,
        .ExternalClass font,
        .ExternalClass td,
        .ExternalClass div {
            line-height: 100%;
        }

        /* Hide preheader text */
        .preheader {
            display: none !important;
            visibility: hidden;
            mso-hide: all;
            font-size: 1px;
            line-height: 1px;
            max-height: 0;
            max-width: 0;
            opacity: 0;
            overflow: hidden;
        }

        /* Mobile styles */
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                margin: auto !important;
            }

            .fluid-table {
                width: 100% !important;
            }

            .stack-column {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                direction: ltr !important;
            }

            .center-on-narrow {
                text-align: center !important;
                display: block !important;
                margin-left: auto !important;
                margin-right: auto !important;
                float: none !important;
            }

            .mobile-padding {
                padding: 10px !important;
            }

            .mobile-hide {
                display: none !important;
            }

            .mobile-text-center {
                text-align: center !important;
            }
        }

        /* Dark mode styles */
        @media (prefers-color-scheme: dark) {
            .dark-img {
                display: block !important;
                width: auto !important;
                overflow: visible !important;
                float: none !important;
                max-height: inherit !important;
                max-width: inherit !important;
                line-height: auto !important;
                margin-top: 0px !important;
                visibility: inherit !important;
            }

            .light-img {
                display: none;
                display: none !important;
            }
        }

        [data-ogsc] .dark-img {
            display: block !important;
            width: auto !important;
            overflow: visible !important;
            float: none !important;
            max-height: inherit !important;
            max-width: inherit !important;
            line-height: auto !important;
            margin-top: 0px !important;
            visibility: inherit !important;
        }

        [data-ogsc] .light-img {
            display: none;
            display: none !important;
        }
    </style>
</head>

<body style="margin: 0; padding: 0; background-color: #f4f4f6; font-family: 'Roboto', Arial, Helvetica, sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">

    <!-- Preheader Text -->
    <div class="preheader">{{ $preheaderText ?? 'ClientCare Portal Notification' }}</div>

    <!-- Full Width Background Table -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 0; padding: 0; background-color: #f4f4f6;">
        <tr>
            <td style="padding: 20px 10px;">

                <!-- Email Container -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" class="email-container" style="margin: 0 auto; background-color: #ffffff; border: 1px solid #e6e9ee; border-radius: 8px; max-width: 600px;">

                    <!-- Email Header -->
                    <tr>
                        <td style="padding: 24px 24px 0 24px;">
                            @include('components.header', [
                            'title' => $emailTitle ?? 'Client Care Portal Notification',
                            'subtitle' => $emailSubtitle ?? null,
                            'urgentBadge' => $urgentBadge ?? false
                            ])
                        </td>
                    </tr>

                    <!-- Email Content -->
                    <tr>
                        <td style="padding: 0 24px 24px 24px;">
                            @yield('content')
                        </td>
                    </tr>

                    <!-- Email Footer -->
                    <tr>
                        <td style="padding: 0 24px 24px 24px; border-top: 1px solid #e6e9ee;">
                            @include('components.footer')
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>