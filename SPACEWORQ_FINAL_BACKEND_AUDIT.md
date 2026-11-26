# SPACEWORQ – Final Independent Backend Audit Report

**Audit Date:** 2026-11-26  
**Project Inspected:** `D:\xampp-8.2\htdocs\New folder (5)\spaceworq` (`Laravel 12.74.0`, `PHP 8.2.12`)  
**Audit Method:** Read-only code review of routes, middleware, policies, controllers, form requests, services, models, migrations, seeders, and config; paired with `php artisan test`, `route:list --json`, SHA-256 reference integrity checks, and live disposable `10.4.32-MariaDB` schema/seed verification.

---

## Executive Verdict & Severity Summary

| Severity | Count | Summary |
|---|---|---|
| **Critical** | **0** | No critical unauthenticated admin/provider bypass, SQL injection, secret leakage, or fake-paid bypass found. |
| **High** | **1** | Expired unpaid booking holds are not re-verified against capacity during late payment verification or webhook capture (`PaymentService`). |
| **Medium** | **7** | Intra-provider staff access to KYC docs & profile bank last4 (`ProviderProfileController`); unused route-level `admin.*` permissions (`RolePermissionSeeder`); `allow_non_day_pass_instant_book=false` bypass via `is_instant_bookable=true`; post-lock coupon `per_user_limit` race & unscoped `idempotency_key`; partial refund status/inventory gap; missing API JSON forced rendering & `config/cors.php`; expansion filter `subcategory` vs `subtype`. |
| **Low** | **8** | Non-expiring Sanctum tokens (`expiration => null`); nullable composite unique constraints (`workspace_id = NULL`); `110001::down()` index drop ordering; provider vs customer password strength inconsistency; email uniqueness pre-lowercasing/soft-delete handling; consultation `workspace_id` existence probe & missing search rate limits; unused OTP `activation` purpose; booking cancel/complete date boundaries & CSV formula escaping. |
| **Passed** | **Verified** | `18/18` PHPUnit tests (`224` assertions), `56/56` InnoDB tables (`84` FKs) migrated/seeded cleanly on MariaDB, `145` routes inspected, `56/56` design reference SHA-256 hashes unchanged (`0` mismatches). |

