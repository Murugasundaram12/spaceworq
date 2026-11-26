<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->string('state', 120)->nullable();
            $table->string('country', 100)->default('India');
            $table->string('image_path', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'is_featured', 'sort_order'], 'cities_active_featured_idx');
        });

        Schema::create('localities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('slug', 150);
            $table->string('pincode', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['city_id', 'slug'], 'localities_city_slug_uq');
            $table->index(['city_id', 'is_active'], 'localities_city_active_idx');
        });

        Schema::create('workspace_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 120);
            $table->string('subtitle', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('badge_label', 60)->nullable();
            $table->string('image_path', 255)->nullable();
            $table->string('default_booking_mode', 32)->default('reserve_request');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('workspace_subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('workspace_categories')->cascadeOnDelete();
            $table->string('code', 60)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('price_unit_label', 120)->nullable();
            $table->boolean('supports_upcoming')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category_id', 'is_active'], 'subcategories_cat_active_idx');
        });

        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->string('icon_key', 60)->nullable();
            $table->string('category', 60)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amenities');
        Schema::dropIfExists('workspace_subcategories');
        Schema::dropIfExists('workspace_categories');
        Schema::dropIfExists('localities');
        Schema::dropIfExists('cities');
    }
};
