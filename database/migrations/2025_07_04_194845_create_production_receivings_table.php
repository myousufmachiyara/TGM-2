<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('production_receivings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_id')->nullable(); // optional link to production order
            $table->unsignedBigInteger('vendor_id');                  // FK → vendors
            $table->date('rec_date');
            $table->string('grn_no')->unique()->nullable();           // auto-generated in model booted()
            $table->decimal('convance_charges', 12, 2)->default(0);
            $table->decimal('bill_discount',    12, 2)->default(0);
            $table->unsignedBigInteger('received_by');
            $table->timestamps();
            $table->softDeletes();
 
            $table->foreign('production_id')->references('id')->on('productions')  ->nullOnDelete();
            $table->foreign('vendor_id')    ->references('id')->on('vendors')       ->onDelete('restrict');
            $table->foreign('received_by')  ->references('id')->on('users')         ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_receivings');
    }
};
