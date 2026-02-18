# Email Components Reference

## Quick Component Guide

### Button Component
```blade
@include('emails.components.button', [
    'url' => 'https://example.com',
    'text' => 'Button Text',
    'style' => 'primary', // primary, success, warning, danger
    'fullWidth' => false   // optional
])
```

### Info Box Component
```blade
@include('emails.components.info-box', [
    'type' => 'info',     // info, warning, success, danger
    'title' => 'Title',   // optional
    'content' => 'HTML content here'
])
```

### Detail Table Component
```blade
@include('emails.components.detail-table', [
    'title' => 'Table Title', // optional
    'details' => [
        ['label' => 'Label 1', 'value' => 'Value 1'],
        ['label' => 'Label 2', 'value' => 'Value 2']
    ],
    'highlightBorder' => true // optional, default true
])
```

### Base Template
```blade
@extends('emails.components.email-base', [
    'emailTitle' => 'Email Title',
    'preheaderText' => 'Preview text',
    'urgentBadge' => false // optional
])

@section('content')
<!-- Your content here -->
@endsection
```

## Color Reference

### Button Styles
- `primary`: #1E3161 (Navy Blue)
- `success`: #28a745 (Green)
- `warning`: #ffc107 (Amber)
- `danger`: #dc3545 (Red)

### Info Box Types
- `info`: Blue background (#d1ecf1)
- `warning`: Yellow background (#fff3cd)
- `success`: Green background (#d4edda)
- `danger`: Red background (#f8d7da)

## Email Testing Checklist

### Desktop Clients
- [ ] Outlook 2016
- [ ] Outlook 2019
- [ ] Outlook 365
- [ ] Apple Mail (macOS)
- [ ] Thunderbird

### Webmail Clients
- [ ] Gmail (Web)
- [ ] Outlook.com
- [ ] Yahoo Mail
- [ ] AOL Mail

### Mobile Clients
- [ ] Gmail (iOS/Android)
- [ ] Apple Mail (iOS)
- [ ] Outlook (iOS/Android)
- [ ] Samsung Email

### Responsive Testing
- [ ] 320px width (mobile)
- [ ] 480px width (mobile landscape)
- [ ] 600px width (tablet)
- [ ] Dark mode compatibility