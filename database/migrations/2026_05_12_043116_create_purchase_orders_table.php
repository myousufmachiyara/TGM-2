<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();                          // e.g. PO-00001
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->date('order_date');
            $table->string('ordered_by')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('vendor_id')->references('id')->on('chart_of_accounts')->onDelete('restrict');
            $table->foreign('category_id')->references('id')->on('product_categories')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('width', 10, 2)->default(0);
            $table->string('description')->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('quantity', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
        });

        Schema::create('purchase_order_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');
            $table->string('file_path');
            $table->timestamps();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
        });

        Schema::create('purchase_order_receivings', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number')->unique();                         // e.g. GRN-00001
            $table->unsignedBigInteger('purchase_order_id');
            $table->date('received_date');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });

        Schema::create('purchase_order_receiving_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_receiving_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->timestamps();

            // FIX: explicit short name — auto-generated name exceeds MySQL's 64-char limit
            $table->foreign('purchase_order_receiving_id', 'po_rec_items_rec_id_fk')
                  ->references('id')->on('purchase_order_receivings')->onDelete('cascade');
            $table->foreign('product_id', 'po_rec_items_product_id_fk')
                  ->references('id')->on('products')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_receiving_items');
        Schema::dropIfExists('purchase_order_receivings');
        Schema::dropIfExists('purchase_order_attachments');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};