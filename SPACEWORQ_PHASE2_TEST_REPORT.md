# SPACEWORQ – Phase 2 Test & Verification Report

**Test Date:** 2026-11-26  
**Execution Order Guarantee:** Every test, concurrency run, route check, and SHA-256 verification reported below ran **after** the final PHP edit (`BookingService.php`, `QuoteCalculationService.php`, `AvailabilityService.php`).

---

## 1. Full Laravel Test Suite on Isolated SQLite `:memory:`

**Command:** `php artisan test`  
**Exit Code:** `0` (`SQLITE_TEST_EXIT=0`)  
**Total Result:** **`22 passed` (`271 assertions`)**, `0 failed`, `0 skipped`  
**Duration:** `7.33s`

### Exact Case Breakdown
- `Tests\Unit\ExampleTest`: `that true is true` (**PASS**)
- `Tests\Feature\ExampleTest`: `the application returns a successful response` (**PASS**)
- `Tests\Feature\Phase1DatabaseSchemaTest`:
  - `all expected tables are migrated on isolated sqlite memory` (**PASS**)
  - `master data seeder seeds verified reference data and no fake records` (**PASS**)
- `Tests\Feature\Phase2AuthenticationRbacTest`:
  - `customer registration requires activation before login` (**PASS**)
  - `provider registration creates unified provider and provider user` (**PASS**)
  - `portal boundaries prevent customer from accessing provider or admin login` (**PASS**)
  - `otp login generates and verifies hashed code` (**PASS**)
  - `redirect safety service blocks external and protocol relative urls` (**PASS**)
- `Tests\Feature\Phase3PublicCatalogTest`:
  - `public search filters and hides unapproved or private provider data` (**PASS**)
  - `consultation and partner inquiry submission and reviews` (**PASS**)
- `Tests\Feature\Phase4And5CustomerBookingPaymentTest`:
  - `quote is server authoritative and booking prevents overbooking and duplicates` (**PASS**)
  - `payment fails closed when unconfigured and verifies signatures and webhooks` (**PASS**)
  - `customer vault reserve requests profile and support tickets` (**PASS**)
- `Tests\Feature\Phase6ProviderPanelTest`:
  - `provider workspace crud image cap xss sanitization and tenant isolation` (**PASS**)
  - `provider staff rbac templates reports kyc and masked bank account` (**PASS**)
- `Tests\Feature\Phase7SuperAdminTest`:
  - `non admin is blocked from super admin routes` (**PASS**)
  - `super admin kyc workspace approval publishing coupons settings and audit logs` (**PASS**)
- `Tests\Feature\Phase8AuditFixesTest` (New):
  - `expired hold cannot be verified or confirmed and frees capacity` (**PASS** — covers expired hold rejection, cleanup, freed seat re-booking, duplicate verify idempotency, identical `idempotency_key` across two customers, and capacity conservation)
  - `expired hold webhook does not confirm and valid webhook notifies and is idempotent` (**PASS** — covers expired hold webhook rejection `hold_expired`, `WebhookEvent` `FAILED`, valid webhook confirmation + `PaymentStatusNotification` + replay `already_processed`)
  - `non day pass instant book setting coupon per user limit and expansion subtype` (**PASS** — covers `allow_non_day_pass_instant_book=false` even when `is_instant_bookable=true`, opt-in toggle, coupon `per_user_limit=1`, and `?subtype=`)
  - `provider kyc ownership admin permissions json errors and cors` (**PASS** — covers low-privilege provider staff `403` on profile/KYC download, revoked `admin.coupons.manage` `403` while `admin.read` passes `200`, forced JSON `404` without `Accept: application/json`, and restrictive CORS headers)

---

## 2. Targeted Feature Suite on Disposable MariaDB (`spaceworq_audit_phpunit`)

**Command:** `php artisan test --filter="Phase4And5|Phase8"` with `DB_CONNECTION=mysql`, `DB_DATABASE=spaceworq_audit_phpunit` (`10.4.32-MariaDB`)  
**Exit Code:** `0` (`MYSQL_TEST_EXIT=0`)  
**Total Result:** **`7 passed` (`102 assertions`)**, `0 failed`, `0 skipped`  
**Duration:** `213.68s`

### Executed Cases on MariaDB InnoDB
- `Phase4And5CustomerBookingPaymentTest` (`3/3` passed: `205.06s`, `2.19s`, `1.13s`)
- `Phase8AuditFixesTest` (`4/4` passed: `1.05s`, `2.36s`, `1.08s`, `0.54s`)

---

## 3. Disposable MariaDB Schema Migration, Seeder & Index Check (`spaceworq_audit_20261126`)

- **Incremental Migration Run (`2026_11_26_120001_scope_idempotency_keys_by_customer.php`):** Exit `0` (`761.37ms DONE`).
- **Seeder Run (`DatabaseSeeder`):** Exit `0` (`SEED_EXIT=0`).
- **Verified Scoped Unique Indexes (`SHOW INDEXES`):**
  - `bookings`: `bookings_customer_idem_uq` on `(customer_user_id, idempotency_key)` (`Non_unique = 0`).
  - `payment_transactions`: `payment_tx_booking_cust_idem_uq` on `(booking_id, customer_user_id, idempotency_key)` (`Non_unique = 0`).

---

## 4. Parallel Multi-Process MariaDB InnoDB Concurrency Tests (`spaceworq_audit_phpunit`)

Executed via two synchronized concurrent PHP CLI worker processes (`Start-Job` barrier) against real `10.4.32-MariaDB` InnoDB (`REPEATABLE READ`) row locking (`C:\Users\Dreamzcoder\.gemini\antigravity\brain\66b2ff3c-c0a5-43b6-a014-99f38fca0ce0\scratch\mariadb_concurrency_test.php`):
- **Test A – Simultaneous Capacity Reservation Race (`5` seat workspace `DP-CONC0001`, 2 concurrent workers each booking `4` seats on `2028-11-24`):**
  - Worker 1: `SEAT_W1=REJECTED:{"seat_count":["Insufficient seats available for the selected slot."]}`
  - Worker 2: `SEAT_W2=SUCCESS:14`
  - Post-check: `wsA_reserved_seats = 4` (`<= 5` capacity, zero overbooking).
- **Test B – Simultaneous Cross-Workspace Coupon `per_user_limit=1` Race (`CONCCA47C`, same customer booking `wsA` and `wsB` simultaneously on `2028-05-26`):**
  - Worker 1: `COUPON_W1=SUCCESS:15`
  - Worker 2: `COUPON_W2=REJECTED:{"coupon_code":["You have exceeded the usage limit for this coupon."]}`
  - Post-check: `coupon_used_count = 1`, `coupon_usage_rows = 1` (zero deadlock, zero limit bypass).

---

## 5. Route Audit & SHA-256 Reference Integrity

- **API Route Check:** `145` total `/api/v1` routes; **`42` protected `/api/v1/admin/*` routes** bound to Spatie `PermissionMiddleware` (`admin.*`).
- **Reference SHA-256 Check:** `HASH_CHECK=56 MISMATCHES=0` (`24/24` `spaceworq-clone/` files + `32/32` vendor reference files match Phase 0 baseline).
