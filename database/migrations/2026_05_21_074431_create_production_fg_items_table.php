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
        Schema::create('production_fg_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_id');
            $table->unsignedBigInteger('product_id');          // FG product (product_type = 'fg')
            $table->unsignedBigInteger('variation_id')->nullable();
            $table->decimal('qty',                15, 2)->default(0);
            $table->decimal('manufacturing_rate', 15, 2)->default(0); // CMT rate per piece
            $table->string('desc')->nullable();
            $table->timestamps();
 
            $table->foreign('production_id')->references('id')->on('productions')       ->onDelete('cascade');
            $table->foreign('product_id')  ->references('id')->on('products')           ->onDelete('restrict');
            $table->foreign('variation_id')->references('id')->on('product_variations') ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_fg_items');
    }
};
