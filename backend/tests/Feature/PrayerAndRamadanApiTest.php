<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Masjid;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrayerAndRamadanApiTest extends TestCase
{
    use RefreshDatabase;

    private Masjid $masjid;

    protected function setUp(): void
    {
        parent::setUp();
        $this->masjid = Masjid::factory()->create(['slug' => 'test-masjid']);
        $this->masjid->iqamahRules()->create([
            'prayer' => 'fajr', 'mode' => 'offset', 'offset_minutes' => 20, 'round_to_minutes' => 5, 'effective_from' => '2026-01-01',
        ]);
        $this->masjid->iqamahRules()->create([
            'prayer' => 'isha', 'mode' => 'fixed', 'fixed_time' => '20:45', 'effective_from' => '2026-01-01',
        ]);
        $this->masjid->jumuahSessions()->create(['label' => 'Jumuah', 'khutbah_time' => '13:00', 'iqamah_time' => '13:30']);
    }

    public function test_daily_prayer_times_include_iqamah_and_jumuah_on_friday(): void
    {
        $response = $this->getJson('/api/v1/masjids/test-masjid/prayer-times?date=2026-10-02')->assertOk();

        $response->assertJsonPath('data.date', '2026-10-02')
            ->assertJsonPath('data.weekday', 'Friday')
            ->assertJsonPath('data.weekday_te', 'శుక్రవారం')
            ->assertJsonPath('data.times.fajr', '05:11')
            ->assertJsonPath('data.iqamah.fajr', '05:35') // 05:31 rounded up to 5 minutes
            ->assertJsonPath('data.iqamah.isha', '20:45')
            ->assertJsonPath('data.iqamah.dhuhr', null)
            ->assertJsonPath('data.jumuah.0.khutbah', '13:00')
            ->assertJsonPath('data.hijri.year', 1448);
    }

    public function test_iqamah_rule_applies_from_its_first_day_in_eastern_timezones(): void
    {
        $this->masjid->update(['timezone' => 'Asia/Kolkata', 'latitude' => 17.385, 'longitude' => 78.4867]);
        $this->masjid->iqamahRules()->create([
            'prayer' => 'fajr', 'mode' => 'fixed', 'fixed_time' => '05:30', 'effective_from' => '2026-10-02',
        ]);

        $this->getJson('/api/v1/masjids/test-masjid/prayer-times?date=2026-10-02')
            ->assertJsonPath('data.iqamah.fajr', '05:30');
        $this->getJson('/api/v1/masjids/test-masjid/prayer-times?date=2026-10-01')
            ->assertJsonPath('data.iqamah.fajr', '05:15');
    }

    public function test_monthly_timetable_has_every_day(): void
    {
        $this->getJson('/api/v1/masjids/test-masjid/prayer-times/monthly?year=2026&month=10')
            ->assertOk()
            ->assertJsonCount(31, 'data')
            ->assertJsonPath('data.0.date', '2026-10-01');
    }

    public function test_ramadan_calendar_is_estimated_until_confirmed(): void
    {
        $this->getJson('/api/v1/masjids/test-masjid/ramadan/1447')
            ->assertOk()
            ->assertJsonPath('data.starts_on', '2026-02-18')
            ->assertJsonPath('data.is_confirmed', false)
            ->assertJsonPath('data.days.0.day', 1)
            ->assertJsonPath('data.days.0.hijri.month_name', 'Ramadan')
            ->assertJsonPath('data.days.19.odd_night_after_iftar', 21)
            ->assertJsonPath('data.days.0.weekday_te', 'బుధవారం')
            ->assertJsonPath('data.duas.0.key', 'sehri')
            ->assertJsonPath('data.timetable_image_url', asset('images/ramadan-timetable.jpg'))
            ->assertJsonPath('data.duas.1.key', 'iftar')
            ->assertJsonPath('data.duas.1.telugu', 'అల్లాహుమ్మ లక సుమ్తు వ బిక ఆమన్తు వ అలైక తవక్కల్తు వ అలా రిజ్ఖిక అఫ్తర్తు ఫతఖబ్బల్ మిన్నీ');
    }

    public function test_admin_confirms_moon_sighted_start(): void
    {
        $admin = User::factory()->staffOf($this->masjid, Role::Admin)->create();

        $this->actingAs($admin)->putJson('/api/v1/masjids/test-masjid/ramadan/1447', [
            'starts_on' => '2026-02-19', 'total_days' => 29, 'is_confirmed' => true, 'taraweeh_time' => '20:30',
        ])->assertOk()
            ->assertJsonPath('data.starts_on', '2026-02-19')
            ->assertJsonPath('data.is_confirmed', true)
            ->assertJsonCount(29, 'data.days')
            ->assertJsonPath('data.eid_al_fitr', '2026-03-20');

        $this->getJson('/api/v1/masjids/test-masjid/ramadan/1447')->assertJsonPath('data.starts_on', '2026-02-19');
    }

    public function test_only_admins_of_this_masjid_change_settings(): void
    {
        $treasurer = User::factory()->staffOf($this->masjid, Role::Treasurer)->create();
        $otherAdmin = User::factory()->staffOf(Masjid::factory()->create(), Role::Admin)->create();
        $payload = ['starts_on' => '2026-02-19', 'total_days' => 30];

        $this->actingAs($treasurer)->putJson('/api/v1/masjids/test-masjid/ramadan/1447', $payload)->assertForbidden();
        $this->actingAs($otherAdmin)->putJson('/api/v1/masjids/test-masjid/ramadan/1447', $payload)->assertForbidden();
    }

    public function test_nearby_search_orders_by_distance(): void
    {
        Masjid::factory()->create(['slug' => 'far', 'latitude' => 51.60, 'longitude' => -0.12]);
        Masjid::factory()->create(['slug' => 'too-far', 'latitude' => 53.48, 'longitude' => -2.24]);

        $this->getJson('/api/v1/masjids?lat=51.5074&lng=-0.1278&radius_km=25')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.slug', 'test-masjid')
            ->assertJsonPath('data.1.slug', 'far');
    }
}
