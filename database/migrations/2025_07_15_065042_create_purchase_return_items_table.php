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
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_return_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('variation_id')->nullable();
            $table->unsignedBigInteger('purchase_invoice_id')->nullable();
            $table->string('item_name')->nullable();
            $table->decimal('quantity', 12, 3);
            $table->unsignedBigInteger('unit');
            $table->decimal('price', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
 
            $table->foreign('purchase_return_id') ->references('id')->on('purchase_returns')  ->onDelete('cascade');
            $table->foreign('item_id')            ->references('id')->on('products')           ->onDelete('restrict');
            $table->foreign('variation_id')       ->references('id')->on('product_variations') ->nullOnDelete();
            $table->foreign('purchase_invoice_id')->references('id')->on('purchase_invoices')  ->nullOnDelete();
            $table->foreign('unit')               ->references('id')->on('measurement_units')  ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
    }
};
