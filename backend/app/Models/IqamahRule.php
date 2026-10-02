<?php

namespace App\Models;

use App\Enums\IqamahMode;
use App\Enums\Prayer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['prayer', 'mode', 'fixed_time', 'offset_minutes', 'round_to_minutes', 'effective_from', 'effective_to'])]
class IqamahRule extends Model
{
    protected function casts(): array
    {
        return [
            'prayer' => Prayer::class,
            'mode' => IqamahMode::class,
            'offset_minutes' => 'integer',
            'round_to_minutes' => 'integer',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }

    /**
     * Resolve the iqamah time for a given adhan time.
     */
    public function resolve(CarbonImmutable $adhan): CarbonImmutable
    {
        if ($this->mode === IqamahMode::Fixed) {
            [$h, $m] = array_map('intval', explode(':', $this->fixed_time));

            return $adhan->setTime($h, $m);
        }

        $time = $adhan->addMinutes($this->offset_minutes ?? 0);

        if ($this->round_to_minutes > 1) {
            $remainder = $time->minute % $this->round_to_minutes;
            if ($remainder !== 0) {
                $time = $time->addMinutes($this->round_to_minutes - $remainder);
            }
        }

        return $time;
    }
}
