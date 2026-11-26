<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->after('address');
            $table->text('status_reason')->nullable()->after('kyc_notes');
            $table->timestamp('status_updated_at')->nullable()->after('status_reason');
            $table->foreignId('status_updated_by')->nullable()->after('status_updated_at')->constrained('users')->nullOnDelete();

            $table->index(['status', 'submitted_at'], 'providers_status_submitted_idx');
        });

        Schema::table('workspace_reviews', function (Blueprint $table) {
            $table->foreignId('moderated_by')->nullable()->after('is_visible')->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable()->after('moderated_by');
            $table->text('moderation_notes')->nullable()->after('moderated_at');

            $table->index(['workspace_id', 'is_visible'], 'ws_reviews_visible_idx');
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->foreignId('handled_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable()->after('handled_by');
        });

        Schema::table('partner_inquiries', function (Blueprint $table) {
            $table->text('admin_notes')->nullable()->after('status');
            $table->foreignId('handled_by')->nullable()->after('admin_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable()->after('handled_by');
        });
    }

    public function down(): void
    {
        Schema::table('partner_inquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('handled_by');
            $table->dropColumn(['admin_notes', 'handled_at']);
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('handled_by');
            $table->dropColumn('handled_at');
        });

        Schema::table('workspace_reviews', function (Blueprint $table) {
            $table->dropIndex('ws_reviews_visible_idx');
            $table->dropConstrainedForeignId('moderated_by');
            $table->dropColumn(['moderated_at', 'moderation_notes']);
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->dropIndex('providers_status_submitted_idx');
            $table->dropConstrainedForeignId('status_updated_by');
            $table->dropColumn(['submitted_at', 'status_reason', 'status_updated_at']);
        });
    }
};
