# SPACEWORQ – Dependency-Ordered Implementation Plan

**Project:** `D:\xampp-8.2\htdocs\New folder (5)\spaceworq`  
**Strategy:** Execute Phases 1–10 in strict dependency order, verifying reference SHA-256 hashes and passing isolated SQLite `:memory:` tests at every phase.

---

## Phase Sequence

### Phase 0: Project & Reference Audit (Completed)
- Verified runtime versions, environment isolation, and official SpaceWorq endpoints.
- Recorded SHA-256 baseline hashes in `SPACEWORQ_FRONTEND_INVENTORY.md`.
- Produced `SPACEWORQ_BACKEND_REQUIREMENTS.md`, `SPACEWORQ_DATABASE_PLAN.md`, and `SPACEWORQ_IMPLEMENTATION_PLAN.md`.

### Phase 1: Normalized Database Schema, Models & Master Seeders
- Install `laravel/sanctum` (if Packagist accessible; otherwise document) and configure API routing.
- Create ordered migrations for all verified domains (`users`, `roles/permissions`, `cities/localities/categories/subcategories/amenities`, `providers/provider_users/provider_roles/documents/banks/settings`, `workspaces/configs/hours/images/amenities/floors/templates/mappings/approval_history`, `customer_profiles/favorites/reviews`, `coupons/reserve_requests/bookings/booking_items/inventory_reservations/payments/webhooks/cancellations/refunds`, `consultations/partner_inquiries/support_tickets/notifications/audit_logs/system_settings`).
- Implement Eloquent models with explicit `$fillable`, casts, soft deletes, scopes, and relationships.
- Seed verified master data only (`cities`, `workspace_categories`, `workspace_subcategories`, `amenities`, baseline `roles/permissions`, default fail-closed `system_settings`). Zero fake workspaces/providers/customers/bookings.
- Write schema & relationship unit/feature tests.

### Phase 2: Authentication, RBAC & Separation
- Implement Customer, Provider, and Super Admin auth controllers + Form Requests + safe open-redirect validator.
- Implement `Provider` transactional registration (`User` + `Provider` + `ProviderUser` owner) and activation/reset flows.
- Implement OTP generation/verification (hashed, rate-limited, expiring) and Sanctum token issuance/revocation.
- Implement custom middleware (`EnsureUserType`, `EnsureProviderAccess`, `EnsureProviderPermission`, `EnsureSuperAdmin`) and Policies (`WorkspacePolicy`, `BookingPolicy`, `ReserveRequestPolicy`, `ProviderPolicy`, `SupportTicketPolicy`).
- Write auth, RBAC, redirect protection, and isolation tests.

### Phase 3: Public Website Backend
- Implement `PublicCatalogController`, `WorkspaceDetailController`, `ConsultationController`, `PartnerInquiryController`, `ExpansionController`, `SupportController` (FAQs).
- Centralize workspace visibility via `WorkspaceVisibilityService` (`is_published = true`, `status = 'ACTIVE'`, `approval_status = 'APPROVED'`, and active verified provider).
- Expose API Resources ensuring zero leakage of provider private/bank/KYC/internal data.
- Write search/filter/sort/visibility/inquiry feature tests.

### Phase 4 & 5: Customer Booking, Quote, Payment Abstraction & Account Backend
- Implement `AvailabilityService`, `QuoteCalculationService` (authoritative server-side pricing, dynamic DB coupons, explicit unconfigured/per-workspace tax rules), `BookingService` (DB transactions + `lockForUpdate()` pessimistic locking + duplicate `idempotency_key` prevention + hold expiration), `ReserveRequestService`.
- Implement `PaymentGatewayInterface`, `RazorpayPaymentGateway` (fail-closed when credentials absent), `PaymentService` (idempotent order creation, cryptographic signature verification, webhook signature + `webhook_events` idempotency).
- Implement Customer Vault (`UserFavoriteController`), Profile, Booking History, Reserve Request History, Reviews, and Support Tickets.
- Write booking concurrency/overbooking, duplicate submission, quote tampering prevention, fail-closed payment, webhook idempotency, and customer ownership tests.

### Phase 6: Provider / Vendor Backend
- Implement Provider Dashboard stats & workspace selector scoping.
- Implement Provider Workspace CRUD (sanitized HTML description, geolocation, working hours, image uploads to private/controlled storage with MIME/size checks, listing configs, floor creation, submit for approval).
- Implement Provider Staff (`ProviderUser`) & Provider Role management.
- Implement Provider Reserve Requests & Instant Booking Requests management (status transitions, remarks, staff assignment, reset past bookings).
- Implement Provider Reports (Reserve Requests & Instant Booking Requests analytics + CSV export) and Layout Designer (`LayoutTemplate` CRUD/clone + `FloorTemplateMapping`).
- Implement Provider Profile, KYC Document upload (private disk), and Bank Account management (encrypted account number, masked responses).
- Write strict cross-provider isolation and staff permission tests.

### Phase 7: Super Admin Backend
- Implement Super Admin Dashboard, Provider verification & KYC review, Workspace approval queue (`workspace_approval_histories`), Customer oversight, Master Data CRUD (`cities`, `localities`, `categories`, `subcategories`, `amenities`), Coupon CRUD, Booking/Payment/Refund oversight, Consultations/Partner Inquiries/Support Tickets management, System Settings, and Audit Logs viewer.
- Protect all routes with `EnsureSuperAdmin` and write authorization/approval tests.

### Phase 8 & 9: API Consistency, Notifications, Audit Logs & Docs
- Verify consistent validation error shapes, pagination, rate limiters (`throttle`), and named routes across `routes/api.php` and `routes/web.php`.
- Implement Database Notifications (`BookingStatusNotification`, `ReserveRequestStatusNotification`, `ProviderApprovalNotification`, `PaymentStatusNotification`) and `AuditLogService` (masking sensitive fields).
- Author `API_DOCUMENTATION.md` covering all routes, requests, validation rules, responses, auth, and permissions.

### Phase 10: Security Hardening, Hash Verification & Final Reports
- Execute full test suite (`php artisan test`) on SQLite `:memory:` and verify zero changes to reference asset SHA-256 hashes.
- Run optional MySQL isolated test database check (fresh separate schema `spaceworq_test`, never `spaceworq_db`) if clean and safe.
- Create final deliverables:
  - `SPACEWORQ_BACKEND_IMPLEMENTATION_REPORT.md`
  - `SPACEWORQ_DATABASE_SCHEMA.md`
  - `API_DOCUMENTATION.md`
  - `SPACEWORQ_SECURITY_AUDIT.md`
  - `SPACEWORQ_TEST_REPORT.md`
  - `SPACEWORQ_PENDING_BUSINESS_DECISIONS.md`
