{{--
  Email Header Component
  Reusable header with LLIBI branding and consistent styling
--}}
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
    <tr>
        <td style="text-align: center; padding-bottom: 18px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: 0 auto;">
                <tr>
                    <td align="center" style="text-align: center;">
                            <img src="https://llibi.app/images/lacson-logo.png"
                            alt="LLIBI LOGO"
                            width="250"
                            style="display: block; margin: 0 auto; padding-bottom: 8px; border: 0; outline: none; text-decoration: none; max-width: 300px; height: auto;">
                        <h1 style="margin: 0; font-family: 'Roboto', Arial, Helvetica, sans-serif; font-size: 20px; color: #1E3161; font-weight: 700; line-height: 24px;">
                            {{ $title }}
                        </h1>
                        @if($urgentBadge)
                            <!--[if mso]>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: 8px auto 0 auto;">
                                <tr>
                                    <td style="background-color: #dc3545; color: #ffffff; padding: 4px 8px; border-radius: 4px; font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-weight: bold;">
                                        URGENT
                                    </td>
                                </tr>
                            </table>
                            <![endif]-->
                            <!--[if !mso]><!-->
                            <div style="display: inline-block; background-color: #dc3545; color: #ffffff; padding: 4px 8px; border-radius: 4px; font-family: 'Roboto', Arial, Helvetica, sans-serif; font-size: 12px; font-weight: bold; margin-top: 8px;">
                                URGENT
                            </div>
                            <!--<![endif]-->
                        @endif
                        @if($subtitle)
                            <p style="margin: 8px 0 0 0; font-family: 'Roboto', Arial, Helvetica, sans-serif; font-size: 14px; color: #6c757d; line-height: 18px;">
                                {{ $subtitle }}
                            </p>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding-bottom: 20px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td style="border-bottom: 2px solid #1E3161; line-height: 1px; font-size: 1px;">&nbsp;</td>
                </tr>
            </table>
        </td>
    </tr>
</table>