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
            $table->string('business_category')->nullable();
            $table->string('business_subcategory')->nullable();
            $table->integer('years_in_business')->nullable();
            $table->string('business_website')->nullable();
            $table->string('business_social_media')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_routing_number')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('paypal_email')->nullable();
            $table->timestamp('payment_info_completed_at')->nullable();
            $table->string('phone_number');
            $table->string('id_type')->comment('passport, national_id, nin')->nullable()->default(null);
            $table->string('id_number')->nullable();
            $table->string('id_document')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamp('profile_completed_at')->nullable();
            $table->timestamp('kyc_verified_at')->nullable();
            $table->boolean('agreement_accepted')->default(false);
            $table->timestamp('agreement_accepted_at')->nullable();
            $table->timestamp('agreement_completed_at')->nullable();
            $table->string('agreement_version')->nullable();
            $table->string('agreement_ip_address')->nullable();
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
