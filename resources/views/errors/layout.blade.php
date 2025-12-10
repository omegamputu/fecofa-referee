<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen flex flex-col items-center justify-center bg-neutral-900 text-white">

    @yield('content')

    @fluxScripts
</body>

</html>