{{-- The masjid's printed Ramadan timetable (config/ramadan.php); opens full size on click. --}}
@if ($url)
    <a class="timetable-image" href="{{ $url }}" target="_blank" rel="noopener" title="Open full size">
        <img src="{{ $url }}" alt="Masjid-e-Fatima Ramadan timetable" loading="lazy">
    </a>
@endif
