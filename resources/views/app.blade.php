<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
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
    </body>
</html>
