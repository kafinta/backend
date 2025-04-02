<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['product_id', 'attribute_id']);
        });

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('attribute_value_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Prevent duplicate attribute values for the same product
            $table->unique(['product_id', 'attribute_value_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('product_attribute_values');
    }
};