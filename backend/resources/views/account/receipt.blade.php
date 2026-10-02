@extends('layouts.public')

@section('title', 'Receipt '.$donation->receipt_no)

@section('content')
    <div class="card receipt">
        @include('partials.receipt', ['masjid' => $donation->masjid])
        <div class="actions no-print">
            <button class="btn" onclick="window.print()">Print</button>
            <a class="btn secondary" href="{{ route('account') }}">Back to my donations</a>
        </div>
    </div>
@endsection
