<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Donation;
use App\Models\Masjid;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_pages_render(): void
    {
        $this->get('/')->assertRedirect('/msd/klr');

        foreach (['/msd/klr', '/msd/klr/timetable', '/msd/klr/timetable?month=2027-02', '/msd/klr/ramadan', '/msd/klr/ramadan/1448', '/msd/klr/donate'] as $url) {
            $this->get($url)->assertOk()->assertSee('Masjid-A-Fatima');
        }
    }

    public function test_public_pledge_shows_thank_you(): void
    {
        $fund = Masjid::first()->funds()->first();

        $this->post('/msd/klr/donate', [
            'fund_id' => $fund->id, 'amount' => 500, 'payment_method' => 'upi', 'donor_name' => 'Yusuf', 'donor_phone' => '9999999999',
        ])->assertRedirect('/msd/klr/donate');

        $this->followingRedirects()->get('/msd/klr/donate')->assertOk();
        $this->assertDatabaseHas('donations', ['donor_name' => 'Yusuf', 'status' => 'pending']);
    }

    public function test_admin_pages_render_for_admin(): void
    {
        $admin = User::where('email', 'admin@masjid.test')->first();
        $receipt = Donation::whereNotNull('receipt_no')->first();

        foreach (['/admin', '/admin/donations', '/admin/donations/create', "/admin/donations/{$receipt->id}/receipt",
            '/admin/transactions', '/admin/transactions/create?type=expense', '/admin/reports', '/admin/funds',
            '/admin/settings', '/admin/ramadan', '/admin/users'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $this->actingAs($admin)->get('/admin/reports/export')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_staff_login_and_role_limits(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->post('/admin/login', ['email' => 'treasurer@masjid.test', 'password' => 'password'])->assertRedirect('/admin');

        $this->get('/admin/donations')->assertOk();
        $this->get('/admin/settings')->assertForbidden();

        $member = User::where('email', 'member@masjid.test')->first();
        $this->actingAs($member)->get('/admin')->assertRedirect('/account');
    }

    public function test_dashboard_panels_follow_the_role(): void
    {
        $admin = User::where('email', 'admin@masjid.test')->first();
        $treasurer = User::where('email', 'treasurer@masjid.test')->first();

        $this->actingAs($admin)->get('/admin')->assertOk()
            ->assertSee('Fund balances')->assertSee('Latest expenses')->assertSee('Pledges waiting for payment')
            ->assertSee("Today's prayers", false)->assertSee('Manage Ramadan')->assertSee('treasurer@masjid.test')
            ->assertSee('రమజాన్ క్యాలెండర్')->assertSee('ఇఫ్తార్ దుఆ')->assertSee('సోమవారం')
            ->assertSee('<tr class="friday">', false)->assertDontSee('images/ramadan-timetable.jpg')->assertSee('Jumuah')->assertDontSee('dua-iftar_after');

        $this->actingAs($treasurer)->get('/admin')->assertOk()
            ->assertSee('Fund balances')->assertSee('Latest expenses')->assertSee('Pledges waiting for payment')
            ->assertDontSee("Today's prayers", false)->assertDontSee('Manage Ramadan')->assertDontSee('admin@masjid.test')
            ->assertDontSee('రమజాన్ క్యాలెండర్')
            ->assertDontSee(route('admin.settings.edit'));
    }

    public function test_sign_in_sends_each_role_to_its_own_home(): void
    {
        $this->post('/admin/login', ['email' => 'admin@masjid.test', 'password' => 'password'])->assertRedirect('/admin');
        $this->post('/admin/logout');
        $this->post('/admin/login', ['email' => 'member@masjid.test', 'password' => 'password'])->assertRedirect('/account');
    }

    public function test_member_sees_only_their_own_donations_and_receipts(): void
    {
        $member = User::where('email', 'member@masjid.test')->first();
        $someoneElses = Donation::whereNotNull('receipt_no')->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', '!=', $member->id))->first();

        $this->actingAs($member)->get('/account')->assertOk()
            ->assertSee('Construction')->assertSee('10,000.00')->assertSee('Waiting for confirmation')
            ->assertDontSee((string) $someoneElses->receipt_no);

        $this->actingAs($member)->get("/account/donations/{$someoneElses->id}/receipt")->assertNotFound();

        $mine = $member->donations()->first();
        $mine->forceFill(['receipt_no' => 'RCP-TEST-1', 'status' => 'completed', 'donated_at' => now()])->save();
        $this->actingAs($member)->get("/account/donations/{$mine->id}/receipt")->assertOk()->assertSee('RCP-TEST-1');
    }

    public function test_admin_records_donation_and_expense_from_web(): void
    {
        $masjid = Masjid::first();
        $admin = User::where('email', 'admin@masjid.test')->first();
        $general = $masjid->funds()->where('name', 'General')->first();
        $utilities = $masjid->categories()->where('name', 'Utilities')->first();

        $this->actingAs($admin)->post('/admin/donations', [
            'fund_id' => $general->id, 'amount' => 1000, 'payment_method' => 'cash', 'donor_name' => 'Walk-in',
        ])->assertRedirect();

        $this->actingAs($admin)->post('/admin/transactions', [
            'type' => 'expense', 'category_id' => $utilities->id, 'fund_id' => $general->id, 'amount' => 300,
            'payment_method' => 'cash', 'description' => 'Water bill', 'transacted_on' => now($masjid->timezone)->toDateString(),
        ])->assertRedirect('/admin/transactions');

        $this->assertDatabaseHas('donations', ['donor_name' => 'Walk-in', 'status' => 'completed']);
        $this->assertDatabaseHas('transactions', ['description' => 'Water bill', 'type' => 'expense']);
    }

    public function test_admin_cannot_demote_themselves(): void
    {
        $admin = User::where('email', 'admin@masjid.test')->first();

        $this->actingAs($admin)->put("/admin/users/{$admin->id}", ['role' => Role::Member->value])->assertSessionHasErrors('role');
        $this->assertSame(Role::Admin, $admin->fresh()->role);
    }
}
