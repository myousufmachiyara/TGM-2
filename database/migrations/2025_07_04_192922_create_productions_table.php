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
        Schema::create('productions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');          // FK → vendors (CMT contractor or raw buyer)
            $table->unsignedBigInteger('category_id')->nullable();
            $table->date('order_date');
            $table->string('production_type');                 // 'cmt' | 'sell_raw'
            $table->text('remarks')->nullable();
            $table->json('attachments')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();
 
            $table->foreign('vendor_id')   ->references('id')->on('vendors')           ->onDelete('restrict');
            $table->foreign('category_id') ->references('id')->on('product_categories')->onDelete('set null');
            $table->foreign('created_by')  ->references('id')->on('users')             ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productions');
    }
};
