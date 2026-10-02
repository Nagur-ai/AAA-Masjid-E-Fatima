@if (session('status'))
    <div class="alert success" role="status">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert error" role="alert">
        @if ($errors->count() === 1)
            {{ $errors->first() }}
        @else
            <ul style="margin:0;padding-left:1.2rem">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
