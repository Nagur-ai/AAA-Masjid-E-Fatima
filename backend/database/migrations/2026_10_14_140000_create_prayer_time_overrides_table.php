<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adhan and iqamah times the admin has set by hand for one prayer on one date.
     * Empty means "use the calculated time" (adhan) or "use the iqamah rule" (iqamah).
     */
    public function up(): void
    {
        Schema::create('prayer_time_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('masjid_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('prayer', 10);
            $table->time('adhan')->nullable();
            $table->time('iqamah')->nullable();
            $table->timestamps();
            $table->unique(['masjid_id', 'date', 'prayer']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prayer_time_overrides');
    }
};
