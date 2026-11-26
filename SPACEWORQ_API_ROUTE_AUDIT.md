# SPACEWORQ – API Route & Middleware Audit

**Audit Source:** `php artisan route:list --path=api/v1 --json` (`145` registered routes) and `routes/api.php`  
**Total Verified Endpoints:** `145` routes (`0` route syntax errors, `0` wildcard collisions detected)

---

## 1. Route Inventory Summary by Domain

| Domain Group | Route Count | Auth Middleware | Authorization / Guarding | Rate Limiter | Findings |
|---|---|---|---|---|---|
| **Public Discovery & Inquiries** | `15` (`16` incl. public/optional quote & avail.) | None (`public` / optional `sanctum`) | `WorkspaceVisibilityService` (`is_published=true`, `ACTIVE`, `APPROVED`, verified `ACTIVE` provider) | `throttle:inquiries` (`10/min`) on `/consultations` and `/partner-inquiries`; unlimited on catalog `GET`s | **Medium:** `PublicCatalogController::expansion()` reads `$filters['subcategory']` while `SPACEWORQ_BACKEND_REQUIREMENTS.md` documents `?subtype=` -> `?subtype=` query param is ignored unless passed as `subcategory`. **Low:** Catalog/search endpoints have no dedicated `throttle` middleware. |
| **Auth & Portal Onboarding** | `19` (`/auth/*`, `/admin/auth/login`, `/users/role`) | Mixed: `guest` / `auth:sanctum` / `user.type:provider` | Portal-isolated login checks (`customerLogin`, `ProviderAuthController@login`, `AdminAuthController@login`) | `throttle:auth` (`10/min`) on login/register/reset; `throttle:otp` (`5/min`) on OTP | **Passed:** `redirect_to` sanitized via `RedirectSafetyService`; activation/OTP tokens hashed. **Low:** Shared `/auth/login` allows `customer` or `provider` if password matches; strict portal logins (`/customer/login`, `/provider/login`, `/admin/auth/login`) block cross-portal tokens (`403`). **Medium:** Missing published `config/cors.php` and missing API JSON exception forcing in `bootstrap/app.php` if caller omits `Accept: application/json`. |
| **Customer Bookings, Payments, Reserve Requests, Vault & Support** | `23` (incl. `GET/POST` aliases & webhooks) | `auth:sanctum`, `user.type:customer` (except webhook: unauthenticated HMAC) | `BookingPolicy`, `ReserveRequestPolicy`, `SupportTicketPolicy` + strict customer ownership checks | `throttle:booking` (`30/min`), `throttle:inquiries` (`10/min`) | **Passed:** Server-authoritative quotes; `403` isolation across customers; Razorpay fail-closed `503`. **High:** Payment verification/webhook does not verify booking `hold_expires_at` expiry or re-check slot availability before confirming `HELD` seats. |
| **Provider / Vendor Panel (`/api/v1/provider/*`)** | `41` | `auth:sanctum`, `user.type:provider`, `provider.access`, plus `provider.permission:*` on module routes | `$request->attributes->get('current_provider')` ownership check + Policies (`WorkspacePolicy`, `LayoutTemplatePolicy`, `BookingPolicy`, `ReserveRequestPolicy`, `ProviderPolicy`) | Inherits default API group (no explicit mutation throttle) | **Medium:** `/provider/profile` `GET` and `/provider/documents/{document}/download` lack `ProviderPolicy::update` / `is_owner` check, allowing any active low-privilege staff user of the same provider to inspect bank `last4`/documents and download private KYC files. **Passed:** Cross-provider isolation returns `403`. |
| **Super Admin Portal (`/api/v1/admin/*`)** | `47` | `auth:sanctum`, `super.admin` (`EnsureSuperAdmin`) | Checks `user_type === 'super_admin' && $user->hasRole('super_admin')` | None beyond login `throttle:auth` | **Medium:** Granular `admin.*` permissions seeded in `RolePermissionSeeder` (`admin.providers.manage`, `admin.workspaces.approve`, `admin.master_data.manage`, etc.) are not enforced via route permission middleware; all admin endpoints rely solely on the single `super_admin` role check. |

---

## 2. Exact Route Verification Table (`145` Routes)

