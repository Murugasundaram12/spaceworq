<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->restrictOnDelete();
            $table->string('company_name', 180);
            $table->string('slug', 180)->unique();
            $table->string('brand_name', 180)->nullable();
            $table->string('business_email', 180);
            $table->string('business_phone', 30)->nullable();
            $table->string('gstin', 32)->nullable();
            $table->string('pan', 20)->nullable();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->text('address')->nullable();
            $table->string('status', 32)->default('PENDING_VERIFICATION');
            $table->string('kyc_status', 32)->default('NOT_SUBMITTED');
            $table->text('kyc_notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'kyc_status'], 'providers_status_kyc_idx');
        });

        Schema::create('provider_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->string('document_type', 60);
            $table->string('original_filename', 255);
            $table->string('disk', 40)->default('local');
            $table->string('storage_path', 500);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->string('status', 32)->default('PENDING');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['provider_id', 'status'], 'provider_docs_status_idx');
        });

        Schema::create('provider_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->string('account_holder_name', 180);
            $table->string('bank_name', 180);
            $table->text('account_number_encrypted');
            $table->string('account_last4', 4);
            $table->string('ifsc_code', 20);
            $table->string('upi_id', 120)->nullable();
            $table->boolean('is_primary')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->index(['provider_id', 'is_primary'], 'provider_bank_primary_idx');
        });

        Schema::create('provider_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->unique()->constrained('providers')->cascadeOnDelete();
            $table->boolean('auto_accept_instant_bookings')->default(false);
            $table->string('notification_email', 180)->nullable();
            $table->boolean('gst_registered')->default(false);
            $table->decimal('default_tax_rate', 5, 2)->nullable();
            $table->string('timezone', 60)->default('Asia/Kolkata');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_settings');
        Schema::dropIfExists('provider_bank_accounts');
        Schema::dropIfExists('provider_documents');
        Schema::dropIfExists('providers');
    }
};
