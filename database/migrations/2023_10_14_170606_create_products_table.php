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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->decimal('price', 8, 2);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subcategory_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->enum('status', ['draft', 'active', 'paused', 'denied', 'out_of_stock'])->default('draft');
            $table->text('denial_reason')->nullable();
            $table->boolean('is_featured')->default(false);

            // Inventory fields
            $table->integer('stock_quantity')->default(0);
            $table->boolean('manage_stock')->default(true);

            $table->timestamps();

            // Add performance indexes
            $table->index('subcategory_id', 'idx_product_subcategory');
            $table->index('status', 'idx_products_status');
            $table->index('is_featured', 'idx_products_featured');
            $table->index('stock_quantity', 'idx_products_stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