### 2.1 Public Endpoints
- `GET|HEAD api/v1/public/home` (`api.v1.public.home`) — Public (`api`) — **Passed**
- `GET|HEAD api/v1/cities` (`api.v1.cities.index`) — Public (`api`) — **Passed**
- `GET|HEAD api/v1/expansion-opportunities` (`api.v1.expansion.index`) — Public (`api`) — **Medium** (`subcategory` vs documented `subtype` query param)
- `GET|HEAD api/v1/support/faqs` (`api.v1.support.faqs`) — Public (`api`) — **Passed**
- `GET|HEAD api/v1/workspaces/published` (`api.v1.workspaces.published.index`) — Public (`api`) — **Passed**
- `GET|HEAD api/v1/workspaces/published/amenities` (`api.v1.workspaces.published.amenities`) — Public (`api`) — **Passed**
- `GET|HEAD api/v1/workspaces/published/suggestions` (`api.v1.workspaces.published.suggestions`) — Public (`api`) — **Passed**
- `GET|HEAD api/v1/workspaces/published/compare` (`api.v1.workspaces.published.compare`) — Public (`api`) — **Passed**
- `GET|HEAD api/v1/workspaces/published/{workspace}` (`api.v1.workspaces.published.show`) — Public (`api`) — **Passed** (hides unapproved spaces & private provider fields)
- `GET|HEAD api/v1/workspaces/published/{workspace}/images/{image}/file` (`api.v1.workspaces.published.image`) — Public (`api`) — **Passed** (enforces visibility + `$image->workspace_id === $workspace->id`)
- `GET|HEAD api/v1/workspaces/published/{workspace}/reviews` (`api.v1.workspaces.published.reviews.index`) — Public (`api`) — **Passed**
- `GET|POST|HEAD api/v1/workspaces/published/{workspace}/instant-book/availability` (`api.v1.workspaces.published.instant-book.availability`) — Public (`api`) — **Passed** (checks visibility, date, working hours, active reservations)
- `POST api/v1/workspaces/published/{workspace}/instant-book/quote` (`api.v1.workspaces.published.instant-book.quote`) — Public (`api`) — **Passed** (server-authoritative quote; optional Sanctum user lookup for coupon user cap)
- `POST api/v1/consultations` (`api.v1.consultations.store`) — Public + `throttle:inquiries` — **Low** (`workspace_id` allows referencing any DB ID without checking `isPubliclyVisible`, leaking workspace existence via `exists:workspaces,id`)
- `POST api/v1/partner-inquiries` (`api.v1.partner-inquiries.store`) — Public + `throttle:inquiries` — **Passed**

