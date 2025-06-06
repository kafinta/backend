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
        Schema::create('subcategory_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subcategory_id')->constrained()->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained()->onDelete('cascade');
            $table->boolean('is_required')->default(false);
            $table->integer('display_order')->default(0);
            $table->integer('sort_order')->default(0); // Additional sorting within subcategory
            $table->text('help_text')->nullable(); // Subcategory-specific help text
            $table->timestamps();

            $table->unique(['subcategory_id', 'attribute_id'], 'subcat_attr_unique');

            // Add performance indexes
            $table->index(['subcategory_id', 'attribute_id'], 'idx_subcategory_attributes_lookup');
        });

        Schema::create('subcategory_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subcategory_id')->constrained()->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained()->onDelete('cascade');
            $table->foreignId('attribute_value_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['subcategory_id', 'attribute_id', 'attribute_value_id'], 'subcat_attr_val_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subcategory_attribute_values');
        Schema::dropIfExists('subcategory_attributes');
    }
};
