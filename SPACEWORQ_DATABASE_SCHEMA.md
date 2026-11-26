# SPACEWORQ – Final Database Schema Documentation

**Project:** `D:\xampp-8.2\htdocs\New folder (5)\spaceworq`  
**Target Runtime Database:** MySQL 8 / MariaDB 10.4 (`utf8mb4_unicode_ci`)  
**Automated Test Engine:** Isolated SQLite `:memory:` (`phpunit.xml`)  
**Local Verification Database:** Isolated `database/database.sqlite` (`php artisan migrate` + `php artisan db:seed`)  
**Unified Entity Rule:** `Provider`, `providers`, `provider_users`, and `ProviderUser` only (zero separate `Vendor` table or model).

---

## 1. Migration Inventory (12 Ordered Migrations)

1. `0001_01_01_000000_create_users_table.php` — Base `users`, `password_reset_tokens`, `sessions`
2. `0001_01_01_000001_create_cache_table.php` — `cache`, `cache_locks`
3. `0001_01_01_000002_create_jobs_table.php` — `jobs`, `job_batches`, `failed_jobs`
4. `2026_11_26_104132_create_permission_tables.php` — Spatie RBAC (`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`)
5. `2026_11_26_104133_create_personal_access_tokens_table.php` — Laravel Sanctum bearer tokens (`personal_access_tokens`)
6. `2026_11_26_110001_extend_users_and_create_otp_verifications_table.php` — Portal identity fields on `users` (`first_name`, `last_name`, `mobile`, `username`, `user_type`, `status`, `softDeletes`) + `otp_verifications` + `activation_tokens`
7. `2026_11_26_110002_create_master_data_tables.php` — `cities`, `localities`, `workspace_categories`, `workspace_subcategories`, `amenities`
8. `2026_11_26_110003_create_provider_tables.php` — `providers`, `provider_documents`, `provider_bank_accounts`, `provider_settings`
9. `2026_11_26_110004_create_workspace_and_layout_tables.php` — `workspaces`, `provider_roles`, `provider_users`, `workspace_listing_configs`, `workspace_working_hours`, `workspace_images`, `workspace_amenities`, `workspace_floors`, `layout_templates`, `floor_template_mappings`, `workspace_approval_histories`
10. `2026_11_26_110005_create_customer_tables.php` — `customer_profiles`, `user_favorites`, `workspace_reviews`
11. `2026_11_26_110006_create_booking_and_payment_tables.php` — `coupons`, `reserve_requests`, `bookings`, `booking_items`, `inventory_reservations`, `coupon_usages`, `payment_transactions`, `webhook_events`, `cancellation_records`, `refunds`
12. `2026_11_26_110007_create_platform_tables.php` — `consultations`, `partner_inquiries`, `support_tickets`, `notifications`, `audit_logs`, `system_settings`

---

## 2. Schema Table Summary & Key Constraints

### 2.1 Authentication & RBAC
- **`users`**: Multi-portal account table (`user_type`: `customer`, `provider`, `super_admin`; `status`: `ACTIVE`, `INACTIVE`, `PENDING_ACTIVATION`, `SUSPENDED`), unique `email`, nullable unique `mobile`/`username`, `softDeletes`.
- **`otp_verifications`**: Time-limited hashed OTPs (`otp_hash`), `channel`, `identifier`, `purpose`, `attempts`, `expires_at`, `verified_at`.
- **`activation_tokens`**: SHA-256 hashed activation links (`token_hash`), `type` (`client_activation`, `provider_activation`), `expires_at`, `used_at`.
- **`roles` / `permissions` / `model_has_roles` / `role_has_permissions`**: Seeded with `super_admin`, `provider_owner`, `provider_staff`, `customer` and granular `admin.*` + `providers.*` permission keys.

### 2.2 Master Data (`MasterDataSeeder`)
- **`cities`** (6 seeded: `Bengaluru`, `Chennai`, `Mumbai`, `Delhi NCR`, `Hyderabad`, `Pune`), unique `slug`.
- **`localities`** (`city_id` FK -> `cities`), `name`, `slug`, `pincode`.
- **`workspace_categories`** (4 seeded: `LONG_TERM_LEASING`, `DAY_PASS`, `MEETINGS_EVENTS`, `VIRTUAL_OFFICE`), unique `code`, `default_booking_mode`.
- **`workspace_subcategories`** (10 seeded under the 4 categories, including `COWORKING_SPACE`, `MANAGED_OFFICE`, `OFFICE_COMMERCIAL`, `NEW_BUILDING`, `HOT_DESK_DAY_PASS`, `PRIVATE_CABIN_DAY_PASS`, `MEETING_ROOM`, `EVENT_SPACE`, `BUSINESS_ADDRESS`, `GST_VIRTUAL_OFFICE`).
- **`amenities`** (16 seeded), unique `slug`.

