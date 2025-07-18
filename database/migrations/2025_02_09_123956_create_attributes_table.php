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
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            // Removed 'type' column - simplified approach, all attributes are universal
            $table->boolean('is_variant_generator')->default(false);
            $table->text('help_text')->nullable(); // Help text for sellers
            $table->integer('sort_order')->default(0); // For ordering attributes
            $table->timestamps();

            // Add performance indexes
            $table->index('is_variant_generator', 'idx_attributes_variant_generator');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