**Production Readiness Conclusion:** **NOT YET PRODUCTION-READY** — While the core architecture is solid and passes all existing tests on SQLite `:memory:` plus schema/seeding on MariaDB, production launch requires remediating the **1 High** and key **Medium** findings (particularly expired-hold verification before payment capture, intra-provider KYC download gating, CORS/JSON error handling, and gateway/refund configuration) and completing stakeholder sign-off on [SPACEWORQ_PENDING_BUSINESS_DECISIONS.md](file:///d:/xampp-8.2/htdocs/New%20folder%20(5)/spaceworq/SPACEWORQ_PENDING_BUSINESS_DECISIONS.md).

---

## Detailed Findings by Domain (Items 1–10)

### 1. Automated Tests & Design Integrity (`Passed` with Test Coverage Caveats)
- **`php artisan test`:** **`18 passed (224 assertions)`**, `0 failed`, `0 skipped`, duration `4.88s` on SQLite `:memory:`. See [SPACEWORQ_FINAL_TEST_RESULTS.md](file:///d:/xampp-8.2/htdocs/New%20folder%20(5)/spaceworq/SPACEWORQ_FINAL_TEST_RESULTS.md).
- **Design Folder Integrity:** `24 / 24` `spaceworq-clone/` files and `32 / 32` vendor reference files match Phase 0 SHA-256 baselines with `0` modifications.
- **Test Coverage Gap (`Low`):** Automated suite runs on SQLite `:memory:` only and does not currently test expired-hold payment verification races or cross-connection InnoDB row-lock contention.

### 2. API Routes, Middleware, HTTP Methods & Naming (145 Routes)
See complete route-by-route audit in [SPACEWORQ_API_ROUTE_AUDIT.md](file:///d:/xampp-8.2/htdocs/New%20folder%20(5)/spaceworq/SPACEWORQ_API_ROUTE_AUDIT.md).
- **[Medium] `PublicCatalogController::expansion()` Query Parameter Mismatch:** Reads `$filters['subcategory']` while backend contract documents `?subtype=`. Clients passing `?subtype=` receive unfiltered expansion results.
- **[Medium] API JSON Exception Rendering & Missing CORS Configuration (`bootstrap/app.php`, `config/cors.php`):** No `$exceptions->shouldRenderJsonWhen(...)` configured for `api/*` paths when callers omit `Accept: application/json`, and `config/cors.php` is not published/configured for explicit frontend origin whitelisting.
- **[Passed]** Wildcard route ordering (`reset-past`, `templates/mappings`, `workspaces/favorites`, `reserve-requests/me`) has zero collisions across all `145` routes.

### 3. Database Migrations, FKs, Indexes & Rollback Safety
See [SPACEWORQ_MYSQL_VERIFICATION.md](file:///d:/xampp-8.2/htdocs/New%20folder%20(5)/spaceworq/SPACEWORQ_MYSQL_VERIFICATION.md).
- **[Passed]** All `12` migrations deploy cleanly to `10.4.32-MariaDB` (`56` `InnoDB` `utf8mb4_unicode_ci` tables, `84` foreign keys, composite indexes on visibility/date/provider/booking queries).
- **[Medium] Global Unique `idempotency_key` (`2026_11_26_110006`):** `bookings.idempotency_key` and `payment_transactions.idempotency_key` enforce global uniqueness instead of composite `(customer_user_id, idempotency_key)`.
- **[Low] Composite Nullable Unique Keys (`2026_11_26_110004`):** `provider_roles` and `provider_users` composite uniques include nullable `workspace_id` (`NULL != NULL` in SQL).
- **[Low] `2026_11_26_110001::down()` Rollback Ordering:** Drops columns (`mobile`, `username`) without prior `dropUnique()`. (Static observation; rollback not run per strict rules.)

### 4. Authorization, RBAC, Seeded Permissions & Tenant Isolation
- **[Passed] Three-Portal Isolation:** `EnsureUserType`, `EnsureProviderAccess`, `EnsureSuperAdmin`, plus dedicated login endpoints strictly isolate `customer`, `provider`, and `super_admin` identities. Suspended/rejected providers are blocked and their workspaces are unpublished automatically on Admin suspension (`AdminProviderController::updateStatus`).
- **[Passed] Cross-Tenant Provider & Customer Isolation:** Provider controllers verify `$resource->provider_id === $provider->id` and enforce child entity ownership (`provider_role_id`, `workspace_id`, `workspace_floor_id`, `layout_template_id`, `assigned_provider_user_id`); Customer policies enforce ownership (`403` on foreign bookings, reserve requests, support tickets).
- **[Passed] Unified Provider Schema:** Strictly `Provider`, `providers`, `provider_users`, `ProviderUser` (zero `Vendor` artifacts).
- **[Medium] Intra-Provider KYC Document Download & Profile Read Over-Privilege (`ProviderProfileController.php`):** `ProviderProfileController::show()` and `downloadDocument()` lack `ProviderPolicy::update` (`is_owner`) or a dedicated KYC permission check — only checking `provider.access` and `provider_id`. Any active staff user within the same provider can read bank account last4 metadata and download uploaded KYC documents.
- **[Medium] Granular `admin.*` Seeded Permissions Not Bound to Admin Routes (`RolePermissionSeeder.php`, `routes/api.php`):** All `10` seeded `providers.*` permissions map cleanly to `/api/v1/provider/*` middleware and policies, whereas the `10` seeded `admin.*` permissions (`admin.providers.manage`, `admin.workspaces.approve`, etc.) exist in DB without route-level enforcement (`EnsureSuperAdmin` checks only `super_admin` role).

### 5. Booking Availability, Locking, Quotes & Coupons
- **[Passed] Server-Authoritative Quotes (`QuoteCalculationService`, `BookingService`):** Ignores client-supplied prices/totals, applies DB-only coupons with validity windows and usage caps, and executes booking creation inside `DB::transaction` with `Workspace::lockForUpdate()`.
- **[Medium] `allow_non_day_pass_instant_book = false` Guard Bypass (`AvailabilityService.php` lines 51–61):** If an approved non-Day-Pass workspace has `is_instant_bookable = true`, the guard `! $workspace->is_instant_bookable && ! $allowNonDayPass && ...` passes, allowing instant bookings even when platform flag `allow_non_day_pass_instant_book` is `false`.
- **[Medium] Post-Lock `per_user_limit` Coupon Race (`BookingService.php` line 146):** After locking `Coupon` (`lockForUpdate()`), `BookingService` re-validates global `usage_limit`, but does not re-check `CouponUsage` count for `per_user_limit`. Parallel requests on different workspaces by the same user could exceed `per_user_limit`.
- **[Low] Booking Date/Status Edge Validations:** `BookingController::complete()` allows a paid booking to be marked completed before `booking_date`; `BookingService::cancelBooking()` allows customer cancellation even if status is `Active`.

### 6. Razorpay Verification, Webhooks, Refunds & Fail-Closed Behavior
- **[Passed] Fail-Closed Payment Initiation (`PaymentService`, `RazorpayPaymentGateway`):** Returns HTTP `503` (`PaymentGatewayNotConfiguredException`) when `RAZORPAY_KEY_ID` or `RAZORPAY_KEY_SECRET` are absent.
- **[Passed] Cryptographic Signature & Webhook Idempotency:** Uses `hash_equals()` over HMAC-SHA256 for payment verification and raw-body webhooks; rejects bad/unconfigured webhook signatures with `403`; deduplicates via `webhook_events.event_id` unique constraint.
- **[High] Expired Hold Seats Not Re-Checked at Payment Verify / Webhook Capture (`PaymentService::verifyPayment`, `PaymentService::handleWebhook`):** Neither method checks `$booking->hold_expires_at` or re-runs capacity validation with `Workspace::lockForUpdate()` before moving `InventoryReservation` to `CONFIRMED`. Once `booking_hold_minutes` is configured, late payment verification or delayed `payment.captured` webhooks can confirm an expired hold whose seats were already booked by another customer. Also, webhook capture skips customer `PaymentStatusNotification` and audit logging.
- **[Medium] Manual Refunds Mark Partial Refunds as Full & Lack Gateway Driver (`AdminOperationsController::recordRefund`):** `PaymentGatewayInterface` has no refund execution method; admin refund recording marks both `Booking.payment_status` and `PaymentTransaction.status` as `REFUNDED` even when `amount < total_amount`, and does not release inventory if the booking wasn't cancelled.

### 7. Validation, Sensitive Data Exposure & Rate Limiting
- **[Passed] Output Privacy & Secret Redaction:** `PublicWorkspaceResource` / `PublicWorkspaceDetailResource` exclude provider `business_email`, `business_phone`, `gstin`, `pan`, KYC docs, and bank accounts. `ProviderBankAccount` uses encrypted storage + `$hidden` and exposes only `account_last4`. `AuditLogService` redacts `password`, `token`, `otp`, `secret`, `signature`, `account_number`, and `pan`. Workspace description HTML is sanitized (`HtmlSanitizerService`), and uploads use private local storage disks with UUID filenames.
- **[Low] Password Policy & Registration Normalization Gaps:** `ProviderRegisterRequest`, `ChangePasswordRequest`, and `ResetPasswordRequest` require only `min(8)` (weaker than `CustomerRegisterRequest`); email `unique` rules do not lowercase input or explicitly exclude soft-delete edge collisions; `StoreConsultationRequest` `workspace_id` allows probing unpublished workspace IDs; public search endpoints lack rate limiting; Sanctum tokens have `expiration => null`; CSV export (`ProviderReportService`) lacks spreadsheet formula escaping.

---

## Pre-Frontend-Integration Recommended Remediations (Priority Order)
1. **(High)** Re-verify `hold_expires_at` and lock/check `AvailabilityService` inside `PaymentService::verifyPayment()` and `handleWebhook()` before confirming reservations; add webhook audit log & notification.
2. **(Medium)** Gate `ProviderProfileController::show()` and `downloadDocument()` with owner / dedicated permission authorization (`Gate::authorize('update', $provider)` or owner check).
3. **(Medium)** Fix `AvailabilityService` non-Day-Pass category gate so `allow_non_day_pass_instant_book=false` strictly restricts to `DAY_PASS` and `MEETINGS_EVENTS`.
4. **(Medium)** Re-check `per_user_limit` after `Coupon::lockForUpdate()` and scope `idempotency_key` unique constraints by `(customer_user_id, idempotency_key)`.
5. **(Medium)** Update `AdminOperationsController::recordRefund()` to support `PARTIALLY_REFUNDED` status and plan gateway refund API integration before live payments.
6. **(Medium)** Add `shouldRenderJsonWhen` in `bootstrap/app.php`, publish `config/cors.php` for approved frontend origins, accept `subtype` alongside `subcategory` in `PublicCatalogController::expansion()`, and optionally wire `permission:admin.*` middleware on `/admin/*` routes.
7. **(Business Approval)** Confirm all `10` pending decisions in [SPACEWORQ_PENDING_BUSINESS_DECISIONS.md](file:///d:/xampp-8.2/htdocs/New%20folder%20(5)/spaceworq/SPACEWORQ_PENDING_BUSINESS_DECISIONS.md).
