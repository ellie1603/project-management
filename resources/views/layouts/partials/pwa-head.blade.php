{{-- Lets phones and computers install the system as an app; it keeps working in the browser too. --}}
<link rel="manifest" href="{{ route('app.manifest') }}">
<meta name="theme-color" content="#0c2d6b">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="BMPC">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
