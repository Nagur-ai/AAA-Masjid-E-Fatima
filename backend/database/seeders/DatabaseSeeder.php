<?php

namespace Database\Seeders;

use App\Actions\ProvisionMasjid;
use App\Enums\Role;
use App\Enums\TransactionType;
use App\Models\User;
use App\Services\Finance\DonationService;
use App\Services\Finance\LedgerService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * A demo masjid with staff and a few weeks of donations and expenses.
     */
    public function run(ProvisionMasjid $provision, DonationService $donations, LedgerService $ledger): void
    {
        $masjid = $provision([
            'name' => 'Masjid-A-Fatima',
            'slug' => 'al-noor',
            'address' => 'మస్జిద్-ఎ-ఫాతిమా, కల్లూరు గ్రామం, కురిచేడు మండలం',
            'city' => 'ప్రకాశం జిల్లా - 523304',
            'country' => 'IN',
            'phone' => '+91 40 0000 0000',
            'latitude' => 17.3850,
            'longitude' => 78.4867,
            'elevation' => 0,
            'timezone' => 'Asia/Kolkata',
            'calculation_method' => 'karachi',
            'asr_method' => 'hanafi',
            'currency' => 'INR',
            'bank_details' => "Account: Masjid Al-Noor Trust\nA/C 000123456789 · IFSC DEMO0000123\nUPI: alnoor@demo",
        ], ['name' => 'Masjid Admin', 'email' => 'admin@masjid.test', 'password' => 'password']);

        $treasurer = new User(['name' => 'Treasurer', 'email' => 'treasurer@masjid.test', 'password' => 'password']);
        $treasurer->role = Role::Treasurer;
        $treasurer->masjid()->associate($masjid);
        $treasurer->save();

        $member = User::create(['name' => 'Demo Member', 'email' => 'member@masjid.test', 'password' => 'password']);

        $funds = $masjid->funds()->pluck('id', 'name');
        $donors = ['Abdul Rahman', 'Fatima Begum', 'Mohammed Irfan', 'Ayesha Siddiqui', 'Syed Imran', null];

        for ($i = 30; $i >= 1; $i -= 3) {
            $donations->recordReceived($masjid, [
                'fund_id' => $funds[$i % 2 ? 'General' : 'Zakat'],
                'amount' => 500 * (($i % 5) + 1),
                'payment_method' => $i % 3 ? 'cash' : 'upi',
                'donor_name' => $donors[$i % count($donors)],
                'is_anonymous' => $donors[$i % count($donors)] === null,
                'donated_at' => now($masjid->timezone)->subDays($i)->setTime(14, 0)->toDateTimeString(),
            ], $treasurer);
        }

        $donations->pledge($masjid, [
            'fund_id' => $funds['Construction'], 'amount' => 10000, 'payment_method' => 'bank_transfer',
            'payment_reference' => 'NEFT-DEMO-001',
        ], $member);

        $expense = fn (string $category, int $amount, int $daysAgo, string $description, ?string $fund = 'General') => $ledger->record($masjid, [
            'type' => TransactionType::Expense->value,
            'category_id' => $masjid->categories()->where('name', $category)->value('id'),
            'fund_id' => $fund ? $funds[$fund] : null,
            'amount' => $amount,
            'payment_method' => 'bank_transfer',
            'description' => $description,
            'transacted_on' => now($masjid->timezone)->subDays($daysAgo)->toDateString(),
        ], $treasurer);

        $expense('Utilities', 1200, 20, 'Electricity bill');
        $expense('Imam & staff salaries', 5000, 15, 'Imam salary');
        $expense('Zakat distribution', 3000, 5, 'Zakat to eligible family (ref Z-001)', 'Zakat');
    }
}