### 2.3 Unified Provider Domain
- **`providers`**: Single operator entity (`owner_user_id` FK -> `users`), `company_name`, `slug`, `brand_name`, `business_email`, `business_phone`, `gstin`, `pan`, `city_id`, `status` (`PENDING_VERIFICATION`, `ACTIVE`, `SUSPENDED`, `REJECTED`), `kyc_status` (`NOT_SUBMITTED`, `PENDING`, `VERIFIED`, `REJECTED`), `verified_at`, `verified_by`, `softDeletes`.
- **`provider_roles`**: Provider-scoped RBAC templates (`permissions` JSON supporting canonical `providers.*` and UI permission labels).
- **`provider_users`**: Provider team memberships (`provider_id`, `user_id`, optional `workspace_id`, `provider_role_id`, `is_owner`, `status`), `softDeletes`.
- **`provider_documents`**: Private KYC document metadata (`disk`, hidden `storage_path`, `mime_type`, `size_bytes`, `status`, `reviewed_by`).
- **`provider_bank_accounts`**: Laravel encrypted `account_number_encrypted` (hidden from serialization) + public masked `account_last4`, `ifsc_code`, `upi_id`, `is_primary`, `is_verified`.
- **`provider_settings`**: Per-provider toggles (`auto_accept_instant_bookings`, `gst_registered`, `default_tax_rate`, `timezone`).

### 2.4 Workspaces, Pricing, Floors & Layouts
- **`workspaces`**: Unique `code` (e.g. `DP-BLR0101`), `slug`, `category_id`, `subcategory_id`, `city_id`, `locality_id`, sanitized `description_html`, capacity (`total_floors`, `total_seats`, `available_seats`), `is_upcoming`, `gst_enabled`, `tax_rate`, `status` (`DRAFT`, `In Review`, `ACTIVE`, `INACTIVE`, `REJECTED`, `SUSPENDED`), `approval_status` (`PENDING`, `APPROVED`, `REJECTED`), `is_published`, `is_instant_bookable`, `approved_by`, `approved_at`, `softDeletes`.
- **`workspace_listing_configs`**: Pricing options (`price_per_day`, `price_per_month`, `price_per_hour`, `price_per_half_day`, `price_per_sqft`, `price_per_year`, `min_seats`, `max_seats`).
- **`workspace_working_hours`**: Weekly schedule (`day_of_week` 0–6, `open_time`, `close_time`, `is_closed`).
- **`workspace_images`**: Controlled local storage paths (hidden in JSON), max 5 per workspace enforced in service.
- **`workspace_amenities`**: Pivot table `(workspace_id, amenity_id)`.
- **`workspace_floors`**: Building floors `(workspace_id, floor_number, label, total_seats)`.
- **`layout_templates`**: Provider canvas templates (`elements` JSON, `elements_count`, `total_seats`), `softDeletes`.
- **`floor_template_mappings`**: Links `(provider_id, workspace_id, workspace_floor_id, layout_template_id)` cleanly.
- **`workspace_approval_histories`**: Immutable review log `(workspace_id, actor_user_id, from_status, to_status, notes)`.

### 2.5 Customer, Booking, Inventory & Payments
- **`customer_profiles`**, **`user_favorites`** (Vault, unique `(user_id, workspace_id)`), **`workspace_reviews`** (unique `(workspace_id, user_id)`).
- **`coupons`** & **`coupon_usages`**: Dynamic DB promo code rules (`percentage` / `fixed`, `max_discount_amount`, `min_order_amount`, `applicable_category_code`, `usage_limit`, `per_user_limit`, `starts_at`, `expires_at`, `is_active`).
- **`reserve_requests`**: Long-term / virtual office inquiries (`request_code`, `status` in `Open`, `In Progress`, `Review`, `Discussion`, `Completed`, `Cancelled`, `Rejected`, `reminders_count`).
- **`bookings`** & **`booking_items`**: Unique `booking_reference` & nullable unique `idempotency_key`; server-calculated pricing (`unit_price`, `subtotal_amount`, `discount_amount`, `taxable_amount`, `tax_rate`, `tax_amount`, `total_amount`); `status` (`PENDING_PAYMENT`, `Upcoming`, `Active`, `In Progress`, `Review`, `Completed`, `Cancelled`), `payment_status` (`UNPAID`, `PENDING`, `PAID`, `FAILED`, `REFUNDED`).
- **`inventory_reservations`**: Slot-level capacity lock records (`reservation_date`, `start_time`, `end_time`, `seats_reserved`, `status` in `HELD`, `CONFIRMED`, `RELEASED`, `EXPIRED`, `expires_at`).
- **`payment_transactions`**: Gateway-agnostic payment orders (`idempotency_key`, `gateway_order_id`, `gateway_payment_id`, `status`, sanitized `gateway_payload`).
- **`webhook_events`**: Idempotency log (`event_id` unique, `signature_verified`, `status`).
- **`cancellation_records`** & **`refunds`**: Policy snapshot (`policy_snapshot` JSON), `refundable_amount`, refund records.

### 2.6 Platform Operations
- **`consultations`**, **`partner_inquiries`**, **`support_tickets`**, **`notifications`**, **`audit_logs`**, **`system_settings`** (`7` fail-closed configuration keys seeded with unapproved defaults as `null` / `false`).
