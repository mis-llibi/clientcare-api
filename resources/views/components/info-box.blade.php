{{--
  Email Info Box Component
  Styled information box with consistent styling

  Parameters:
  - $type: Box type (info, warning, success, danger)
  - $title: Box title (optional)
  - $content: Box content (can be HTML)
--}}

@php
    $styles = [
        'info' => ['bg' => '#d1ecf1', 'border' => '#bee5eb', 'title_color' => '#0c5460', 'text_color' => '#0c5460'],
        'warning' => ['bg' => '#fff3cd', 'border' => '#ffeaa7', 'title_color' => '#856404', 'text_color' => '#6b4f00'],
        'success' => ['bg' => '#d4edda', 'border' => '#c3e6cb', 'title_color' => '#155724', 'text_color' => '#155724'],
        'danger' => ['bg' => '#f8d7da', 'border' => '#f5c6cb', 'title_color' => '#721c24', 'text_color' => '#721c24'],
    ];
    $boxStyle = $styles[$type ?? 'info'];
@endphp

<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 12px 0;">
    <tr>
        <td style="background-color: {{ $boxStyle['bg'] }}; border: 1px solid {{ $boxStyle['border'] }}; padding: 16px;">
            @if(isset($title) && $title)
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td style="font-family: 'Roboto', Arial, Helvetica, sans-serif; color: {{ $boxStyle['title_color'] }}; font-size: 14px; font-weight: bold; line-height: 18px; padding-bottom: 8px;">
                        {{ $title }}
                    </td>
                </tr>
            </table>
            @endif

            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td style="font-family: 'Roboto', Arial, Helvetica, sans-serif; color: {{ $boxStyle['text_color'] }}; font-size: 13px; line-height: 18px; margin: 0; text-align: justify;">
                        {!! $content !!}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
