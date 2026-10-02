<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Iqamah (congregation) times: a fixed clock time or N minutes after adhan,
        // effective over a date range so seasonal changes keep their history.
        Schema::create('iqamah_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('masjid_id')->constrained()->cascadeOnDelete();
            $table->string('prayer', 16);
            $table->string('mode', 16);
            $table->time('fixed_time')->nullable();
            $table->unsignedSmallInteger('offset_minutes')->nullable();
            $table->unsignedTinyInteger('round_to_minutes')->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->index(['masjid_id', 'prayer', 'effective_from']);
        });

        Schema::create('jumuah_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('masjid_id')->constrained()->cascadeOnDelete();
            $table->string('label')->default('Jumuah');
            $table->time('khutbah_time');
            $table->time('iqamah_time');
            $table->string('language', 32)->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // One row per Hijri year once the masjid announces (or estimates) the start of Ramadan.
        Schema::create('ramadan_calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('masjid_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('hijri_year');
            $table->date('starts_on');
            $table->unsignedTinyInteger('total_days')->default(30);
            $table->boolean('is_confirmed')->default(false);
            $table->time('taraweeh_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['masjid_id', 'hijri_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ramadan_calendars');
        Schema::dropIfExists('jumuah_sessions');
        Schema::dropIfExists('iqamah_rules');
    }
};
