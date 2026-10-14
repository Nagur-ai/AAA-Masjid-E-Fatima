<?php

namespace App\Models;

use App\Enums\AsrMethod;
use App\Enums\CalculationMethod;
use App\Enums\HighLatitudeRule;
use Database\Factories\MasjidFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'slug', 'address', 'city', 'country', 'phone', 'email',
    'latitude', 'longitude', 'elevation', 'timezone',
    'calculation_method', 'asr_method', 'high_latitude_rule',
    'hijri_adjustment', 'imsak_minutes_before_fajr', 'currency', 'bank_details', 'ramadan_image_path', 'banner_position',
])]
class Masjid extends Model
{
    /** @use HasFactory<MasjidFactory> */
    use HasFactory;

    /**
     * Every new masjid starts with the default sehri and iftar duas (config/ramadan.php).
     */
    protected static function booted(): void
    {
        static::created(function (Masjid $masjid) {
            foreach (config('ramadan.default_duas', []) as $i => $dua) {
                $masjid->ramadanDuas()->create([...$dua, 'sort_order' => $i]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'elevation' => 'integer',
            'hijri_adjustment' => 'integer',
            'imsak_minutes_before_fajr' => 'integer',
            'calculation_method' => CalculationMethod::class,
            'asr_method' => AsrMethod::class,
            'high_latitude_rule' => HighLatitudeRule::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function iqamahRules(): HasMany
    {
        return $this->hasMany(IqamahRule::class);
    }

    public function jumuahSessions(): HasMany
    {
        return $this->hasMany(JumuahSession::class)->orderBy('sort_order');
    }

    public function ramadanCalendars(): HasMany
    {
        return $this->hasMany(RamadanCalendar::class);
    }

    public function ramadanDuas(): HasMany
    {
        return $this->hasMany(RamadanDua::class)->orderBy('sort_order')->orderBy('id');
    }

    public function committeeMembers(): HasMany
    {
        return $this->hasMany(CommitteeMember::class);
    }

    public function prayerTimeOverrides(): HasMany
    {
        return $this->hasMany(PrayerTimeOverride::class);
    }

    public function ramadanDayTimes(): HasMany
    {
        return $this->hasMany(RamadanDayTime::class);
    }

    public function funds(): HasMany
    {
        return $this->hasMany(Fund::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(TransactionCategory::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
