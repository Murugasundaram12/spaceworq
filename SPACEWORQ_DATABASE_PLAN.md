# SPACEWORQ – Database Architecture Plan

**Target Engine:** MySQL 8 / MariaDB 10.4 (`utf8mb4_unicode_ci`) in runtime, SQLite `:memory:` compatible migrations for automated tests.  
**Naming Discipline:** Provider and Vendor share one unified entity (`providers`, `provider_users`, `Provider`, `ProviderUser`).

---

## 1. Domain Entity & Table Map (Dependency Order)

### Domain A: Core Users, Auth & Platform RBAC
1. **`users`**
   - Columns: `id`, `first_name`, `last_name`, `name`, `email` (unique), `mobile` (nullable, unique when present), `username` (nullable unique), `user_type` (`customer`, `provider`, `super_admin`), `status` (`ACTIVE`, `INACTIVE`, `PENDING_ACTIVATION`, `SUSPENDED`), `email_verified_at`, `password`, `remember_token`, `timestamps`, `softDeletes`.
   - Indexes: `(user_type, status)`, `email`, `mobile`.
2. **`password_reset_tokens`**, **`sessions`**, **`personal_access_tokens`** (Sanctum)
3. **`otp_verifications`**
   - Columns: `id`, `user_id` (nullable FK), `channel` (`email`, `mobile`), `identifier`, `otp_hash`, `purpose` (`login`, `activation`), `attempts`, `expires_at`, `verified_at`, `timestamps`.
4. **`roles` & `permissions` & `role_permissions` & `user_roles`**
   - Normalized platform RBAC supporting `super_admin`, `customer`, `provider_owner`, `provider_staff`, plus fine-grained permissions (`admin.read`, `providers.*`).

### Domain B: Master Data
5. **`cities`**: `id`, `name`, `slug` (unique), `state`, `country`, `image_path`, `latitude`, `longitude`, `is_featured` (bool), `is_active` (bool), `sort_order`, `timestamps`.
6. **`localities`**: `id`, `city_id` (FK -> `cities`), `name`, `slug`, `pincode`, `is_active`, `timestamps`, unique `(city_id, slug)`.
7. **`workspace_categories`**: `id`, `code` (unique: `LONG_TERM_LEASING`, `DAY_PASS`, `MEETINGS_EVENTS`, `VIRTUAL_OFFICE`), `name`, `subtitle`, `description`, `badge_label`, `image_path`, `default_booking_mode` (`instant_book`, `reserve_request`), `sort_order`, `is_active`, `timestamps`.
8. **`workspace_subcategories`**: `id`, `category_id` (FK -> `workspace_categories`), `code` (unique: `COWORKING_SPACE`, `MANAGED_OFFICE`, `OFFICE_COMMERCIAL`, `NEW_BUILDING`, `HOT_DESK_DAY_PASS`, `PRIVATE_CABIN_DAY_PASS`, `MEETING_ROOM`, `EVENT_SPACE`, `BUSINESS_ADDRESS`, `GST_VIRTUAL_OFFICE`), `name`, `description`, `price_unit_label`, `supports_upcoming` (bool), `sort_order`, `is_active`, `timestamps`.
9. **`amenities`**: `id`, `name`, `slug` (unique), `icon_key`, `category` (nullable), `is_active`, `sort_order`, `timestamps`.

### Domain C: Provider (Unified Provider / Vendor)
10. **`providers`**
    - Columns: `id`, `owner_user_id` (FK -> `users`), `company_name`, `slug` (unique), `brand_name`, `business_email`, `business_phone`, `gstin` (nullable), `pan` (nullable), `city_id` (nullable FK), `address`, `status` (`PENDING_VERIFICATION`, `ACTIVE`, `SUSPENDED`, `REJECTED`), `kyc_status` (`NOT_SUBMITTED`, `PENDING`, `VERIFIED`, `REJECTED`), `kyc_notes`, `verified_at`, `verified_by` (nullable FK -> `users`), `timestamps`, `softDeletes`.
