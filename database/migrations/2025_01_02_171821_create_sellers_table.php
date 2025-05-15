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
        Schema::create('sellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('business_name');
            $table->text('business_description')->nullable();
            $table->string('business_address');
            $table->string('phone_number');
            $table->string('id_type')->comment('passport, national_id, nin');
            $table->string('id_number');
            $table->string('id_document')->nullable(); // For storing document file path
            $table->decimal('rating', 3, 2)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamp('profile_completed_at')->nullable();
            $table->timestamp('kyc_verified_at')->nullable();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->integer('onboarding_progress')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sellers');
    }
};
