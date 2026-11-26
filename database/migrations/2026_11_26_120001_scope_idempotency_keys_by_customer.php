<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique('bookings_idempotency_key_unique');
            $table->unique(['customer_user_id', 'idempotency_key'], 'bookings_customer_idem_uq');
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropUnique('payment_transactions_idempotency_key_unique');
            $table->unique(['booking_id', 'customer_user_id', 'idempotency_key'], 'payment_tx_booking_cust_idem_uq');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropUnique('payment_tx_booking_cust_idem_uq');
            $table->unique('idempotency_key', 'payment_transactions_idempotency_key_unique');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique('bookings_customer_idem_uq');
            $table->unique('idempotency_key', 'bookings_idempotency_key_unique');
        });
    }
};
