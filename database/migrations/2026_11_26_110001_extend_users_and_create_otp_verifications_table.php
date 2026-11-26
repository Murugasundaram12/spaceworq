<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 100)->nullable()->after('id');
            $table->string('last_name', 100)->nullable()->after('first_name');
            $table->string('mobile', 30)->nullable()->unique()->after('email');
            $table->string('username', 100)->nullable()->unique()->after('mobile');
            $table->string('user_type', 32)->default('customer')->after('username');
            $table->string('status', 32)->default('ACTIVE')->after('user_type');
            $table->softDeletes();

            $table->index(['user_type', 'status'], 'users_type_status_idx');
        });

        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel', 20);
            $table->string('identifier', 150);
            $table->string('otp_hash');
            $table->string('purpose', 40)->default('login');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['identifier', 'purpose'], 'otp_identifier_purpose_idx');
        });

        Schema::create('activation_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('type', 40);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type'], 'activation_user_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activation_tokens');
        Schema::dropIfExists('otp_verifications');
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_type_status_idx');
            $table->dropSoftDeletes();
            $table->dropColumn(['first_name', 'last_name', 'mobile', 'username', 'user_type', 'status']);
        });
    }
};
