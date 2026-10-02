<?php

namespace App\Services\Finance;

use App\Models\Masjid;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Issues gap-free, per-masjid, per-year document numbers (e.g. RCP-2026-000042).
 * Must be called inside a database transaction so the row lock is held until commit.
 */
class SequenceGenerator
{
    public function next(Masjid $masjid, string $prefix, int $year): string
    {
        return sprintf('%s-%d-%06d', $prefix, $year, $this->increment($masjid->id, $prefix, $year));
    }

    private function increment(int $masjidId, string $key, int $year): int
    {
        $query = fn () => DB::table('sequences')
            ->where(['masjid_id' => $masjidId, 'key' => $key, 'year' => $year]);

        $current = $query()->lockForUpdate()->value('last_value');

        if ($current === null) {
            try {
                DB::table('sequences')->insert(['masjid_id' => $masjidId, 'key' => $key, 'year' => $year, 'last_value' => 1]);

                return 1;
            } catch (UniqueConstraintViolationException) {
                // Another request created the row first; fall through and take the lock.
                $current = $query()->lockForUpdate()->value('last_value');
            }
        }

        $query()->update(['last_value' => $current + 1]);

        return $current + 1;
    }
}
