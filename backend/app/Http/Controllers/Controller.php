<?php

namespace App\Http\Controllers;

use App\Models\Masjid;
use Illuminate\Database\Eloquent\Model;

abstract class Controller
{
    /**
     * The signed-in staff member's masjid (set by the `staff` middleware).
     */
    protected function masjid(): Masjid
    {
        return request()->attributes->get('masjid');
    }

    /**
     * Web admin routes bind models globally by id; refuse records of other masjids.
     */
    protected function ensureOwned(Model $model): void
    {
        abort_unless((int) $model->getAttribute('masjid_id') === $this->masjid()->id, 404);
    }
}
