@extends('layouts.public')

@section('content')
    <div class="page-head"><h1>{{ __('Choose a masjid') }}</h1></div>
    @if ($masjids->isEmpty())
        <div class="card">No masjids have been set up yet. Run <code>php artisan db:seed</code> to load the demo masjid.</div>
    @else
        <div class="grid grid-3">
            @foreach ($masjids as $m)
                <a class="card" href="{{ route('masjid.show', $m) }}">
                    <h3>{{ $m->name }}</h3>
                    <span class="muted">{{ $m->city }}</span>
                </a>
            @endforeach
        </div>
    @endif
@endsection
