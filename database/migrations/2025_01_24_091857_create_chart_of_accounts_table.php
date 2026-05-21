<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_code', 20)->unique();
            $table->unsignedBigInteger('shoa_id');
            $table->string('name');
            $table->string('trn')->nullable();           // Tax Registration Number
            $table->string('account_type')->nullable();

            // ── Financial opening balances ─────────────────────────
            $table->decimal('receivables', 15, 2)->default(0);
            $table->decimal('payables', 15, 2)->default(0);

            // ── Credit terms ───────────────────────────────────────
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->unsignedSmallInteger('credit_days')->default(0); // payment due days

            // ── Party details ──────────────────────────────────────
            $table->date('opening_date');
            $table->string('remarks')->nullable();
            $table->string('address')->nullable();
            $table->string('contact_no')->nullable();

            // ── Audit ──────────────────────────────────────────────
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
            $table->softDeletes();

            // ── Foreign keys ───────────────────────────────────────
            $table->foreign('shoa_id')->references('id')->on('sub_head_of_accounts')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};