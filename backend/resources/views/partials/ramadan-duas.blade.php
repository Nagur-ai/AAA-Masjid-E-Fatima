{{-- Sehri and iftar duas in Telugu (config/ramadan.php). --}}
<div class="duas">
    @foreach ($duas as $dua)
        <div @class(['dua', 'dua-'.$dua['key']]) lang="te">
            <strong class="dua-title">{{ $dua['title_te'] }}</strong>
            <p class="dua-telugu">{{ $dua['telugu'] }}</p>
        </div>
    @endforeach
</div>
