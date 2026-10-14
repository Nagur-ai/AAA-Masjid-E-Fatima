<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The masjid committee (president, secretary, members…), shown on the public site.
     */
    public function up(): void
    {
        Schema::create('committee_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('masjid_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('role', 20)->default('member');
            $table->string('title', 80)->nullable(); // shown instead of the role, e.g. for "other"
            $table->string('phone', 32)->nullable();
            $table->boolean('show_phone')->default(false);
            $table->string('photo_path')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['masjid_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_members');
    }
};
