# SPACEWORQ – REST API Documentation (`/api/v1`)

**Base Prefix:** `/api/v1` (`145` registered API routes verified via `php artisan route:list --path=api/v1`)  
**Authentication:** Laravel Sanctum Bearer Token (`Authorization: Bearer <token>`)  
**Validation Error Format:** Laravel standard `422 Unprocessable Entity` (`{"message": "...", "errors": {"field": ["..."]}}`)

---

## 1. Public Catalog & Website Endpoints (Unauthenticated)

| Method | URI | Controller Action | Rate Limit / Auth | Request Inputs / Behavior |
|---|---|---|---|---|
| `GET` | `/api/v1/public/home` | `PublicCatalogController@home` | Public | Live spaces count, active cities with live counts, categories/subcategories, amenities. |
| `GET` | `/api/v1/cities` | `PublicCatalogController@cities` | Public | Active cities with live workspace counts. |
| `GET` | `/api/v1/expansion-opportunities` | `PublicCatalogController@expansion` | Public | Query: `city`, `subtype` (`ALL`, `NEW_BUILDING`, `OFFICE_COMMERCIAL`), `q`. |
| `GET` | `/api/v1/support/faqs` | `SupportFaqController@faqs` | Public | Support FAQ items and support category list. |
| `GET` | `/api/v1/workspaces/published` | `PublicCatalogController@publishedWorkspaces` | Public | Filters: `category`, `subcategory`, `city`, `locality`, `q`, `amenities[]`, `min_price`, `max_price`, `sort`, `page`, `per_page`. Strictly returns approved, active, published workspaces of KYC-verified active providers. |
| `GET` | `/api/v1/workspaces/published/amenities` | `PublicCatalogController@amenities` | Public | Active amenities master list. |
| `GET` | `/api/v1/workspaces/published/suggestions` | `PublicCatalogController@suggestions` | Public | Query: `q` (search autocomplete across cities, localities, workspaces). |
| `GET` | `/api/v1/workspaces/published/compare` | `PublicCatalogController@compare` | Public | Query: `ids[]` (max 3 approved workspace IDs). |
| `GET` | `/api/v1/workspaces/published/{workspace}` | `PublicCatalogController@show` | Public | Approved workspace detail (`PublicWorkspaceDetailResource`) — excludes private provider contact/tax/KYC/bank fields. |
| `GET` | `/api/v1/workspaces/published/{workspace}/images/{image}/file` | `PublicCatalogController@image` | Public | Streams approved workspace photo from controlled local disk. |
| `GET` | `/api/v1/workspaces/published/{workspace}/reviews` | `WorkspaceReviewController@index` | Public | Paginated visible workspace reviews. |
| `GET/POST` | `/api/v1/workspaces/published/{workspace}/instant-book/availability` | `BookingController@availability` | Public | Inputs: `booking_date`, `seat_count`, `slot` (`morning|afternoon|fullday|custom`), optional `start_time`, `end_time`. |
| `POST` | `/api/v1/workspaces/published/{workspace}/instant-book/quote` | `BookingController@quote` | Public / Optional Sanctum | Inputs: `booking_date`, `seat_count`, `slot`, optional `start_time`, `end_time`, `coupon_code`. Server computes `unit_price`, `subtotal_amount`, `discount_amount`, `taxable_amount`, `tax_rate`, `tax_amount`, `total_amount`. |
| `POST` | `/api/v1/consultations` | `InquiryController@storeConsultation` | Public, `throttle:inquiries` | Inputs: `name`, `company_name`, `phone`, `email`, `city_name`, `category_interest`, `seats_range`, `workspace_id`, `notes`. Returns `201`. |
| `POST` | `/api/v1/partner-inquiries` | `InquiryController@storePartnerInquiry` | Public, `throttle:inquiries` | Inputs: `inquiry_type` (`WORKSPACE_PROVIDER|BUILDING_OWNER|DEVELOPER`), `entity_name`, `city_name`, `locality`, property metrics, contact fields. Returns `201`. |

---

## 2. Multi-Portal Authentication (`/api/v1/auth` & `/api/v1/admin/auth`)

