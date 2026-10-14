<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f6b4f">
    <title>@yield('title') · {{ $currentMasjid->name }} admin</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
@php($isAdmin = auth()->user()->role === \App\Enums\Role::Admin)
<header class="topbar">
    <div class="container">
        <a class="brand" href="{{ route('admin.dashboard') }}">{{ $currentMasjid->name }}</a>
        <nav class="nav">
            <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.dashboard')])>Dashboard</a>
            <a href="{{ route('admin.donations.index') }}" @class(['active' => request()->routeIs('admin.donations.*')])>Donations</a>
            <a href="{{ route('admin.transactions.index') }}" @class(['active' => request()->routeIs('admin.transactions.*')])>Ledger</a>
            <a href="{{ route('admin.reports.index') }}" @class(['active' => request()->routeIs('admin.reports.*')])>Reports</a>
            <a href="{{ route('admin.funds.index') }}" @class(['active' => request()->routeIs('admin.funds.*')])>Funds</a>
            @if($isAdmin)
                <a href="{{ route('admin.ramadan.edit') }}" @class(['active' => request()->routeIs('admin.ramadan.*')])>Ramadan</a>
                <a href="{{ route('admin.settings.edit') }}" @class(['active' => request()->routeIs('admin.settings.*')])>Settings</a>
                <a href="{{ route('admin.committee.index') }}" @class(['active' => request()->routeIs('admin.committee.*')])>Committee</a>
                <a href="{{ route('admin.users.index') }}" @class(['active' => request()->routeIs('admin.users.*')])>Staff</a>
            @endif
            <a href="{{ route('masjid.show', $currentMasjid) }}">Public site</a>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit">Sign out</button>
            </form>
        </nav>
        @include('partials.clock', ['clockMasjid' => $currentMasjid])
    </div>
</header>
<main>
    <div class="container">
        @include('partials.flash')
        @yield('content')
    </div>
</main>
</body>
</html>
