<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->string('code', 40)->unique();
            $table->string('slug', 200)->unique();
            $table->string('name', 200);
            $table->string('generated_post_title', 255)->nullable();
            $table->foreignId('category_id')->constrained('workspace_categories')->restrictOnDelete();
            $table->foreignId('subcategory_id')->constrained('workspace_subcategories')->restrictOnDelete();
            $table->foreignId('city_id')->constrained('cities')->restrictOnDelete();
            $table->foreignId('locality_id')->nullable()->constrained('localities')->nullOnDelete();
            $table->string('country', 100)->default('India');
            $table->text('address_line');
            $table->string('landmark', 200)->nullable();
            $table->string('pincode', 20)->nullable();
            $table->string('place_id', 150)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->longText('description_html')->nullable();
            $table->unsignedSmallInteger('total_floors')->default(1);
            $table->unsignedInteger('total_seats')->default(0);
            $table->unsignedInteger('available_seats')->default(0);
            $table->boolean('is_upcoming')->default(false);
            $table->date('tentative_availability_date')->nullable();
            $table->boolean('gst_enabled')->default(false);
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->string('status', 32)->default('DRAFT');
            $table->string('approval_status', 32)->default('PENDING');
            $table->boolean('is_published')->default(false);
            $table->boolean('is_instant_bookable')->default(false);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_published', 'status', 'approval_status'], 'ws_visibility_idx');
            $table->index(['city_id', 'category_id'], 'ws_city_cat_idx');
            $table->index(['provider_id', 'status'], 'ws_provider_status_idx');
        });

        Schema::create('provider_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->foreignId('workspace_id')->nullable()->constrained('workspaces')->cascadeOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->json('permissions');
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['provider_id', 'workspace_id', 'name'], 'provider_roles_scope_name_uq');
        });

        Schema::create('provider_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('workspace_id')->nullable()->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('provider_role_id')->nullable()->constrained('provider_roles')->nullOnDelete();
            $table->string('role_name', 120);
            $table->boolean('is_owner')->default(false);
            $table->string('status', 20)->default('ACTIVE');
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['provider_id', 'user_id', 'workspace_id'], 'provider_user_workspace_uq');
            $table->index(['user_id', 'status'], 'provider_users_user_status_idx');
        });

        Schema::create('workspace_listing_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('subcategory_id')->constrained('workspace_subcategories')->restrictOnDelete();
            $table->decimal('price_per_day', 12, 2)->nullable();
            $table->decimal('price_per_month', 12, 2)->nullable();
            $table->decimal('price_per_hour', 12, 2)->nullable();
            $table->decimal('price_per_half_day', 12, 2)->nullable();
            $table->decimal('price_per_sqft', 12, 2)->nullable();
            $table->decimal('price_per_year', 12, 2)->nullable();
            $table->unsignedInteger('min_seats')->default(1);
            $table->unsignedInteger('max_seats')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['workspace_id', 'subcategory_id'], 'ws_listing_config_uq');
        });

        Schema::create('workspace_working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->string('day_name', 16);
            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->unique(['workspace_id', 'day_of_week'], 'ws_working_hours_uq');
        });

        Schema::create('workspace_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('disk', 40)->default('local');
            $table->string('storage_path', 500);
            $table->string('original_filename', 255);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['workspace_id', 'sort_order'], 'ws_images_order_idx');
        });

        Schema::create('workspace_amenities', function (Blueprint $table) {
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained('amenities')->cascadeOnDelete();
            $table->primary(['workspace_id', 'amenity_id']);
        });

        Schema::create('workspace_floors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->unsignedSmallInteger('floor_number');
            $table->string('label', 80);
            $table->unsignedInteger('total_seats')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['workspace_id', 'floor_number'], 'ws_floors_num_uq');
        });

        Schema::create('layout_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->json('elements');
            $table->unsignedInteger('elements_count')->default(0);
            $table->unsignedInteger('total_seats')->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['provider_id', 'created_at'], 'layout_templates_provider_idx');
        });

        Schema::create('floor_template_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('workspace_floor_id')->unique()->constrained('workspace_floors')->cascadeOnDelete();
            $table->foreignId('layout_template_id')->constrained('layout_templates')->cascadeOnDelete();
            $table->foreignId('mapped_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['provider_id', 'workspace_id'], 'floor_tpl_map_provider_ws_idx');
        });

        Schema::create('workspace_approval_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'created_at'], 'ws_approval_hist_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_approval_histories');
        Schema::dropIfExists('floor_template_mappings');
        Schema::dropIfExists('layout_templates');
        Schema::dropIfExists('workspace_floors');
        Schema::dropIfExists('workspace_amenities');
        Schema::dropIfExists('workspace_images');
        Schema::dropIfExists('workspace_working_hours');
        Schema::dropIfExists('workspace_listing_configs');
        Schema::dropIfExists('provider_users');
        Schema::dropIfExists('provider_roles');
        Schema::dropIfExists('workspaces');
    }
};
