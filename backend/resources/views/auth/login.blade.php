@extends('layouts.public')

@section('title', 'Sign in')

@section('content')
    <form method="POST" action="{{ route('login') }}" class="card" style="max-width:400px;margin:24px auto">
        @csrf
        <h1>Sign in</h1>
        <p class="muted">Masjid staff go to the admin panel; members see their donations and receipts.</p>
        <div class="field" style="margin-bottom:12px">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        </div>
        <div class="field" style="margin-bottom:12px">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
        </div>
        <label class="check" style="margin-bottom:12px"><input type="checkbox" name="remember" value="1"> Remember me</label>
        <button class="btn" type="submit" style="width:100%">Sign in</button>
    </form>
@endsection
