{{--
  Email Button Component
  Cross-client compatible button with VML fallback for Outlook

  Parameters:
  - $url: Button link URL
  - $text: Button text
  - $style: Button style (primary, success, warning, danger)
  - $fullWidth: Make button full width (optional)
--}}

@php
    $fullWidth = $fullWidth ?? false;
    $styles = [
        'primary' => ['bg' => '#1E3161', 'color' => '#ffffff'],
        'success' => ['bg' => '#28a745', 'color' => '#ffffff'],
        'warning' => ['bg' => '#ffc107', 'color' => '#000000'],
        'danger' => ['bg' => '#dc3545', 'color' => '#ffffff'],
    ];
    $buttonStyle = $styles[$style ?? 'primary'];
@endphp

<!-- Bulletproof Button -->
<table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto;" class="button-table">
    <tr>
        <td align="center" style="border-radius: 6px; background-color: {{ $buttonStyle['bg'] }};">
            <a href="{{ $url }}"
               target="_blank"
               style="display: inline-block;
                      padding: 14px 32px;
                      min-width: 200px;
                      max-width: 400px;
                      font-family: 'Roboto', Arial, Helvetica, sans-serif;
                      font-size: 15px;
                      font-weight: 700;
                      color: #ffffff !important;
                      text-decoration: none !important;
                      border-radius: 6px;
                      background-color: {{ $buttonStyle['bg'] }};
                      border: 1px solid {{ $buttonStyle['bg'] }};
                      line-height: 1.5;
                      text-align: center;
                      word-wrap: break-word;
                      word-break: break-word;
                      -webkit-text-size-adjust: none;
                      box-sizing: border-box;">
                <span style="color: #ffffff !important; text-decoration: none !important; display: inline-block; word-wrap: break-word;">{{ $text }}</span>
            </a>
        </td>
    </tr>
</table>
