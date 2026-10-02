<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f6b4f">
    <title>@yield('title', config('app.name'))</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header class="topbar">
    <div class="container">
        @isset($masjid)
            <a class="brand" href="{{ route('masjid.show', $masjid) }}">{{ $masjid->name }}</a>
        @else
            <a class="brand" href="{{ route('home') }}">{{ config('app.name') }}</a>
        @endisset
        <nav class="nav">
            @isset($masjid)
                <a href="{{ route('masjid.show', $masjid) }}" @class(['active' => request()->routeIs('masjid.show')])>Today</a>
                <a href="{{ route('masjid.timetable', $masjid) }}" @class(['active' => request()->routeIs('masjid.timetable')])>Timetable</a>
                <a href="{{ route('masjid.ramadan', $masjid) }}" @class(['active' => request()->routeIs('masjid.ramadan')])>Ramadan</a>
                <a href="{{ route('masjid.donate', $masjid) }}" @class(['active' => request()->routeIs('masjid.donate')])>Donate</a>
            @endisset
            @auth
                @if (auth()->user()->homeUrl() === route('admin.dashboard'))
                    <a href="{{ route('admin.dashboard') }}">Admin panel</a>
                @endif
                <a href="{{ route('account') }}" @class(['active' => request()->routeIs('account*')])>My donations</a>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit">Sign out</button>
                </form>
            @else
                <a href="{{ route('login') }}" @class(['active' => request()->routeIs('login')])>Sign in</a>
            @endauth
        </nav>
    </div>
</header>
<main>
    <div class="container">
        @include('partials.flash')
        @yield('content')
    </div>
</main>
<footer>
    <div class="container">
        @isset($masjid)
            {{ $masjid->address }}{{ $masjid->city ? ', '.$masjid->city : '' }}
            &middot; Times calculated using {{ $masjid->calculation_method->label() }}, Asr {{ $masjid->asr_method->label() }}.
        @endisset
    </div>
</footer>
</body>
</html>
