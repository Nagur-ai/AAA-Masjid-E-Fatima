@extends('layouts.admin')

@section('title', 'Receipt '.$donation->receipt_no)

@section('content')
    <div class="card receipt">
        @include('partials.receipt', ['masjid' => $currentMasjid])
        <div class="actions no-print">
            <button class="btn" onclick="window.print()">Print</button>
            <a class="btn secondary" href="{{ route('admin.donations.index') }}">Back to donations</a>
        </div>
    </div>
@endsection
