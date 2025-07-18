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
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->json('representation')->nullable();
            $table->timestamps();

            // Ensure unique value within an attribute
            $table->unique(['attribute_id', 'name']);

            // Add performance indexes
            $table->index('attribute_id', 'idx_attribute_values_attribute');
            $table->index('name', 'idx_attribute_values_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
    }
};
