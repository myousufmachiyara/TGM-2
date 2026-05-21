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
        Schema::create('production_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_id');
            $table->unsignedBigInteger('product_id');          // raw product
            $table->unsignedBigInteger('variation_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable(); // source invoice
            $table->string('desc')->nullable();
            $table->decimal('rate', 15, 2)->default(0);
            $table->decimal('qty',  15, 2)->default(0);
            $table->unsignedBigInteger('unit');
            $table->timestamps();
 
            $table->foreign('production_id')->references('id')->on('productions')       ->onDelete('cascade');
            $table->foreign('product_id')  ->references('id')->on('products')           ->onDelete('restrict');
            $table->foreign('variation_id')->references('id')->on('product_variations') ->nullOnDelete();
            $table->foreign('invoice_id')  ->references('id')->on('purchase_invoices')  ->nullOnDelete();
            $table->foreign('unit')        ->references('id')->on('measurement_units')  ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_details');
    }
};
