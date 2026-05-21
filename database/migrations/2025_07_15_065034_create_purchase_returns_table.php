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
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->date('return_date');
            $table->string('return_no')->nullable()->unique();
            $table->string('ref_no')->nullable();
            $table->string('bill_no')->nullable();
            $table->text('remarks')->nullable();
            $table->decimal('convance_charges', 12, 2)->default(0);
            $table->decimal('bill_discount',    12, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
 
            $table->foreign('vendor_id') ->references('id')->on('vendors')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
