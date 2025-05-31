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
        Schema::create('variants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 8, 2)->nullable();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            // Inventory fields
            $table->integer('stock_quantity')->default(0);
            $table->boolean('manage_stock')->default(true);

            $table->timestamps();

            // Add performance indexes
            $table->index('stock_quantity', 'idx_variants_stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variants');
    }
};