11. **`provider_roles`**: `id`, `provider_id` (FK -> `providers`), `workspace_id` (nullable FK -> `workspaces`), `name`, `description`, `permissions` (JSON array of canonical permission keys/labels), `is_system` (bool), `timestamps`.
12. **`provider_users`**: `id`, `provider_id` (FK -> `providers`), `user_id` (FK -> `users`), `workspace_id` (nullable FK -> `workspaces` for workspace-scoped staff in `users.html`), `provider_role_id` (nullable FK -> `provider_roles`), `role_name`, `is_owner` (bool), `status` (`ACTIVE`, `INACTIVE`), `invited_by` (nullable FK -> `users`), `timestamps`, `softDeletes`. Unique `(provider_id, user_id, workspace_id)`.
13. **`provider_documents`**: `id`, `provider_id` (FK -> `providers`), `document_type`, `original_filename`, `disk` (`local`), `storage_path`, `mime_type`, `size_bytes`, `status` (`PENDING`, `APPROVED`, `REJECTED`), `reviewed_by` (nullable FK), `reviewed_at`, `review_notes`, `timestamps`.
14. **`provider_bank_accounts`**: `id`, `provider_id` (FK -> `providers`), `account_holder_name`, `bank_name`, `account_number_encrypted`, `account_last4`, `ifsc_code`, `upi_id` (nullable), `is_primary` (bool), `is_verified` (bool), `timestamps`.
15. **`provider_settings`**: `id`, `provider_id` (FK -> `providers`, unique), `auto_accept_instant_bookings` (bool), `notification_email`, `gst_registered` (bool), `default_tax_rate` (decimal 5,2 nullable), `timezone`, `timestamps`.

### Domain D: Workspaces, Floors, Pricing & Layout Designer
16. **`workspaces`**
    - Columns: `id`, `provider_id` (FK -> `providers`), `code` (unique e.g. `LTL-CHECS0007`), `slug` (unique), `name`, `generated_post_title`, `category_id` (FK -> `workspace_categories`), `subcategory_id` (FK -> `workspace_subcategories`), `city_id` (FK -> `cities`), `locality_id` (nullable FK -> `localities`), `country`, `address_line`, `landmark`, `pincode`, `place_id`, `latitude` (decimal 10,7), `longitude` (decimal 10,7), `description_html` (sanitized), `total_floors`, `total_seats`, `available_seats`, `is_upcoming` (bool), `tentative_availability_date` (date nullable), `gst_enabled` (bool), `tax_rate` (decimal 5,2 nullable), `rating_avg` (decimal 3,2 default 0), `reviews_count` (unsigned int default 0), `status` (`DRAFT`, `IN_REVIEW`, `ACTIVE`, `INACTIVE`, `REJECTED`, `SUSPENDED`), `approval_status` (`PENDING`, `APPROVED`, `REJECTED`), `is_published` (bool default false), `is_instant_bookable` (bool default false), `created_by` (FK -> `users`), `updated_by` (nullable FK -> `users`), `approved_by` (nullable FK -> `users`), `approved_at`, `timestamps`, `softDeletes`.
    - Indexes: `(is_published, status, approval_status)`, `(city_id, category_id)`, `provider_id`, `code`.
17. **`workspace_listing_configs`**: `id`, `workspace_id` (FK -> `workspaces`), `subcategory_id` (FK -> `workspace_subcategories`), `price_per_day` (decimal 12,2 nullable), `price_per_month` (decimal 12,2 nullable), `price_per_hour` (decimal 12,2 nullable), `price_per_half_day` (decimal 12,2 nullable), `price_per_sqft` (decimal 12,2 nullable), `price_per_year` (decimal 12,2 nullable), `min_seats`, `max_seats`, `is_active` (bool), `timestamps`.
18. **`workspace_working_hours`**: `id`, `workspace_id` (FK -> `workspaces`), `day_of_week` (0-6 / Monday-Sunday), `open_time` (time nullable), `close_time` (time nullable), `is_closed` (bool), `timestamps`. Unique `(workspace_id, day_of_week)`.
19. **`workspace_images`**: `id`, `workspace_id` (FK -> `workspaces`), `disk`, `storage_path`, `original_filename`, `mime_type`, `size_bytes`, `sort_order`, `is_primary` (bool), `timestamps`.
20. **`workspace_amenities`**: `workspace_id` (FK), `amenity_id` (FK), primary `(workspace_id, amenity_id)`.
21. **`workspace_floors`**: `id`, `workspace_id` (FK -> `workspaces`), `floor_number` (unsigned int), `label`, `total_seats`, `is_active` (bool), `timestamps`. Unique `(workspace_id, floor_number)`.
22. **`layout_templates`**: `id`, `provider_id` (FK -> `providers`), `name`, `description`, `elements` (JSON), `elements_count`, `total_seats`, `created_by` (FK -> `users`), `timestamps`, `softDeletes`.
23. **`floor_template_mappings`**: `id`, `provider_id` (FK -> `providers`), `workspace_id` (FK -> `workspaces`), `workspace_floor_id` (FK -> `workspace_floors`), `layout_template_id` (FK -> `layout_templates`), `mapped_by` (FK -> `users`), `timestamps`. Unique `(workspace_floor_id)`.
24. **`workspace_approval_histories`**: `id`, `workspace_id` (FK -> `workspaces`), `actor_user_id` (FK -> `users`), `from_status`, `to_status`, `notes`, `timestamps`.

