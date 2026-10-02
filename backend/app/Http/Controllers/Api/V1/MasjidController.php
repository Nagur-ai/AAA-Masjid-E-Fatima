<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMasjidRequest;
use App\Http\Resources\MasjidResource;
use App\Models\Masjid;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MasjidController extends Controller
{
    private const EARTH_RADIUS_KM = 6371;

    /**
     * List masjids, optionally by name/city (?q=) or nearest to a point (?lat=&lng=&radius_km=).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $params = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'lat' => ['nullable', 'required_with:lng', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'required_with:lat', 'numeric', 'between:-180,180'],
            'radius_km' => ['nullable', 'numeric', 'between:1,500'],
        ]);

        $query = Masjid::query()->with('jumuahSessions')
            ->when($params['q'] ?? null, fn ($q, $term) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('city', 'like', "%{$term}%")
            ));

        if (! isset($params['lat'])) {
            return MasjidResource::collection($query->orderBy('name')->paginate(20));
        }

        // Cheap bounding-box prefilter in SQL, exact great-circle distance in PHP.
        $lat = (float) $params['lat'];
        $lng = (float) $params['lng'];
        $radius = (float) ($params['radius_km'] ?? 25);
        $dLat = rad2deg($radius / self::EARTH_RADIUS_KM);
        $dLng = rad2deg($radius / self::EARTH_RADIUS_KM / max(cos(deg2rad($lat)), 0.01));

        $masjids = $query
            ->whereBetween('latitude', [$lat - $dLat, $lat + $dLat])
            ->whereBetween('longitude', [$lng - $dLng, $lng + $dLng])
            ->limit(200)
            ->get()
            ->each(fn (Masjid $m) => $m->setAttribute('distance_km', $this->distance($lat, $lng, $m->latitude, $m->longitude)))
            ->filter(fn (Masjid $m) => $m->distance_km <= $radius)
            ->sortBy('distance_km')
            ->values();

        return MasjidResource::collection($masjids);
    }

    public function show(Masjid $masjid): MasjidResource
    {
        return new MasjidResource($masjid->load('jumuahSessions'));
    }

    public function update(UpdateMasjidRequest $request, Masjid $masjid): MasjidResource
    {
        $masjid->update($request->validated());

        return new MasjidResource($masjid->load('jumuahSessions'));
    }

    private function distance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $a = sin(deg2rad($lat2 - $lat1) / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin(deg2rad($lng2 - $lng1) / 2) ** 2;

        return self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
