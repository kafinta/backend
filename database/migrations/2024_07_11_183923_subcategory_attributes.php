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
            $table->foreignId('subcategory_id')->constrained();
            $table->foreignId('attribute_id')->constrained();
            $table->string('value');
            $table->timestamps();

            $table->unique(['subcategory_id', 'attribute_id', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subcategory_attributes');
    }
};