### Domain E: Customer Profile, Vault & Reviews
25. **`customer_profiles`**: `id`, `user_id` (FK -> `users`, unique), `company_name`, `gstin`, `preferred_city_id` (nullable FK), `billing_address`, `timestamps`.
26. **`user_favorites`** (Vault): `id`, `user_id` (FK -> `users`), `workspace_id` (FK -> `workspaces`), `timestamps`. Unique `(user_id, workspace_id)`.
27. **`workspace_reviews`**: `id`, `workspace_id` (FK -> `workspaces`), `user_id` (FK -> `users`), `rating` (tinyint 1-5), `title`, `comment`, `is_visible` (bool), `timestamps`. Unique `(workspace_id, user_id)`.

### Domain F: Bookings, Reserve Requests, Inventory, Coupons & Payments
28. **`coupons`**: `id`, `code` (unique), `title`, `discount_type` (`percentage`, `fixed`), `discount_value` (decimal 10,2), `max_discount_amount` (decimal 10,2 nullable), `min_order_amount` (decimal 10,2 nullable), `applicable_category_code` (nullable), `usage_limit` (nullable int), `per_user_limit` (nullable int), `used_count` (int default 0), `starts_at`, `expires_at`, `is_active` (bool), `timestamps`.
29. **`reserve_requests`**: `id`, `request_code` (unique), `workspace_id` (FK -> `workspaces`), `provider_id` (FK -> `providers`), `customer_user_id` (FK -> `users`), `assigned_provider_user_id` (nullable FK -> `provider_users`), `status` (`Open`, `In Progress`, `Review`, `Discussion`, `Completed`, `Cancelled`, `Rejected`), `requested_seats` (int nullable), `move_in_date` (date nullable), `duration_months` (int nullable), `request_summary`, `remarks`, `reminders_count` (int default 0), `last_reminded_at`, `timestamps`, `softDeletes`.
30. **`bookings`** (Instant Bookings & Confirmed Reservations):
    - Columns: `id`, `booking_reference` (unique), `idempotency_key` (nullable unique), `workspace_id` (FK -> `workspaces`), `provider_id` (FK -> `providers`), `customer_user_id` (FK -> `users`), `assigned_provider_user_id` (nullable FK -> `provider_users`), `booking_type` (`INSTANT_BOOKING`, `DAY_PASS`, `MEETING_ROOM`), `booking_date` (date), `slot` (`morning`, `afternoon`, `fullday`, `custom`), `start_time` (time), `end_time` (time), `seat_count` (unsigned int), `unit_price` (decimal 12,2), `subtotal_amount` (decimal 12,2), `coupon_id` (nullable FK -> `coupons`), `coupon_code` (nullable), `discount_amount` (decimal 12,2 default 0), `taxable_amount` (decimal 12,2), `tax_rate` (decimal 5,2 default 0), `tax_amount` (decimal 12,2 default 0), `total_amount` (decimal 12,2), `currency` (char 3 default 'INR'), `status` (`PENDING_PAYMENT`, `Upcoming`, `Active`, `In Progress`, `Review`, `Completed`, `Cancelled`), `payment_status` (`UNPAID`, `PENDING`, `PAID`, `FAILED`, `REFUNDED`, `PARTIALLY_REFUNDED`), `hold_expires_at` (timestamp nullable), `contact_name`, `contact_email`, `contact_phone`, `remarks`, `checkin_token` (nullable), `completed_at`, `cancelled_at`, `timestamps`, `softDeletes`.
    - Indexes: `(workspace_id, booking_date, status)`, `(customer_user_id, status)`, `(provider_id, status)`.
