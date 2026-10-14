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
        $page = "/m/{$this->masjid->slug}/ramadan";

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
        $this->get("/m/{$this->masjid->slug}/ramadan-image")->assertOk();
        $this->get("/m/{$this->masjid->slug}/ramadan")->assertSee('class="timetable-image"', false);

        $this->delete('/admin/ramadan/image')->assertSessionHasNoErrors();
        Storage::assertMissing($path);
        $this->assertNull($this->masjid->refresh()->ramadan_image_path);
        $this->get("/m/{$this->masjid->slug}/ramadan-image")->assertNotFound();
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
}