### 2.2 Authentication Endpoints
- `POST api/v1/auth/register` (`api.v1.auth.register`) — `throttle:auth` — **Passed**
- `POST api/v1/auth/register-provider` (`api.v1.auth.register-provider`) — `throttle:auth` — **Low** (`ProviderRegisterRequest` uses `Password::min(8)` vs `CustomerRegisterRequest` requiring mixedCase/numbers/symbols; email uniqueness check isn't lowercased prior to validation)
- `POST api/v1/auth/verify-email` (`api.v1.auth.verify-email`) — `throttle:auth` — **Passed**
- `POST api/v1/auth/activate-provider` (`api.v1.auth.activate-provider`) — `throttle:auth` — **Passed**
- `POST api/v1/auth/resend-client-activation` (`api.v1.auth.resend-client-activation`) — `throttle:auth` — **Passed**
- `POST api/v1/auth/resend-provider-activation` (`api.v1.auth.resend-provider-activation`) — `throttle:auth` — **Passed**
- `POST api/v1/auth/login` (`api.v1.auth.login`) — `throttle:auth` — **Passed**
- `POST api/v1/auth/customer/login` (`api.v1.auth.customer.login`) — `throttle:auth` — **Passed**
- `POST api/v1/auth/provider/login` (`api.v1.auth.provider.login`) — `throttle:auth` — **Passed**
- `POST api/v1/admin/auth/login` (`api.v1.admin.auth.login`) — `throttle:auth` — **Passed**
- `POST api/v1/auth/send-otp` (`api.v1.auth.send-otp`) — `throttle:otp` — **Low** (`purpose=activation` validated in request but `sendOtp`/`verifyOtp` only operate on active customer accounts)
- `POST api/v1/auth/verify-otp` (`api.v1.auth.verify-otp`) — `throttle:otp` — **Passed**
- `POST api/v1/auth/forgot-password` (`api.v1.auth.forgot-password`) — `throttle:auth` — **Passed**
- `POST api/v1/auth/reset-password` (`api.v1.auth.reset-password`) — `throttle:auth` — **Passed**
- `GET|HEAD api/v1/auth/me` (`api.v1.auth.me`) — `auth:sanctum` — **Passed** (`Medium`: Sanctum tokens have `expiration => null` in `config/sanctum.php`)
- `POST api/v1/auth/refresh` (`api.v1.auth.refresh`) — `auth:sanctum` — **Passed**
- `POST api/v1/auth/logout` (`api.v1.auth.logout`) — `auth:sanctum` — **Passed**
- `POST api/v1/auth/change-password` (`api.v1.auth.change-password`) — `auth:sanctum` — **Passed**
- `GET|HEAD api/v1/users/role` (`api.v1.users.role`) — `auth:sanctum`, `user.type:provider` — **Passed**

### 2.3 Customer & Webhook Endpoints
All authenticated customer routes enforce `auth:sanctum` and `user.type:customer` (`403` for providers/admins, `401` if unauthenticated/inactive):
- Reviews: `POST api/v1/workspaces/published/{workspace}/reviews` (`reviews.upsert`) — **Passed**
- Instant Bookings: `POST .../instant-book` (`throttle:booking`), `GET /workspaces/instant-bookings/me`, `GET /bookings`, `GET /bookings/{booking}` (`BookingPolicy::viewCustomerBooking`), `POST /bookings/{booking}/cancel` (`BookingPolicy::manageAsCustomer`), `POST /workspaces/instant-bookings/{booking}/complete` (`BookingPolicy::manageAsCustomer`) — **Passed** (`Low`: `complete` does not verify `booking_date <= today`; `cancel` allows cancelling `Active` bookings)
- Payments & Webhook: `POST /bookings/{booking}/payments/initiate` (`throttle:booking`), `POST /bookings/{booking}/payments/verify` (`throttle:booking`), `POST /webhooks/payments/{gateway}` (signature-checked unauthenticated) — **High** (missing expired-hold availability check inside `verifyPayment` & webhook capture)
- Reserve Requests: `POST .../reserve-request` (`throttle:inquiries`), `POST .../reserve-request/reminder` (`throttle:inquiries`), `GET /workspaces/reserve-requests/me`, `POST /workspaces/reserve-requests/{reserveRequest}/cancel` (`ReserveRequestPolicy::manageAsCustomer`) — **Passed**
- Vault: `GET /workspaces/favorites`, `POST /workspaces/{workspace}/favorite`, `DELETE /workspaces/{workspace}/favorite` — **Passed**
- Profile, Notifications & Support: `GET|PATCH /customer/profile`, `GET /customer/notifications`, `POST /customer/notifications/{id}/read`, `GET|POST /support/tickets`, `GET /support/tickets/{ticket}` (`SupportTicketPolicy::view`) — **Passed** (`support.tickets.store` validates `booking_id` ownership before save)

### 2.4 Provider / Vendor Panel Endpoints (`/api/v1/provider/*`)
All 41 routes require `auth:sanctum`, `user.type:provider`, and `provider.access`:
- **Dashboard:** `GET /dashboard` (`providers.dashboard.view`) — **Passed**
- **Profile / KYC / Bank:** `GET /profile`, `PATCH /profile` (`ProviderPolicy::update`), `POST /documents` (`ProviderPolicy::update`), `GET /documents/{document}/download`, `POST /bank-accounts` (`ProviderPolicy::update`), `DELETE /bank-accounts/{bankAccount}` (`ProviderPolicy::update`) — **Medium** (`GET /profile` & `download` lack owner-only / dedicated permission guard beyond `provider.access`)
- **Workspaces (`providers.workspaceManagement.view`):** `GET|POST /workspaces`, `GET|PATCH|DELETE /workspaces/{workspace}`, `POST .../submit-review`, `POST .../images`, `DELETE .../images/{image}` — **Passed**
- **Floors (`providers.floors.view`):** `GET|PUT /workspaces/{workspace}/floors` — **Passed**
- **Roles (`providers.roleManagement.view`):** `GET /permissions`, `GET|POST /roles`, `PATCH|DELETE /roles/{role}` — **Passed**
- **Staff (`providers.users.view`):** `GET|POST /staff`, `PATCH|DELETE /staff/{staff}` — **Passed**
- **Reserve Requests (`providers.reserveRequests.view`):** `GET /reserve-requests`, `PATCH .../{id}/status`, `POST .../{id}/assign` — **Passed**
- **Instant Bookings (`providers.instantBookingRequests.view`):** `GET /instant-bookings`, `POST /instant-bookings/reset-past`, `PATCH .../{id}/status`, `POST .../{id}/assign` — **Passed**
- **Reports (`providers.reports.view`):** `GET /reports/reserve-requests`, `.../export`, `GET /reports/instant-bookings`, `.../export` — **Low** (`fputcsv()` does not neutralize spreadsheet formula prefixes `=`, `+`, `-`, `@`)
- **Layout Designer & Mapping (`providers.layoutDesigner.view` / `providers.templateMapping.view`):** `GET|POST /templates`, `GET|PATCH|DELETE /templates/{template}`, `POST /templates/{template}/clone`, `GET|POST /templates/mappings` — **Passed** (note `/templates/mappings` is correctly registered before `/templates/{template}`)

### 2.5 Super Admin Endpoints (`/api/v1/admin/*`)
All 46 authenticated admin routes require `auth:sanctum` and `super.admin` (`EnsureSuperAdmin`):
- `dashboard`, `providers`, `providers/{id}`, `providers/{id}/status`, `providers/{id}/verify-kyc`, `provider-documents/{id}/download`, `workspaces`, `workspaces/{id}`, `workspaces/{id}/decision`, `cities`, `localities`, `categories`, `subcategories`, `amenities`, `customers`, `customers/{id}/status`, `bookings`, `bookings/{id}/refund`, `reserve-requests`, `coupons`, `payments`, `webhook-events`, `refunds`, `consultations`, `partner-inquiries`, `support-tickets`, `settings`, `roles`, `audit-logs` — **Medium** (granular seeded `admin.*` permissions are not enforced per route; `EnsureSuperAdmin` grants full admin access only to `super_admin` role).
