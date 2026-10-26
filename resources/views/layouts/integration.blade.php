<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Integración Siigo')</title>
    <link rel="stylesheet" href="{{ asset('integration/css/integration.css') }}">
    @stack('head')
    @stack('styles')
</head>

<body>
    @include('layouts.partials.integration-nav')

    <main class="rv-page @yield('page_class')">
        @yield('content')
    </main>

    @stack('scripts')
</body>

</html>
