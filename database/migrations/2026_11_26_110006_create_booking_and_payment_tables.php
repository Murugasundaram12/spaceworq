<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('title', 150);
            $table->string('discount_type', 20);
            $table->decimal('discount_value', 10, 2);
            $table->decimal('max_discount_amount', 10, 2)->nullable();
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->string('applicable_category_code', 50)->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_user_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('reserve_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_code', 40)->unique();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->foreignId('customer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_provider_user_id')->nullable()->constrained('provider_users')->nullOnDelete();
            $table->string('status', 32)->default('Open');
            $table->unsignedInteger('requested_seats')->nullable();
            $table->date('move_in_date')->nullable();
            $table->unsignedInteger('duration_months')->nullable();
            $table->text('request_summary');
            $table->text('remarks')->nullable();
            $table->unsignedInteger('reminders_count')->default(0);
            $table->timestamp('last_reminded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['provider_id', 'status'], 'rr_provider_status_idx');
            $table->index(['customer_user_id', 'workspace_id'], 'rr_customer_ws_idx');
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference', 40)->unique();
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->foreignId('workspace_id')->constrained('workspaces')->restrictOnDelete();
            $table->foreignId('provider_id')->constrained('providers')->restrictOnDelete();
            $table->foreignId('customer_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_provider_user_id')->nullable()->constrained('provider_users')->nullOnDelete();
            $table->string('booking_type', 32)->default('INSTANT_BOOKING');
            $table->date('booking_date');
            $table->string('slot', 32)->default('fullday');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('seat_count');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal_amount', 12, 2);
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->string('coupon_code', 50)->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('taxable_amount', 12, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->char('currency', 3)->default('INR');
            $table->string('status', 32)->default('PENDING_PAYMENT');
            $table->string('payment_status', 32)->default('UNPAID');
            $table->timestamp('hold_expires_at')->nullable();
            $table->string('contact_name', 150);
            $table->string('contact_email', 180);
            $table->string('contact_phone', 30);
            $table->text('remarks')->nullable();
            $table->string('checkin_token', 64)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'booking_date', 'status'], 'bookings_ws_date_status_idx');
            $table->index(['customer_user_id', 'status'], 'bookings_customer_status_idx');
            $table->index(['provider_id', 'status'], 'bookings_provider_status_idx');
        });

        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('workspace_listing_config_id')->nullable()->constrained('workspace_listing_configs')->nullOnDelete();
            $table->string('item_label', 180);
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });

        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->date('reservation_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('seats_reserved');
            $table->string('status', 24)->default('HELD');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'reservation_date', 'status'], 'inv_res_ws_date_status_idx');
        });

        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->decimal('discount_amount', 12, 2);
            $table->timestamps();

            $table->unique(['coupon_id', 'booking_id'], 'coupon_booking_uq');
            $table->index(['coupon_id', 'user_id'], 'coupon_user_idx');
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('customer_user_id')->constrained('users')->restrictOnDelete();
            $table->string('gateway', 40);
            $table->string('idempotency_key', 100)->unique();
            $table->string('gateway_order_id', 120)->nullable()->unique();
            $table->string('gateway_payment_id', 120)->nullable()->unique();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('INR');
            $table->string('status', 32)->default('CREATED');
            $table->text('failure_reason')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->json('gateway_payload')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'status'], 'payment_tx_booking_status_idx');
        });

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 40);
            $table->string('event_id', 120)->unique();
            $table->string('event_type', 80);
            $table->boolean('signature_verified')->default(false);
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->string('status', 32)->default('RECEIVED');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('cancellation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('cancelled_by_user_id')->constrained('users')->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->json('policy_snapshot')->nullable();
            $table->decimal('refundable_amount', 12, 2)->default(0);
            $table->timestamp('cancelled_at');
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('payment_transaction_id')->constrained('payment_transactions')->restrictOnDelete();
            $table->foreignId('cancellation_record_id')->nullable()->constrained('cancellation_records')->nullOnDelete();
            $table->string('gateway_refund_id', 120)->nullable()->unique();
            $table->decimal('amount', 12, 2);
            $table->string('status', 32)->default('PENDING');
            $table->text('reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('cancellation_records');
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('inventory_reservations');
        Schema::dropIfExists('booking_items');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('reserve_requests');
        Schema::dropIfExists('coupons');
    }
};
