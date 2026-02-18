{{--
  Email Detail Table Component
  Two-column table for displaying key-value pairs

  Parameters:
  - $title: Table title (optional)
  - $details: Array of details [['label' => 'Label', 'value' => 'Value'], ...]
  - $highlightBorder: Show colored left border (default: true)
--}}

<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 20px 0;">
    <tr>
        <td style="background-color: #f8f9fa; border: 1px solid #e6e9ee; {{ ($highlightBorder ?? true) ? 'border-left: 4px solid #1E3161;' : '' }} padding: 20px;">

            @if(isset($title) && $title)
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td style="font-family: 'Roboto', Arial, Helvetica, sans-serif; color: #495057; font-size: 18px; font-weight: 500; line-height: 22px; padding-bottom: 12px; margin: 0;">
                        {{ $title }}
                    </td>
                </tr>
            </table>
            @endif

            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                @foreach($details as $index => $detail)
                <tr>
                    <td style="padding: 8px 0; {{ $index < count($details) - 1 ? 'border-bottom: 1px solid #dee2e6;' : '' }}">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                            <tr>
                                <!-- Label Column -->
                                <td style="width: 40%; font-family: 'Roboto', Arial, Helvetica, sans-serif; color: #6c757d; font-size: 14px; font-weight: bold; line-height: 18px; vertical-align: top; padding-right: 10px;">
                                    {{ $detail['label'] }}:
                                </td>
                                <!-- Value Column -->
                                <td style="width: 60%; font-family: 'Roboto', Arial, Helvetica, sans-serif; color: #495057; font-size: 14px; line-height: 18px; vertical-align: top; text-align: left;">
                                    {!! $detail['value'] !!}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                @endforeach
            </table>

        </td>
    </tr>
</table>