31. **`booking_items`**: `id`, `booking_id` (FK -> `bookings`), `workspace_listing_config_id` (nullable FK), `item_label`, `quantity`, `unit_price` (decimal 12,2), `line_total` (decimal 12,2), `timestamps`.
32. **`inventory_reservations`**: `id`, `workspace_id` (FK -> `workspaces`), `booking_id` (FK -> `bookings`), `reservation_date` (date), `start_time` (time), `end_time` (time), `seats_reserved` (unsigned int), `status` (`HELD`, `CONFIRMED`, `RELEASED`, `EXPIRED`), `expires_at` (timestamp nullable), `timestamps`.
    - Index: `(workspace_id, reservation_date, status)`.
33. **`coupon_usages`**: `id`, `coupon_id` (FK -> `coupons`), `user_id` (FK -> `users`), `booking_id` (FK -> `bookings`), `discount_amount` (decimal 12,2), `timestamps`. Unique `(coupon_id, booking_id)`.
34. **`payment_transactions`**: `id`, `booking_id` (FK -> `bookings`), `customer_user_id` (FK -> `users`), `gateway` (`razorpay`, `unconfigured`), `idempotency_key` (unique), `gateway_order_id` (nullable unique), `gateway_payment_id` (nullable unique), `amount` (decimal 12,2), `currency` (char 3 default 'INR'), `status` (`CREATED`, `PENDING`, `CAPTURED`, `FAILED`, `REFUNDED`), `failure_reason`, `verified_at`, `gateway_payload` (JSON sanitized), `timestamps`.
35. **`webhook_events`**: `id`, `gateway`, `event_id` (unique), `event_type`, `signature_verified` (bool), `payload` (JSON), `processed_at`, `status` (`RECEIVED`, `PROCESSED`, `IGNORED`, `FAILED`), `error_message`, `timestamps`.
36. **`cancellation_records`**: `id`, `booking_id` (FK -> `bookings`), `cancelled_by_user_id` (FK -> `users`), `reason`, `policy_snapshot` (JSON), `refundable_amount` (decimal 12,2 default 0), `cancelled_at`, `timestamps`.
37. **`refunds`**: `id`, `booking_id` (FK -> `bookings`), `payment_transaction_id` (FK -> `payment_transactions`), `cancellation_record_id` (nullable FK -> `cancellation_records`), `gateway_refund_id` (nullable), `amount` (decimal 12,2), `status` (`PENDING`, `PROCESSED`, `FAILED`), `reason`, `processed_at`, `timestamps`.

### Domain G: Inquiries, Support, Notifications, Audit & Settings
38. **`consultations`**: `id`, `user_id` (nullable FK), `name`, `company_name`, `phone`, `email` (nullable), `city_name`, `category_interest` (nullable), `seats_range` (nullable), `workspace_id` (nullable FK), `notes`, `status` (`NEW`, `CONTACTED`, `QUALIFIED`, `CLOSED`), `timestamps`.
39. **`partner_inquiries`**: `id`, `user_id` (nullable FK), `inquiry_type` (`WORKSPACE_PROVIDER`, `BUILDING_OWNER`, `DEVELOPER`), `entity_name`, `city_name`, `locality`, `total_desks` (nullable int), `desk_price` (nullable decimal 12,2), `total_area_sqft` (nullable int), `floor_plate_sqft` (nullable int), `partnership_model` (nullable), `construction_stage` (nullable), `expected_completion` (nullable), `indicative_rent` (nullable), `developer_brand` (nullable), `contact_name`, `contact_phone`, `contact_email`, `status` (`NEW`, `IN_REVIEW`, `ONBOARDED`, `ARCHIVED`), `timestamps`.
40. **`support_tickets`**: `id`, `ticket_code` (unique), `user_id` (FK -> `users`), `booking_id` (nullable FK -> `bookings`), `category`, `subject`, `message`, `status` (`OPEN`, `IN_PROGRESS`, `RESOLVED`, `CLOSED`), `assigned_admin_id` (nullable FK -> `users`), `resolution_notes`, `timestamps`.
41. **`notifications`**: Laravel standard `notifications` table (UUID primary key, `notifiable` morph, `data`, `read_at`, `timestamps`).
42. **`audit_logs`**: `id`, `actor_user_id` (nullable FK -> `users`), `actor_role`, `provider_id` (nullable FK -> `providers`), `action`, `auditable_type`, `auditable_id`, `ip_address`, `user_agent`, `metadata` (JSON without secrets/PII), `created_at`.
43. **`system_settings`**: `id`, `key` (unique), `value` (JSON/text), `value_type`, `group`, `description`, `is_public` (bool), `updated_by` (nullable FK -> `users`), `timestamps`.