| Method | URI | Controller Action | Middleware | Request / Behavior |
|---|---|---|---|---|
| `POST` | `/api/v1/auth/register` | `AuthController@register` | `throttle:auth` | Customer signup (`first_name`, `last_name`, `email`, `mobile`, `password`, `password_confirmation`, `terms_accepted`). Status starts `PENDING_ACTIVATION`. |
| `POST` | `/api/v1/auth/verify-email` | `AuthController@verifyEmail` | `throttle:auth` | Activates customer via `{ token }`. |
| `POST` | `/api/v1/auth/resend-client-activation` | `AuthController@resendClientActivation` | `throttle:auth` | `{ email }` -> generic safe response (no enumeration). |
| `POST` | `/api/v1/auth/register-provider` | `ProviderAuthController@registerProvider` | `throttle:auth` | Atomic creation of `User`, `Provider` (`PENDING_VERIFICATION`), default `ProviderRole`, and owner `ProviderUser`. |
| `POST` | `/api/v1/auth/activate-provider` | `ProviderAuthController@activateProvider` | `throttle:auth` | Activates provider owner via `{ token }`. |
| `POST` | `/api/v1/auth/resend-provider-activation` | `ProviderAuthController@resendProviderActivation` | `throttle:auth` | Generic safe resend response. |
| `POST` | `/api/v1/auth/login` | `AuthController@login` | `throttle:auth` | Customer/Provider identifier+password login with open-redirect validation on `redirect_to`. |
| `POST` | `/api/v1/auth/customer/login` | `AuthController@customerLogin` | `throttle:auth` | Strict Customer portal login (rejects provider/super_admin accounts with `403`). |
| `POST` | `/api/v1/auth/provider/login` | `ProviderAuthController@login` | `throttle:auth` | Strict Provider portal login returning provider context & permissions. |
| `POST` | `/api/v1/admin/auth/login` | `AdminAuthController@login` | `throttle:auth` | Strict Super Admin login requiring `user_type = super_admin` + `super_admin` role. |
| `POST` | `/api/v1/auth/send-otp` | `AuthController@sendOtp` | `throttle:otp` | `{ channel: email|mobile, identifier, purpose: login|activation }`. Stored hashed + expires in 10 min. |
| `POST` | `/api/v1/auth/verify-otp` | `AuthController@verifyOtp` | `throttle:otp` | `{ channel, identifier, otp, portal }`. Max 5 attempts; returns Sanctum token when valid. |
| `POST` | `/api/v1/auth/forgot-password` | `AuthController@forgotPassword` | `throttle:auth` | Generic safe reset link dispatch. |
| `POST` | `/api/v1/auth/reset-password` | `AuthController@resetPassword` | `throttle:auth` | Token + email + password confirmation. |
| `GET` | `/api/v1/auth/me` | `AuthController@me` | `auth:sanctum` | Current user profile & role context. |
| `POST` | `/api/v1/auth/refresh` | `AuthController@refresh` | `auth:sanctum` | Rotates current Sanctum bearer token. |
| `POST` | `/api/v1/auth/logout` | `AuthController@logout` | `auth:sanctum` | Revokes active Sanctum token. |
| `POST` | `/api/v1/auth/change-password` | `AuthController@changePassword` | `auth:sanctum` | Verifies `current_password`, updates password. |
| `GET` | `/api/v1/users/role` | `ProviderAuthController@roleContext` | `auth:sanctum`, `user.type:provider` | Provider role & permission array context. |

---

## 3. Customer Account, Booking, Reserve Requests, Payments & Vault

**Middleware (Customer routes):** `auth:sanctum`, `user.type:customer`

