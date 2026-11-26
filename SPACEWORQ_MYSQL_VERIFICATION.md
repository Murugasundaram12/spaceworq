# SPACEWORQ – Disposable MySQL / MariaDB Verification Report

**Database Engine Tested:** `10.4.32-MariaDB` (`D:\xampp-8.2\mysql\bin\mysql.exe`)  
**Disposable Test Schema:** `spaceworq_audit_20261126` (`CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`)  
**Safety Compliance:** Legacy `spaceworq_db` and production schemas were never touched; zero destructive commands (`DROP`, `TRUNCATE`, `db:wipe`, `migrate:fresh`, `migrate:rollback`) were executed.

---

## 1. Migration & Seeder Execution on Disposable MariaDB

| Check | Result | Observed Metrics | Classification |
|---|---|---|---|
| Create clean disposable schema (`spaceworq_audit_20261126`) | Exit `0` | UTF-8 (`utf8mb4_unicode_ci`) | **Passed** |
| Run all `12` migrations (`php artisan migrate --force`) | Exit `0` (`MIGRATE_EXIT=0`) | `56` InnoDB tables created (`47` application/package domain tables verified + system/Laravel tables) | **Passed** |
| Storage engine & collation uniformity | Verified via `information_schema.tables` | All `56` tables use `ENGINE=InnoDB` and `TABLE_COLLATION=utf8mb4_unicode_ci` | **Passed** |
| Foreign key constraint integrity | Verified via `information_schema.table_constraints` | **`84`** active foreign key constraints created cleanly in MariaDB strict mode | **Passed** |
| Run `DatabaseSeeder` (`php artisan db:seed --force`) | Exit `0` (`SEED_EXIT=0`) | `cities=6`, `workspace_categories=4`, `workspace_subcategories=10`, `amenities=16`, `roles=4`, `permissions=20` | **Passed** |
| Zero fake business records in seeders | Verified row counts | `users=0`, `providers=0`, `workspaces=0`, `bookings=0` | **Passed** |
| Fail-closed `system_settings` seeded values | Verified rows in `system_settings` | `default_tax_rate=NULL`, `booking_hold_minutes=NULL`, `half_day_multiplier=NULL`, `max_instant_booking_seats=NULL`, `cancellation_cutoff_hours=NULL`, `pay_at_venue_enabled=false`, `allow_non_day_pass_instant_book=false` | **Passed** |
| InnoDB row lock syntax & rollback safety probe | Exit `0` (`START TRANSACTION; SELECT ... FOR UPDATE; ROLLBACK;`) | Succeeded cleanly with zero locks retained | **Passed** |

---

## 2. Static Migration & MySQL Schema Findings

1. **[Low] Composite Nullable Unique Constraints on `provider_roles` and `provider_users` (`2026_11_26_110004`):**
   - `provider_roles_scope_name_uq` (`provider_id`, `workspace_id`, `name`) and `provider_user_workspace_uq` (`provider_id`, `user_id`, `workspace_id`) include nullable `workspace_id`. In MySQL/MariaDB and SQLite, composite unique indexes allow multiple duplicate rows when `workspace_id IS NULL`. Duplicate checks are enforced in PHP (`ProviderRoleController::store`, `ProviderStaffController::store`), though concurrent inserts with `workspace_id = NULL` could bypass them.
2. **[Medium] Global Unique `idempotency_key` Not Scoped per User (`2026_11_26_110006`):**
   - `bookings.idempotency_key` and `payment_transactions.idempotency_key` are globally unique across all customers, whereas `BookingService` looks up existing bookings scoped by `['idempotency_key', 'customer_user_id']`. Two distinct customers submitting identical simple idempotency keys would collide with a DB unique constraint violation (`500`) for the second customer.
3. **[Low] Migration Rollback Column Drop Order (`2026_11_26_110001::down()`):**
   - `down()` calls `dropColumn(['first_name', 'last_name', 'mobile', 'username', 'user_type', 'status'])` without dropping unique indexes `users_mobile_unique` and `users_username_unique` first. (Rollback was not executed due to non-destructive audit policy).
4. **[Low] Multi-Statement `SHOW` / Strict Mode Audit:**
   - All migrations compile under `strict => true` and MariaDB `10.4.32` without key-length overflow (`max 191/180` or composite index widths stay well under `InnoDB` 3072-byte `utf8mb4` limit).
