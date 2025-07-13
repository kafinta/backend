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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('value_for_money', 2, 1); // 1-5
            $table->decimal('true_to_description', 2, 1); // 1-5
            $table->decimal('product_quality', 2, 1); // 1-5
            $table->decimal('shipping', 2, 1); // 1-5
            $table->text('comment')->nullable();
            $table->json('images')->nullable();
            $table->unsignedBigInteger('parent_review_id')->nullable(); // for replies
            $table->enum('status', ['active', 'flagged', 'removed'])->default('active');
            $table->unsignedBigInteger('flagged_by')->nullable();
            $table->text('flag_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('parent_review_id')->references('id')->on('reviews')->onDelete('cascade');
            $table->foreign('flagged_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