### Instant Booking & Reserve Requests
| Method | URI | Action | Policy / Notes |
|---|---|---|---|
| `POST` | `/api/v1/workspaces/published/{workspace}/instant-book` | `BookingController@store` | `throttle:booking`; supports `Idempotency-Key` / `idempotency_key`; pessimistic row lock prevents overbooking. |
| `GET` | `/api/v1/workspaces/instant-bookings/me` & `/api/v1/bookings` | `BookingController@index` | Customer's own bookings (filters: `status`, `buildingId`/`workspace_id`). |
| `GET` | `/api/v1/bookings/{booking}` | `BookingController@show` | `BookingPolicy::viewCustomerBooking` (`403` for other customers). |
| `POST` | `/api/v1/bookings/{booking}/cancel` | `BookingController@cancel` | `BookingPolicy::manageAsCustomer`; releases `InventoryReservation` + writes `CancellationRecord`. |
| `POST` | `/api/v1/workspaces/instant-bookings/{booking}/complete` | `BookingController@complete` | `BookingPolicy::manageAsCustomer`; requires `PAYMENT_PAID`. |
| `POST` | `/api/v1/workspaces/published/{workspace}/reserve-request` | `ReserveRequestController@store` | `throttle:inquiries`; creates or increments reminder on active request. |
| `POST` | `/api/v1/workspaces/published/{workspace}/reserve-request/reminder` | `ReserveRequestController@remind` | `throttle:inquiries`. |
| `GET` | `/api/v1/workspaces/reserve-requests/me` | `ReserveRequestController@index` | Filter by `buildingId` / `workspace_id` / `status`. |
| `POST` | `/api/v1/workspaces/reserve-requests/{reserveRequest}/cancel` | `ReserveRequestController@cancel` | `ReserveRequestPolicy::manageAsCustomer`. |

### Payments & Gateway Webhooks
| Method | URI | Action | Security / Notes |
|---|---|---|---|
| `POST` | `/api/v1/bookings/{booking}/payments/initiate` | `PaymentController@initiate` | `throttle:booking`, `BookingPolicy::manageAsCustomer`. Fails closed (`503`) if Razorpay keys unconfigured. |
| `POST` | `/api/v1/bookings/{booking}/payments/verify` | `PaymentController@verify` | `throttle:booking`, `BookingPolicy::manageAsCustomer`. Verifies HMAC signature -> marks `PAID` + `Upcoming`. |
| `POST` | `/api/v1/webhooks/payments/{gateway}` | `PaymentController@webhook` | Unauthenticated external webhook; validates `X-Razorpay-Signature` HMAC -> `403` on bad/unconfigured sig; `webhook_events.event_id` idempotent. |

### Vault, Reviews, Profile, Notifications & Support
- `GET /api/v1/workspaces/favorites`, `POST /api/v1/workspaces/{workspace}/favorite`, `DELETE /api/v1/workspaces/{workspace}/favorite` (`VaultController`)
- `POST /api/v1/workspaces/published/{workspace}/reviews` (`WorkspaceReviewController@upsert`)
- `GET /api/v1/customer/profile`, `PATCH /api/v1/customer/profile` (`CustomerProfileController`)
- `GET /api/v1/customer/notifications`, `POST /api/v1/customer/notifications/{notificationId}/read`
- `GET /api/v1/support/tickets`, `POST /api/v1/support/tickets` (`throttle:inquiries`), `GET /api/v1/support/tickets/{ticket}` (`SupportTicketPolicy::view`)

---

## 4. Provider / Vendor Portal (`/api/v1/provider`)

**Base Middleware:** `auth:sanctum`, `user.type:provider`, `provider.access` (resolves single/selected `Provider`, blocks `SUSPENDED`/`REJECTED` providers).

