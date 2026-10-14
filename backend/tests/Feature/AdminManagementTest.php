<?php

namespace Tests\Feature;

use App\Enums\IqamahMode;
use App\Enums\Role;
use App\Enums\TransactionType;
use App\Models\Masjid;
use App\Models\RamadanDua;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Admins add, edit and delete the masjid's content on every admin tab.
 */
class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    private Masjid $masjid;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('email', 'admin@masjid.test')->first();
        $this->masjid = $this->admin->masjid;
    }

    public function test_new_masjid_starts_with_sehri_and_iftar_duas(): void
    {
        $this->assertSame(['sehri', 'iftar'], $this->masjid->ramadanDuas()->pluck('type')->all());
    }

    public function test_admin_adds_edits_hides_and_deletes_a_dua(): void
    {
        $page = "/msd/{$this->masjid->slug}/ramadan";

        $this->actingAs($this->admin)->post('/admin/ramadan/duas', [
            'type' => 'other', 'title' => 'లైలతుల్ ఖద్ర్ దుఆ', 'body' => 'అల్లాహుమ్మ ఇన్నక అఫువ్వున్', 'is_active' => '1',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $dua = RamadanDua::where('title', 'లైలతుల్ ఖద్ర్ దుఆ')->firstOrFail();
        $this->assertSame(2, $dua->sort_order); // after the two default duas
        $this->get($page)->assertSee('అల్లాహుమ్మ ఇన్నక అఫువ్వున్');

        $this->put("/admin/ramadan/duas/{$dua->id}", [
            'type' => 'other', 'title' => 'లైలతుల్ ఖద్ర్', 'body' => 'కొత్త పాఠం', 'is_active' => '1',
        ])->assertSessionHasNoErrors();
        $this->get($page)->assertSee('కొత్త పాఠం')->assertDontSee('అల్లాహుమ్మ ఇన్నక అఫువ్వున్');

        // Unticking "Show" hides it from the public calendar and the API.
        $this->put("/admin/ramadan/duas/{$dua->id}", ['type' => 'other', 'title' => 'లైలతుల్ ఖద్ర్', 'body' => 'కొత్త పాఠం']);
        $this->get($page)->assertDontSee('కొత్త పాఠం');
        $this->getJson("/api/v1/masjids/{$this->masjid->slug}/ramadan")->assertJsonCount(2, 'data.duas');

        $this->delete("/admin/ramadan/duas/{$dua->id}")->assertSessionHasNoErrors();
        $this->assertModelMissing($dua);
    }

    public function test_dua_of_another_masjid_cannot_be_touched_and_treasurer_cannot_manage_duas(): void
    {
        $other = Masjid::factory()->create();
        $foreign = $other->ramadanDuas()->first();

        $this->actingAs($this->admin)->delete("/admin/ramadan/duas/{$foreign->id}")->assertNotFound();
        $this->assertModelExists($foreign);

        $treasurer = User::where('email', 'treasurer@masjid.test')->first();
        $this->actingAs($treasurer)->post('/admin/ramadan/duas', ['type' => 'sehri', 'title' => 'x', 'body' => 'y'])->assertForbidden();
    }

    public function test_admin_uploads_and_removes_the_timetable_image(): void
    {
        Storage::fake();

        $this->actingAs($this->admin)->post('/admin/ramadan/image', [
            'image' => UploadedFile::fake()->image('timetable.jpg', 600, 800),
        ])->assertSessionHasNoErrors();

        $path = $this->masjid->refresh()->ramadan_image_path;
        Storage::assertExists($path);
        $this->get("/msd/{$this->masjid->slug}/ramadan-image")->assertOk();
        $this->get("/msd/{$this->masjid->slug}/ramadan")->assertSee('class="timetable-image"', false);

        $this->delete('/admin/ramadan/image')->assertSessionHasNoErrors();
        Storage::assertMissing($path);
        $this->assertNull($this->masjid->refresh()->ramadan_image_path);
        $this->get("/msd/{$this->masjid->slug}/ramadan-image")->assertNotFound();
    }

    public function test_admin_edits_iqamah_rule_and_jumuah_session(): void
    {
        $rule = $this->masjid->iqamahRules()->where('prayer', 'fajr')->first();
        $this->actingAs($this->admin)->put("/admin/settings/iqamah/{$rule->id}", [
            'prayer' => 'fajr', 'mode' => 'fixed', 'fixed_time' => '05:30', 'round_to_minutes' => 0,
            'effective_from' => $rule->effective_from->toDateString(),
        ])->assertSessionHasNoErrors();
        $this->assertSame(IqamahMode::Fixed, $rule->refresh()->mode);
        $this->assertStringStartsWith('05:30', $rule->fixed_time);

        $session = $this->masjid->jumuahSessions()->first();
        $this->put("/admin/settings/jumuah/{$session->id}", [
            'label' => 'First Jumuah', 'khutbah_time' => '12:45', 'iqamah_time' => '13:15', 'language' => 'Urdu',
        ])->assertSessionHasNoErrors();
        $this->assertSame('First Jumuah', $session->refresh()->label);
        $this->assertStringStartsWith('12:45', $session->khutbah_time);
    }

    public function test_fund_is_deleted_only_when_unused(): void
    {
        $used = $this->masjid->funds()->where('name', 'General')->first();
        $this->actingAs($this->admin)->delete("/admin/funds/{$used->id}")->assertSessionHasErrors('fund');
        $this->assertModelExists($used);

        $this->post('/admin/funds', ['name' => 'Madrasa', 'type' => 'general', 'is_active' => '1'])->assertSessionHasNoErrors();
        $new = $this->masjid->funds()->where('name', 'Madrasa')->first();
        $this->delete("/admin/funds/{$new->id}")->assertSessionHasNoErrors();
        $this->assertModelMissing($new);
    }

    public function test_category_is_renamed_and_deleted_only_when_unused(): void
    {
        $this->actingAs($this->admin);

        $unused = $this->masjid->categories()->where('name', 'Education')->first();
        $this->put("/admin/categories/{$unused->id}", ['name' => 'Madrasa'])->assertSessionHasNoErrors();
        $this->assertSame('Madrasa', $unused->refresh()->name);
        $this->delete("/admin/categories/{$unused->id}")->assertSessionHasNoErrors();
        $this->assertModelMissing($unused);

        $used = $this->masjid->categories()->where('name', 'Utilities')->first();
        $this->delete("/admin/categories/{$used->id}")->assertSessionHasErrors('category');
        $this->assertModelExists($used);

        $system = $this->masjid->categories()->where('is_system', true)->where('type', TransactionType::Income)->first();
        $this->put("/admin/categories/{$system->id}", ['name' => 'Gifts'])->assertSessionHasErrors('category');
        $this->delete("/admin/categories/{$system->id}")->assertSessionHasErrors('category');
    }

    public function test_admin_edits_and_deletes_staff_but_not_themselves(): void
    {
        $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'Imam', 'email' => 'imam@masjid.test', 'role' => 'treasurer', 'password' => 'secret-pass-123',
        ])->assertSessionHasNoErrors();
        $imam = User::where('email', 'imam@masjid.test')->first();

        $this->put("/admin/users/{$imam->id}", [
            'name' => 'Imam Sahab', 'email' => 'imam@masjid.test', 'phone' => '9999999999', 'role' => 'admin', 'password' => 'new-pass-4567',
        ])->assertSessionHasNoErrors();
        $imam->refresh();
        $this->assertSame('Imam Sahab', $imam->name);
        $this->assertSame(Role::Admin, $imam->role);
        $this->assertTrue(password_verify('new-pass-4567', $imam->password));

        $this->delete("/admin/users/{$this->admin->id}")->assertSessionHasErrors('user');
        $this->assertModelExists($this->admin);

        $this->delete("/admin/users/{$imam->id}")->assertSessionHasNoErrors();
        $this->assertModelMissing($imam);
    }

    public function test_staff_who_recorded_ledger_entries_is_kept(): void
    {
        $recorder = User::find($this->masjid->transactions()->value('recorded_by'));
        $other = User::factory()->staffOf($this->masjid)->create();

        $this->actingAs($other)->delete("/admin/users/{$recorder->id}")->assertSessionHasErrors('user');
        $this->assertModelExists($recorder);
    }

    public function test_admin_tabs_show_the_management_controls(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/ramadan')->assertOk()->assertSee('Add dua')->assertSee('Upload image')->assertSee('నవైతు అన్ అసూమ');
        $this->get('/admin/settings')->assertOk()->assertSee('Save rule')->assertSee('Save session');
        $this->get('/admin/funds')->assertOk()->assertSee('Delete fund')->assertSee('Rename');
        $this->get('/admin/users')->assertOk()->assertSee('New password (optional)');
    }

    public function test_public_home_shows_todays_sehri_iftar_and_jumuah_table(): void
    {
        $this->get("/msd/{$this->masjid->slug}")->assertOk()
            ->assertSee('Sehri ends')->assertSee('Iftar')->assertSee('si-time', false)
            ->assertSee('Khutbah')->assertSee('13:15')->assertSee('13:45');
    }

    public function test_admin_sets_and_resets_daily_sehri_and_iftar_times(): void
    {
        $api = "/api/v1/masjids/{$this->masjid->slug}/ramadan/1448";
        $calculated = $this->getJson($api)->json('data.days.1');

        $this->actingAs($this->admin)->put('/admin/ramadan/1448/days', [
            'sehri' => [1 => '05:14', 2 => $calculated['sehri_ends']],
            'iftar' => [1 => '18:22', 2 => $calculated['iftar']],
        ])->assertRedirect()->assertSessionHasNoErrors();

        // Day 1 uses the admin's times; day 2 matched the calculation, so nothing is stored for it.
        $this->getJson($api)
            ->assertJsonPath('data.days.0.sehri_ends', '05:14')
            ->assertJsonPath('data.days.0.iftar', '18:22')
            ->assertJsonPath('data.days.0.sehri_custom', true)
            ->assertJsonPath('data.days.1.sehri_custom', false);
        $this->assertSame(1, $this->masjid->ramadanDayTimes()->count());
        $this->get("/msd/{$this->masjid->slug}/ramadan/1448")->assertSee('05:14')->assertSee('18:22');
        $this->get('/admin/ramadan/1448')->assertSee('1 day edited');

        $this->put('/admin/ramadan/1448/days', ['sehri' => [1 => '5:14'], 'iftar' => []])->assertSessionHasErrors('sehri.1');

        $this->delete('/admin/ramadan/1448/days')->assertSessionHasNoErrors();
        $this->assertSame(0, $this->masjid->ramadanDayTimes()->count());
        $this->getJson($api)->assertJsonPath('data.days.0.sehri_custom', false);
    }

    public function test_treasurer_cannot_edit_ramadan_times(): void
    {
        $treasurer = User::where('email', 'treasurer@masjid.test')->first();
        $this->actingAs($treasurer)->put('/admin/ramadan/1448/days', ['sehri' => [1 => '05:00']])->assertForbidden();
    }

    public function test_admin_changes_a_days_date_and_telugu_day_name(): void
    {
        $api = "/api/v1/masjids/{$this->masjid->slug}/ramadan/1448";
        $first = $this->getJson($api)->json('data.days.0');
        $moved = \Carbon\CarbonImmutable::parse($first['date'])->addDay()->toDateString();

        $this->actingAs($this->admin)->put('/admin/ramadan/1448/days', [
            'date' => [1 => $moved],
            'day_label' => [2 => 'మంగళ'],
        ])->assertSessionHasNoErrors();

        // A new date brings its own Telugu day name; a typed name is kept as written.
        $auto = \Carbon\CarbonImmutable::parse($moved)->locale('te')->dayName;
        $this->getJson($api)
            ->assertJsonPath('data.days.0.date', $moved)
            ->assertJsonPath('data.days.0.date_custom', true)
            ->assertJsonPath('data.days.0.weekday_te', $auto)
            ->assertJsonPath('data.days.0.day_label_custom', false)
            ->assertJsonPath('data.days.1.weekday_te', 'మంగళ')
            ->assertJsonPath('data.days.1.day_label_custom', true);
        $this->get("/msd/{$this->masjid->slug}/ramadan/1448")->assertSee('మంగళ');

        // Typing back the automatic name and date stores nothing.
        $this->put('/admin/ramadan/1448/days', [
            'date' => [1 => $first['date']],
            'day_label' => [2 => ''],
        ]);
        $this->assertSame(0, $this->masjid->ramadanDayTimes()->count());

        $this->put('/admin/ramadan/1448/days', ['date' => [1 => '08-02-2027']])->assertSessionHasErrors('date.1');
        $this->put('/admin/ramadan/1448/days', ['day_label' => [1 => 'Monday']])->assertSessionHasErrors('day_label.1');
    }

    public function test_admin_changes_one_days_adhan_and_iqamah(): void
    {
        $date = '2026-10-14';
        $api = "/api/v1/masjids/{$this->masjid->slug}/prayer-times?date={$date}";
        $calc = $this->getJson($api)->json('data');

        $this->actingAs($this->admin)->get("/admin/prayer-times?date={$date}")->assertOk()->assertSee('Save times');

        // Fajr adhan moves; its iqamah is left as shown, so it keeps following the rule (+20, rounded to 5).
        $this->put('/admin/prayer-times', [
            'date' => $date,
            'adhan' => ['fajr' => '05:00', 'isha' => $calc['times']['isha']],
            'iqamah' => ['fajr' => $calc['iqamah']['fajr'], 'isha' => '20:00'],
        ])->assertSessionHasNoErrors();

        $this->getJson($api)
            ->assertJsonPath('data.times.fajr', '05:00')
            ->assertJsonPath('data.iqamah.fajr', '05:20')
            ->assertJsonPath('data.times.isha', $calc['times']['isha'])
            ->assertJsonPath('data.iqamah.isha', '20:00');
        $this->assertNull($this->masjid->prayerTimeOverrides()->where('prayer', 'fajr')->value('iqamah'));

        // Saving the page again unchanged must not freeze the rule-based iqamah.
        $this->put('/admin/prayer-times', ['date' => $date, 'adhan' => ['fajr' => '05:00'], 'iqamah' => ['fajr' => '05:20']]);
        $this->assertNull($this->masjid->prayerTimeOverrides()->where('prayer', 'fajr')->value('iqamah'));
        $this->put('/admin/prayer-times', ['date' => $date, 'adhan' => ['fajr' => '05:30'], 'iqamah' => ['fajr' => '05:20']]);
        $this->getJson($api)->assertJsonPath('data.iqamah.fajr', '05:50');

        // Only this date changes.
        $this->getJson("/api/v1/masjids/{$this->masjid->slug}/prayer-times?date=2026-10-15")->assertJsonPath('data.times.fajr', fn ($t) => $t !== '05:30');

        $this->put('/admin/prayer-times', ['date' => $date, 'adhan' => ['fajr' => '5am']])->assertSessionHasErrors('adhan.fajr');

        $this->delete('/admin/prayer-times', ['date' => $date])->assertSessionHasNoErrors();
        $this->getJson($api)->assertJsonPath('data.times.fajr', $calc['times']['fajr']);
    }

    public function test_treasurer_cannot_change_prayer_times(): void
    {
        $treasurer = User::where('email', 'treasurer@masjid.test')->first();
        $this->actingAs($treasurer)->get('/admin/prayer-times')->assertForbidden();
        $this->actingAs($treasurer)->put('/admin/prayer-times', ['date' => '2026-10-14', 'adhan' => ['fajr' => '05:00']])->assertForbidden();
    }

    public function test_prayer_times_out_of_order_are_refused(): void
    {
        $this->actingAs($this->admin)->put('/admin/prayer-times', [
            'date' => '2026-10-14',
            'adhan' => ['dhuhr' => '01:00'],
            'iqamah' => ['dhuhr' => '01:30'],
        ])->assertSessionHasErrors(['adhan.dhuhr' => 'Dhuhr adhan (01:00) must be after Fajr (04:56). Did you mean 13:00?']);

        $this->put('/admin/prayer-times', [
            'date' => '2026-10-14', 'adhan' => ['asr' => '16:30'], 'iqamah' => ['asr' => '16:00'],
        ])->assertSessionHasErrors('iqamah.asr');
        $this->assertSame(0, $this->masjid->prayerTimeOverrides()->count());
    }

    public function test_admin_manages_committee_shown_on_public_page(): void
    {
        Storage::fake();
        $this->actingAs($this->admin)->post('/admin/committee', [
            'name' => 'Abdul Rahman', 'role' => 'president', 'phone' => '+91 98765 43210', 'show_phone' => '1', 'is_active' => '1',
            'photo' => UploadedFile::fake()->image('p.jpg', 200, 200),
        ])->assertSessionHasNoErrors();
        $this->post('/admin/committee', ['name' => 'Yusuf', 'role' => 'member', 'phone' => '12345', 'is_active' => '1'])->assertSessionHasNoErrors();
        $this->post('/admin/committee', ['name' => 'Hafiz Saab', 'role' => 'other', 'is_active' => '1'])->assertSessionHasErrors('title');
        $this->post('/admin/committee', ['name' => 'Hafiz Saab', 'role' => 'other', 'title' => 'Imam', 'is_active' => '1'])->assertSessionHasNoErrors();

        $president = $this->masjid->committeeMembers()->where('name', 'Abdul Rahman')->first();
        Storage::assertExists($president->photo_path);

        $page = "/msd/{$this->masjid->slug}/committee";
        $this->post('/admin/committee', ['name' => 'Haji Saab', 'role' => 'mutawalli', 'is_active' => '1'])->assertSessionHasNoErrors();

        // One table, mutawalli first, then office bearers, members and others.
        $this->get($page)->assertOk()
            ->assertSeeInOrder(['Haji Saab', 'Mutawalli', 'Abdul Rahman', 'President', '+91 98765 43210', 'Yusuf', 'Member', 'Hafiz Saab', 'Imam'])
            ->assertSee('<table>', false)
            ->assertDontSee('12345'); // phone not shown unless ticked
        $this->get($page.'?lang=ur')->assertSee('متولی')->assertSee('عہدہ');
        $this->get("/msd/{$this->masjid->slug}/committee/{$president->id}/photo")->assertOk();
        $this->get($page.'?lang=te')->assertSee('అధ్యక్షులు')->assertSee('మస్జిద్ కమిటీ');

        $yusuf = $this->masjid->committeeMembers()->where('name', 'Yusuf')->first();
        $this->put("/admin/committee/{$yusuf->id}", ['name' => 'Yusuf Ali', 'role' => 'vice_president', 'is_active' => '1'])->assertSessionHasNoErrors();
        $this->assertSame('vice_president', $yusuf->refresh()->role);
        $this->put("/admin/committee/{$yusuf->id}", ['name' => 'Yusuf Ali', 'role' => 'vice_president']); // hidden
        $this->get($page); // shows (and so clears) the "updated" message
        $this->get($page)->assertDontSee('Yusuf Ali');

        $this->delete("/admin/committee/{$president->id}")->assertSessionHasNoErrors();
        $this->assertModelMissing($president);
        Storage::assertMissing($president->photo_path);

        $treasurer = User::where('email', 'treasurer@masjid.test')->first();
        $this->actingAs($treasurer)->post('/admin/committee', ['name' => 'X', 'role' => 'member'])->assertForbidden();
        $other = Masjid::factory()->create()->committeeMembers()->create(['name' => 'Elsewhere', 'role' => 'member']);
        $this->actingAs($this->admin)->delete("/admin/committee/{$other->id}")->assertNotFound();
    }

    public function test_visitor_switches_language_and_it_is_remembered(): void
    {
        $home = "/msd/{$this->masjid->slug}";

        $this->get($home)->assertSee('<html lang="en" dir="ltr">', false)->assertSee('Sehri ends');
        $this->get($home.'?lang=te')->assertSee('<html lang="te" dir="ltr">', false)->assertSee('సహరీ ముగింపు')->assertSee('ఫజర్');
        $this->get($home)->assertSee('సహరీ ముగింపు'); // remembered for the next page
        $this->get($home.'?lang=ur')->assertSee('<html lang="ur" dir="rtl">', false)->assertSee('سحری کا اختتام')->assertSee('جمادی الاولیٰ');
        $this->get($home.'?lang=xx')->assertSee('dir="rtl"', false); // unknown language is ignored
        $this->get($home.'?lang=en')->assertSee('Sehri ends');

        // The API stays in English for the app.
        $this->getJson("/api/v1/masjids/{$this->masjid->slug}/prayer-times?date=2026-10-14")
            ->assertJsonPath('data.hijri.formatted', '3 Jumada al-Ula 1448 AH');
    }

    public function test_admin_sets_the_banner_photo_behind_the_masjid_name(): void
    {
        Storage::fake();
        $home = "/msd/{$this->masjid->slug}";
        $this->get($home)->assertDontSee('hero-photo', false);

        $this->actingAs($this->admin)->post('/admin/settings/banner', [
            'banner' => UploadedFile::fake()->image('masjid.jpg', 1200, 900), 'banner_position' => 'top',
        ])->assertSessionHasNoErrors();
        $path = $this->masjid->refresh()->banner_image_path;
        Storage::assertExists($path);
        $this->get($home)->assertSee('hero-photo', false)->assertSee('background-position: center top', false);
        $this->get("{$home}/banner")->assertOk();

        // Changing only the position keeps the photo.
        $this->post('/admin/settings/banner', ['banner_position' => 'bottom'])->assertSessionHasNoErrors();
        $this->assertSame($path, $this->masjid->refresh()->banner_image_path);
        $this->assertSame('bottom', $this->masjid->banner_position);

        $this->post('/admin/settings/banner', ['banner' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'), 'banner_position' => 'top'])
            ->assertSessionHasErrors('banner');

        $this->delete('/admin/settings/banner')->assertSessionHasNoErrors();
        Storage::assertMissing($path);
        $this->get("{$home}/banner")->assertNotFound();

        $treasurer = User::where('email', 'treasurer@masjid.test')->first();
        $this->actingAs($treasurer)->post('/admin/settings/banner', ['banner_position' => 'top'])->assertForbidden();
    }
}
