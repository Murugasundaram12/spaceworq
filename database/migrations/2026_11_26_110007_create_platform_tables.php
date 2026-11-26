<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 150);
            $table->string('company_name', 180);
            $table->string('phone', 30);
            $table->string('email', 180)->nullable();
            $table->string('city_name', 120);
            $table->string('category_interest', 120)->nullable();
            $table->string('seats_range', 60)->nullable();
            $table->foreignId('workspace_id')->nullable()->constrained('workspaces')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->string('status', 32)->default('NEW');
            $table->timestamps();

            $table->index(['status', 'city_name'], 'consultations_status_city_idx');
        });

        Schema::create('partner_inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('inquiry_type', 40);
            $table->string('entity_name', 200);
            $table->string('city_name', 120);
            $table->string('locality', 180)->nullable();
            $table->unsignedInteger('total_desks')->nullable();
            $table->decimal('desk_price', 12, 2)->nullable();
            $table->unsignedInteger('total_area_sqft')->nullable();
            $table->unsignedInteger('floor_plate_sqft')->nullable();
            $table->string('partnership_model', 80)->nullable();
            $table->string('construction_stage', 80)->nullable();
            $table->string('expected_completion', 80)->nullable();
            $table->string('indicative_rent', 80)->nullable();
            $table->string('developer_brand', 180)->nullable();
            $table->string('contact_name', 150);
            $table->string('contact_phone', 30);
            $table->string('contact_email', 180);
            $table->string('status', 32)->default('NEW');
            $table->timestamps();

            $table->index(['inquiry_type', 'status'], 'partner_inquiries_type_status_idx');
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code', 40)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->string('category', 40);
            $table->string('subject', 200);
            $table->text('message');
            $table->string('status', 32)->default('OPEN');
            $table->foreignId('assigned_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status'], 'support_tickets_user_status_idx');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 60)->nullable();
            $table->foreignId('provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->string('action', 100);
            $table->string('auditable_type', 120)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['action', 'created_at'], 'audit_action_created_idx');
            $table->index(['provider_id', 'created_at'], 'audit_provider_created_idx');
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('value_type', 20)->default('string');
            $table->string('group', 40)->default('general');
            $table->string('description', 255)->nullable();
            $table->boolean('is_public')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('partner_inquiries');
        Schema::dropIfExists('consultations');
    }
};
