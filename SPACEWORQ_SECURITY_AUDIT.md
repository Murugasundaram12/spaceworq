# SPACEWORQ – Backend Security Audit

**Audit Target:** `D:\xampp-8.2\htdocs\New folder (5)\spaceworq`  
**Scope:** Laravel 12 API Backend (`routes/api.php`, middleware, services, models, policies, form requests)

---

## 1. Verified Security Controls Implemented

### 1.1 Authentication, Session & Portal Isolation
- **Three-Portal Identity Boundaries:**
  - Customer (`user_type = customer`), Provider (`user_type = provider`), and Super Admin (`user_type = super_admin` + `super_admin` Spatie role) have dedicated login endpoints (`/api/v1/auth/customer/login`, `/api/v1/auth/provider/login`, `/api/v1/admin/auth/login`) and route middleware (`user.type:customer`, `user.type:provider`, `provider.access`, `super.admin`).
  - Provider staff creation (`ProviderStaffController::store`) blocks attaching an email belonging to another portal `user_type`.
- **Activation & OTP Hardening:**
  - Newly registered accounts start in `PENDING_ACTIVATION` and cannot obtain Sanctum tokens until email/provider activation completes (`AuthService::verifyActivationToken`).
  - Resend activation and password-reset endpoints return generic success messages to prevent user/account enumeration.
  - OTPs are randomly generated, SHA-256 hashed (`otp_hash`) at rest, expire after `config('spaceworq.otp.ttl_minutes')` (10 min), enforce max attempts (`5`), and use dedicated rate limiters (`throttle:auth` = 10/min, `throttle:otp` = 5/min).
- **Open-Redirect Prevention:**
  - `RedirectSafetyService::sanitizeRelativeRedirect()` rejects external domains, protocol-relative URLs (`//evil.com`), backslash bypasses (`\/`), and javascript/data URI schemes, falling back safely to relative paths.

### 1.2 Authorization, RBAC & Cross-Tenant Isolation
- **Model Policies & Middleware:**
  - `BookingPolicy`, `ReserveRequestPolicy`, `SupportTicketPolicy`, `WorkspacePolicy`, `ProviderPolicy`, and `LayoutTemplatePolicy` enforced via `Gate::authorize(...)`.
  - Every Provider controller resolves `current_provider` from `$request->attributes` via `EnsureProviderAccess` and verifies `$resource->provider_id === $provider->id` before access or mutation (tested across Provider A vs Provider B in `Phase6ProviderPanelTest`).
  - Sub-entity tenancy checks: creating/updating roles, assigning staff, mapping floors to layout templates, or attaching support tickets to bookings all verify parent/child foreign keys belong to the same provider/customer (`403`/`422` on mismatch).
  - Staff permission enforcement (`EnsureProviderPermission`) checks canonical `providers.*` keys as well as mapped UI labels (`Manage Workspaces`, `Manage Users`, `Manage Roles`, `Approve Bookings`, `View Reports`, `Layout Designer`).

### 1.3 Data Privacy & Output Filtering
- **Public API Isolation:** `PublicWorkspaceResource` and `PublicWorkspaceDetailResource` never expose `business_email`, `business_phone`, `gstin`, `pan`, KYC documents, or payout bank accounts.
- **Public Catalog Visibility:** `WorkspaceVisibilityService` enforces `is_published = true`, `status = ACTIVE`, `approval_status = APPROVED`, and `provider.status = ACTIVE` + `provider.kyc_status = VERIFIED`. Suspending a provider via Admin automatically unpublishes all of that provider's workspaces.
- **Provider Financial & Document Protection:**
  - `ProviderBankAccount` casts `account_number_encrypted` with Laravel's `encrypted` cast and `$hidden`, returning only `masked_account_number` and `account_last4` via `ProviderBankAccountResource`.
  - Workspace images and KYC documents are stored on private `local` storage disks (`config/spaceworq.php`), with UUID storage filenames, MIME/size validation, and authorized download controllers instead of raw client path exposure.

### 1.4 Input Sanitization & Integrity
- **XSS Prevention:** `HtmlSanitizerService` strips dangerous tags (`<script>`, `<style>`, `<iframe>`, `<object>`, `<embed>`, `<form>`), inline `on*` event attributes, and `javascript:`/`data:`/`vbscript:` hrefs from `description_html` on workspace create/update.
- **Mass Assignment & Client Price Tampering:** All inputs pass through Laravel `FormRequest` classes or explicit `$request->validate(...)` + `$model->forceFill($whitelisted)`. Booking prices, unit rates, coupon discounts, taxes, and totals are always computed server-side by `QuoteCalculationService` and `BookingService` (`Phase4And5CustomerBookingPaymentTest` verifies client-injected `unit_price`/`total_amount` are ignored).
- **Concurrency & Idempotency:** `BookingService::createInstantBooking` uses `DB::transaction` with pessimistic row locking (`lockForUpdate()`) on the `Workspace` and `Coupon`, checks overlapping time-slot `InventoryReservation` sums (`CONFIRMED` + non-expired `HELD`), and honors `idempotency_key` to prevent duplicate bookings or overbooking.

### 1.5 Fail-Closed Payment & Webhook Integrity
- **Fail-Closed Gateway Check:** `PaymentService::initiatePayment()` and `RazorpayPaymentGateway::isConfigured()` reject payment initiation with HTTP `503` (`PaymentGatewayNotConfiguredException`) whenever `RAZORPAY_KEY_ID` or `RAZORPAY_KEY_SECRET` are absent.
- **Cryptographic Verification & Replay Protection:** Payment verification requires valid HMAC-SHA256 signature (`hash_equals`); webhook handling requires valid `X-Razorpay-Signature` HMAC over raw payload and deduplicates events via `webhook_events.event_id`. Providers cannot mark an `UNPAID` booking as `Active`, `In Progress`, or `Completed`.

---

## 2. Known Pre-Production Limitations / Operational Reminders
1. **Production Environment Secrets & Debug Mode:** `.env` currently runs local development settings (`APP_DEBUG=true`, SQLite file driver). Before staging/production deployment, set `APP_DEBUG=false`, `APP_ENV=production`, configure MySQL 8+ credentials, HTTPS-only `APP_URL`, and `RAZORPAY_*` secrets.
2. **Database Row-Locking Semantics:** Automated tests execute against SQLite `:memory:`, where transactions serialize writes differently than InnoDB row locks. Concurrency stress testing should be executed against MySQL/MariaDB before production launch.
3. **CSV Formula Injection Hygiene:** Admin/Provider reports output UTF-8 CSV via `fputcsv()`. If spreadsheet users open untrusted workspace/customer names directly in Excel, consider prepending a leading apostrophe to cell values beginning with `=`, `+`, `-`, or `@` during frontend/export hardening.
4. **Hold Expiry Cleanup Scheduler:** Expired unpaid `HELD` reservations are ignored in real-time by `AvailabilityService` (`expires_at > now()`), and an optional scheduled console command to transition expired `PENDING_PAYMENT` bookings to `Cancelled` can be added once `booking_hold_minutes` is approved.
