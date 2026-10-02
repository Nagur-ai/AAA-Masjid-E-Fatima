<?php

namespace App\Actions;

use App\Enums\FundType;
use App\Enums\IqamahMode;
use App\Enums\Prayer;
use App\Enums\Role;
use App\Enums\TransactionType;
use App\Models\Masjid;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Creates a masjid with its first admin and sensible defaults:
 * standard funds, ledger categories, iqamah offsets and one Jumuah session.
 */
class ProvisionMasjid
{
    public const FUNDS = [
        ['General', FundType::General, false, 'Running costs: utilities, maintenance, staff.'],
        ['Zakat', FundType::Zakat, true, 'Distributed only to eligible recipients.'],
        ['Sadaqah', FundType::Sadaqah, false, 'Voluntary charity for the community.'],
        ['Zakat al-Fitr', FundType::Fitrah, true, 'Collected before Eid al-Fitr for the poor.'],
        ['Ramadan Iftar', FundType::Iftar, false, 'Daily community iftar during Ramadan.'],
        ['Construction', FundType::Construction, false, 'Building and expansion projects.'],
    ];

    public const EXPENSE_CATEGORIES = [
        'Utilities', 'Imam & staff salaries', 'Maintenance & repairs', 'Cleaning',
        'Zakat distribution', 'Iftar & events', 'Education', 'Rent', 'Construction', 'Other expense',
    ];

    public const INCOME_CATEGORIES = [TransactionCategory::DONATIONS, 'Rent income', 'Event income', 'Other income'];

    /**
     * @param  array<string, mixed>  $attributes  Masjid columns
     * @param  array{name: string, email: string, password: string}  $admin
     */
    public function __invoke(array $attributes, array $admin): Masjid
    {
        return DB::transaction(function () use ($attributes, $admin) {
            $masjid = Masjid::create($attributes);

            foreach (self::FUNDS as [$name, $type, $restricted, $description]) {
                $masjid->funds()->create([
                    'name' => $name, 'type' => $type, 'is_restricted' => $restricted, 'description' => $description,
                ]);
            }

            foreach (self::EXPENSE_CATEGORIES as $name) {
                $masjid->categories()->create(['name' => $name, 'type' => TransactionType::Expense, 'is_system' => false]);
            }
            foreach (self::INCOME_CATEGORIES as $name) {
                $masjid->categories()->create([
                    'name' => $name, 'type' => TransactionType::Income, 'is_system' => $name === TransactionCategory::DONATIONS,
                ]);
            }

            $offsets = [Prayer::Fajr->value => 20, Prayer::Dhuhr->value => 15, Prayer::Asr->value => 15, Prayer::Maghrib->value => 5, Prayer::Isha->value => 15];
            foreach ($offsets as $prayer => $minutes) {
                $masjid->iqamahRules()->create([
                    'prayer' => $prayer,
                    'mode' => IqamahMode::Offset,
                    'offset_minutes' => $minutes,
                    'round_to_minutes' => $prayer === Prayer::Maghrib->value ? 0 : 5,
                    'effective_from' => now($masjid->timezone)->startOfYear()->toDateString(),
                ]);
            }

            $masjid->jumuahSessions()->create(['label' => 'Jumuah', 'khutbah_time' => '13:15', 'iqamah_time' => '13:45']);

            $user = new User(['name' => $admin['name'], 'email' => $admin['email'], 'password' => $admin['password']]);
            $user->role = Role::Admin;
            $user->masjid()->associate($masjid);
            $user->save();

            return $masjid;
        });
    }
}
