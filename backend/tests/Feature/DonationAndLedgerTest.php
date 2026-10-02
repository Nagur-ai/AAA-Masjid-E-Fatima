<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TransactionType;
use App\Models\Fund;
use App\Models\Masjid;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonationAndLedgerTest extends TestCase
{
    use RefreshDatabase;

    private Masjid $masjid;

    private Fund $general;

    private Fund $zakat;

    private User $treasurer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->masjid = Masjid::factory()->create(['slug' => 'test-masjid']);
        $this->general = Fund::factory()->for($this->masjid)->create(['name' => 'General']);
        $this->zakat = Fund::factory()->for($this->masjid)->restricted()->create(['name' => 'Zakat']);
        $this->treasurer = User::factory()->staffOf($this->masjid, Role::Treasurer)->create();
    }

    private function url(string $path = ''): string
    {
        return '/api/v1/masjids/test-masjid'.$path;
    }

    public function test_guest_pledge_is_pending_until_treasurer_confirms_it(): void
    {
        $pledge = $this->postJson($this->url('/donations'), [
            'fund_id' => $this->general->id, 'amount' => '250.50', 'payment_method' => 'bank_transfer',
            'donor_name' => 'Aisha', 'donor_phone' => '+44 7000 000000',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.receipt_no', null)
            ->assertJsonPath('data.currency', 'GBP');

        $this->assertDatabaseCount('transactions', 0);

        $id = $pledge->json('data.id');
        $this->actingAs($this->treasurer)
            ->postJson($this->url("/admin/donations/{$id}/complete"), ['payment_reference' => 'BANK-123'])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.receipt_no', 'RCP-'.now()->year.'-000001')
            ->assertJsonPath('data.payment_reference', 'BANK-123');

        $this->assertDatabaseHas('transactions', [
            'type' => 'income', 'amount' => '250.50', 'fund_id' => $this->general->id, 'source_type' => 'donation', 'source_id' => $id,
        ]);

        // Completing twice must not post income twice.
        $this->actingAs($this->treasurer)->postJson($this->url("/admin/donations/{$id}/complete"))->assertUnprocessable();
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_guest_must_leave_contact_details(): void
    {
        $this->postJson($this->url('/donations'), ['fund_id' => $this->general->id, 'amount' => 10, 'payment_method' => 'upi'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['donor_name', 'donor_phone']);
    }

    public function test_signed_in_member_sees_their_donations(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->postJson($this->url('/donations'), [
            'fund_id' => $this->general->id, 'amount' => 20, 'payment_method' => 'card',
        ])->assertCreated()->assertJsonPath('data.donor_name', $member->name);

        $this->actingAs($member)->getJson('/api/v1/me/donations')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.masjid.slug', 'test-masjid');
    }

    public function test_receipts_are_numbered_sequentially(): void
    {
        foreach ([1, 2, 3] as $n) {
            $this->actingAs($this->treasurer)->postJson($this->url('/admin/donations'), [
                'fund_id' => $this->general->id, 'amount' => 100, 'payment_method' => 'cash',
            ])->assertCreated()->assertJsonPath('data.receipt_no', sprintf('RCP-%d-%06d', now()->year, $n));
        }
    }

    public function test_restricted_fund_cannot_be_overspent(): void
    {
        $this->actingAs($this->treasurer)->postJson($this->url('/admin/donations'), [
            'fund_id' => $this->zakat->id, 'amount' => 100, 'payment_method' => 'cash',
        ])->assertCreated();

        $category = $this->masjid->categories()->create(['name' => 'Zakat distribution', 'type' => TransactionType::Expense]);
        $expense = fn ($amount) => $this->actingAs($this->treasurer)->postJson($this->url('/admin/transactions'), [
            'type' => 'expense', 'category_id' => $category->id, 'fund_id' => $this->zakat->id, 'amount' => $amount,
            'payment_method' => 'cash', 'description' => 'Zakat to family', 'transacted_on' => now()->toDateString(),
        ]);

        $expense('100.01')->assertUnprocessable()->assertJsonValidationErrors('amount');
        $expense('100.00')->assertCreated();

        $this->actingAs($this->treasurer)->getJson($this->url('/admin/reports/summary'))
            ->assertOk()
            ->assertJsonPath('data.income', '100.00')
            ->assertJsonPath('data.expense', '100.00')
            ->assertJsonPath('data.funds.1.name', 'Zakat')
            ->assertJsonPath('data.funds.1.balance', '0.00');
    }

    public function test_refund_voids_the_ledger_entry_and_direct_void_is_refused(): void
    {
        $donation = $this->actingAs($this->treasurer)->postJson($this->url('/admin/donations'), [
            'fund_id' => $this->general->id, 'amount' => 75, 'payment_method' => 'cash',
        ])->json('data');

        $transactionId = $this->masjid->transactions()->value('id');
        $this->actingAs($this->treasurer)
            ->postJson($this->url("/admin/transactions/{$transactionId}/void"), ['reason' => 'mistake'])
            ->assertUnprocessable();

        $this->actingAs($this->treasurer)
            ->postJson($this->url("/admin/donations/{$donation['id']}/refund"), ['reason' => 'Donor request'])
            ->assertOk()->assertJsonPath('data.status', 'refunded');

        $this->assertDatabaseHas('transactions', ['id' => $transactionId, 'status' => 'voided']);
        $this->actingAs($this->treasurer)->getJson($this->url('/admin/reports/summary'))->assertJsonPath('data.income', '0.00');
    }

    public function test_expense_must_use_an_expense_category_of_this_masjid(): void
    {
        $donations = $this->masjid->categories()->create(['name' => TransactionCategory::DONATIONS, 'type' => TransactionType::Income]);
        $foreign = Masjid::factory()->create()->categories()->create(['name' => 'Utilities', 'type' => TransactionType::Expense]);

        foreach ([$donations->id, $foreign->id] as $categoryId) {
            $this->actingAs($this->treasurer)->postJson($this->url('/admin/transactions'), [
                'type' => 'expense', 'category_id' => $categoryId, 'amount' => 5, 'payment_method' => 'cash',
                'description' => 'Bulbs', 'transacted_on' => now()->toDateString(),
            ])->assertUnprocessable()->assertJsonValidationErrors('category_id');
        }
    }

    public function test_finance_endpoints_are_restricted_to_this_masjids_staff(): void
    {
        $member = User::factory()->create();
        $otherTreasurer = User::factory()->staffOf(Masjid::factory()->create(), Role::Treasurer)->create();

        $this->getJson($this->url('/admin/donations'))->assertUnauthorized();
        $this->actingAs($member)->getJson($this->url('/admin/donations'))->assertForbidden();
        $this->actingAs($otherTreasurer)->getJson($this->url('/admin/donations'))->assertForbidden();
        $this->actingAs($this->treasurer)->getJson($this->url('/admin/donations'))->assertOk();
    }

    public function test_donations_of_another_masjid_are_not_reachable(): void
    {
        $other = Masjid::factory()->create(['slug' => 'other']);
        $otherFund = Fund::factory()->for($other)->create();
        $otherAdmin = User::factory()->staffOf($other, Role::Treasurer)->create();
        $foreignDonation = $this->actingAs($otherAdmin)->postJson('/api/v1/masjids/other/admin/donations', [
            'fund_id' => $otherFund->id, 'amount' => 10, 'payment_method' => 'cash',
        ])->json('data.id');

        $this->actingAs($this->treasurer)->getJson($this->url("/admin/donations/{$foreignDonation}"))->assertNotFound();

        // Nor can a fund of another masjid be used.
        $this->actingAs($this->treasurer)->postJson($this->url('/admin/donations'), [
            'fund_id' => $otherFund->id, 'amount' => 10, 'payment_method' => 'cash',
        ])->assertUnprocessable()->assertJsonValidationErrors('fund_id');
    }

    public function test_mobile_login_issues_a_token(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com', 'password' => 'password', 'device_name' => 'Pixel',
        ])->assertOk()->json('token');

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.email', 'user@example.com')
            ->assertJsonPath('data.role', 'member');
    }
}
