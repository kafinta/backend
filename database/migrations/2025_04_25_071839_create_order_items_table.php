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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->decimal('subtotal', 10, 2);

            // Store product and variant names in case they change later
            $table->string('product_name');
            $table->string('variant_name')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
