<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('company_name', 180)->nullable();
            $table->string('gstin', 32)->nullable();
            $table->foreignId('preferred_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->text('billing_address')->nullable();
            $table->timestamps();
        });

        Schema::create('user_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'workspace_id'], 'user_favorites_uq');
        });

        Schema::create('workspace_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('title', 160)->nullable();
            $table->text('comment')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(['workspace_id', 'user_id'], 'ws_reviews_user_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_reviews');
        Schema::dropIfExists('user_favorites');
        Schema::dropIfExists('customer_profiles');
    }
};
