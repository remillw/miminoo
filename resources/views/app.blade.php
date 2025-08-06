<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">



        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon -->
        <link rel="icon" href="{{ asset('favicon.ico') }}?v={{ now()->timestamp }}" type="image/x-icon">
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v={{ now()->timestamp }}" type="image/x-icon">
        <link rel="apple-touch-icon" href="{{ asset('favicon.ico') }}?v={{ now()->timestamp }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Stripe.js -->
        <script src="https://js.stripe.com/v3/"></script>

        @routes
        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
        <script>
      // Fonction pour détecter l'app mobile
      function isMobileApp() {
        return !!(
          window.ReactNativeWebView || 
          (window.requestDeviceToken) ||
          window.navigator.userAgent.includes('TrouveTaBabySitter/Mobile') ||
          (window.__EXPO_WEBVIEW__) ||
          (window.isExpoApp)
        );
      }

      // Charger le chatbot partout sauf dans l'app mobile
      if (!isMobileApp()) {
        const typebotInitScript = document.createElement("script");
        typebotInitScript.type = "module";
        typebotInitScript.innerHTML = `import Typebot from 'https://cdn.jsdelivr.net/npm/@typebot.io/js@0/dist/web.js'

Typebot.initBubble({
  typebot: "customer-support-2lar6lt",
  theme: {
    button: {
      backgroundColor: "#ff8157",
      customIconSrc:
        "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij4KCTxnIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2ZmZiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIiBzdHJva2Utd2lkdGg9IjIiPgoJCTxwYXRoIGQ9Ik0xOCA0YTMgMyAwIDAgMSAzIDN2OGEzIDMgMCAwIDEtMyAzaC01bC01IDN2LTNINmEzIDMgMCAwIDEtMy0zVjdhMyAzIDAgMCAxIDMtM3pNOS41IDloLjAxbTQuOTkgMGguMDEiIC8+CgkJPHBhdGggZD0iTTkuNSAxM2EzLjUgMy41IDAgMCAwIDUgMCIgLz4KCTwvZz4KPC9zdmc+",
    },
  },
});
`;
        document.addEventListener('DOMContentLoaded', function() {
          document.body.append(typebotInitScript);
        });
      } else {
        console.log('🚫 Chatbot Typebot désactivé sur l\'app mobile');
      }
    </script>
    </body>
</html>
