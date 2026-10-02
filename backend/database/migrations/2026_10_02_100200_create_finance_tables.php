<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('masjid_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 16);
            $table->text('description')->nullable();
            $table->decimal('target_amount', 14, 2)->nullable();
            // Restricted funds (e.g. Zakat) may only be spent up to what they hold.
            $table->boolean('is_restricted')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['masjid_id', 'name']);
        });

        Schema::create('transaction_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('masjid_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 16);
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['masjid_id', 'type', 'name']);
        });

        // Gap-free per-masjid, per-year counters for receipt and voucher numbers.
        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('masjid_id')->constrained()->cascadeOnDelete();
            $table->string('key', 16);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_value')->default(0);

            $table->unique(['masjid_id', 'key', 'year']);
        });

        // The ledger. Posted rows are never edited or deleted; mistakes are voided.
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('masjid_id')->constrained()->cascadeOnDelete();
            $table->string('reference_no', 32);
            $table->string('type', 16);
            $table->foreignId('category_id')->constrained('transaction_categories');
            $table->foreignId('fund_id')->nullable()->constrained();
            $table->decimal('amount', 14, 2);
            $table->string('payment_method', 16);
            $table->string('party_name')->nullable();
            $table->string('description');
            $table->date('transacted_on');
            $table->string('status', 16)->default('posted');
            $table->nullableMorphs('source');
            $table->string('attachment_path')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->foreignId('voided_by')->nullable()->constrained('users');
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->timestamps();

            $table->unique(['masjid_id', 'reference_no']);
            $table->index(['masjid_id', 'status', 'transacted_on']);
        });

        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('masjid_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fund_id')->constrained();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('receipt_no', 32)->nullable();
            $table->string('donor_name')->nullable();
            $table->string('donor_email')->nullable();
            $table->string('donor_phone', 32)->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->decimal('amount', 14, 2);
            $table->char('currency', 3);
            $table->string('payment_method', 16);
            $table->string('payment_reference')->nullable();
            $table->string('status', 16)->default('pending');
            $table->timestamp('donated_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['masjid_id', 'receipt_no']);
            $table->index(['masjid_id', 'status', 'donated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('sequences');
        Schema::dropIfExists('transaction_categories');
        Schema::dropIfExists('funds');
    }
};