| Area | Method & URI | Permission Middleware | Notes |
|---|---|---|---|
| **Dashboard** | `GET /api/v1/provider/dashboard` | `providers.dashboard.view` | Workspace & booking KPIs, optional `?workspace_id=` filter. |
| **Profile & KYC** | `GET|PATCH /api/v1/provider/profile`, `POST /api/v1/provider/documents`, `GET /api/v1/provider/documents/{document}/download`, `POST /api/v1/provider/bank-accounts`, `DELETE /api/v1/provider/bank-accounts/{bankAccount}` | Owner check (`ProviderPolicy::update`) for mutations | Encrypted bank numbers returned masked (`account_last4` only); private local disk for KYC docs. |
| **Workspaces** | `GET|POST /api/v1/provider/workspaces`, `GET|PATCH|DELETE /api/v1/provider/workspaces/{workspace}`, `POST .../submit-review`, `POST .../images`, `DELETE .../images/{image}` | `providers.workspaceManagement.view` | Sanitizes HTML; enforces max 5 images; prevents self-publishing prior to Super Admin approval. |
| **Floors** | `GET|PUT /api/v1/provider/workspaces/{workspace}/floors` | `providers.floors.view` | Upserts `WorkspaceFloor` capacity configuration. |
| **Roles** | `GET /api/v1/provider/permissions`, `GET|POST /api/v1/provider/roles`, `PATCH|DELETE /api/v1/provider/roles/{role}` | `providers.roleManagement.view` | Enforces provider ownership & protects system roles. |
| **Staff** | `GET|POST /api/v1/provider/staff`, `PATCH|DELETE /api/v1/provider/staff/{staff}` | `providers.users.view` | Prevents cross-portal account attachment & protects owner membership. |
| **Reserve Requests** | `GET /api/v1/provider/reserve-requests`, `PATCH .../{reserveRequest}/status`, `POST .../{reserveRequest}/assign` | `providers.reserveRequests.view` | Notifies customer on status change. |
| **Instant Bookings** | `GET /api/v1/provider/instant-bookings`, `POST .../reset-past`, `PATCH .../{booking}/status`, `POST .../{booking}/assign` | `providers.instantBookingRequests.view` | Blocks unpaid bookings from moving to Active/Completed; releases inventory on Cancel/Complete/Reset Past. |
| **Reports & CSV** | `GET /api/v1/provider/reports/reserve-requests`, `.../export`, `GET /api/v1/provider/reports/instant-bookings`, `.../export` | `providers.reports.view` | JSON aggregates + streaming `text/csv` download. |
| **Layout Designer & Mapping** | `GET|POST /api/v1/provider/templates`, `GET|PATCH|DELETE .../{template}`, `POST .../{template}/clone`, `GET|POST /api/v1/provider/templates/mappings` | `providers.layoutDesigner.view` / `providers.templateMapping.view` | Validates canvas element geometry & strictly scopes templates/floors/workspaces to provider. |

---

## 5. Super Admin Portal (`/api/v1/admin`)

**Middleware:** `auth:sanctum`, `super.admin` (`EnsureSuperAdmin` checks `user_type = super_admin` AND Spatie `super_admin` role).

- **Dashboard:** `GET /api/v1/admin/dashboard`
- **Providers & KYC:** `GET /api/v1/admin/providers`, `GET .../{provider}`, `PATCH .../{provider}/status` (suspending unpublishes all provider workspaces), `POST .../{provider}/verify-kyc`, `GET /api/v1/admin/provider-documents/{document}/download`
- **Workspace Approvals:** `GET /api/v1/admin/workspaces`, `GET .../{workspace}`, `POST .../{workspace}/decision` (`APPROVE`, `REJECT`, `SUSPEND`, `UNPUBLISH` + `WorkspaceApprovalHistory` + owner notification)
- **Master Data:** `GET|POST /api/v1/admin/cities`, `PATCH /api/v1/admin/cities/{city}`, `GET|POST /api/v1/admin/localities`, `GET|POST /api/v1/admin/categories`, `POST /api/v1/admin/subcategories`, `GET|POST /api/v1/admin/amenities`, `PATCH /api/v1/admin/amenities/{amenity}`
- **Operations:** `GET /api/v1/admin/customers`, `PATCH /api/v1/admin/customers/{user}/status`, `GET /api/v1/admin/bookings`, `POST /api/v1/admin/bookings/{booking}/refund`, `GET /api/v1/admin/reserve-requests`, `GET|POST /api/v1/admin/coupons`, `PATCH|DELETE /api/v1/admin/coupons/{coupon}`, `GET /api/v1/admin/payments`, `GET /api/v1/admin/webhook-events`, `GET /api/v1/admin/refunds`, `GET|PATCH /api/v1/admin/consultations`, `GET|PATCH /api/v1/admin/partner-inquiries`, `GET|PATCH /api/v1/admin/support-tickets`, `GET|PUT /api/v1/admin/settings`, `GET /api/v1/admin/roles`, `GET /api/v1/admin/audit-logs`
