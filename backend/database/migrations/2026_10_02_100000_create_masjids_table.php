<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('masjids', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->decimal('latitude', 9, 6);
            $table->decimal('longitude', 9, 6);
            $table->unsignedSmallInteger('elevation')->default(0);
            $table->string('timezone', 64);
            $table->string('calculation_method', 32)->default('mwl');
            $table->string('asr_method', 16)->default('standard');
            $table->string('high_latitude_rule', 16)->default('angle_based');
            $table->tinyInteger('hijri_adjustment')->default(0);
            $table->unsignedTinyInteger('imsak_minutes_before_fajr')->default(10);
            $table->char('currency', 3)->default('USD');
            $table->text('bank_details')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('masjid_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('phone', 32)->nullable()->after('email');
            $table->string('role', 16)->default('member')->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('masjid_id');
            $table->dropColumn(['phone', 'role']);
        });

        Schema::dropIfExists('masjids');
    }
};
