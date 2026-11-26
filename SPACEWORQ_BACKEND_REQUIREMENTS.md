# SPACEWORQ – Backend Requirements & Frontend Integration Mapping

**Project:** `D:\xampp-8.2\htdocs\New folder (5)\spaceworq`  
**Scope:** Backend-only Laravel 12 architecture aligned strictly with verified `spaceworq-clone/`, `spaceworq-vendor`, and `spaceworq.com` / `partners.spaceworq.com` specifications.

---

## 1. Architecture Mandates

1. **Provider & Vendor Unification:** Use `Provider`, `providers`, `provider_users`, and `ProviderUser` only. Never create a separate `Vendor` model or table.
2. **Three-Portal Separation:** Keep **Customer**, **Provider**, and **Super Admin** authentication guards/scopes, middleware, policies, and routes cleanly isolated.
3. **Zero Trust on Client Inputs:** Server calculates and enforces all pricing, slot/category rules, taxes, coupon discounts, seat availability, provider scoping, and booking totals.
4. **Fail-Closed Payment Abstraction:** Payment initiation and webhook/signature verification must fail closed when gateway credentials are unconfigured (`503 Service Unavailable` / explicit configuration exception). Never mark bookings paid without verified payment.
5. **Frontend Immutability Rule:** Both `spaceworq-clone/` and vendor frontend files remain unmodified until explicit separate approval is granted to wire `fetch()` / CSRF / form action bindings. All endpoints are designed so the frontend can integrate cleanly without altering UI layouts.

---

## 2. Frontend-to-Backend Integration Mapping

### 2.1 Public Website & Discovery (`spaceworq-clone/` + `spaceworq.com`)
| UI Component / Page | Backend Endpoint | Method | Auth / Role | Core Inputs / Outputs |
|---|---|---|---|---|
| Homepage dynamic bootstrap & featured cities | `/api/v1/public/home`, `/api/v1/cities` | `GET` | Public | Cities (`Bengaluru`, `Chennai`, `Mumbai`, `Delhi NCR`, `Hyderabad`, `Pune`), workspace counts, categories, amenities |
| Live search, category/city/amenity filters, sorting, pagination | `/api/v1/workspaces/published` | `GET` | Public | `category`, `city`, `locality`, `q`, `amenities[]`, `sort`, `page`, `per_page`; returns approved/published active workspaces only |
| Search suggestions | `/api/v1/workspaces/published/suggestions` | `GET` | Public | `q` (min 2 chars); matching cities, localities, workspace names |
| Amenities master list | `/api/v1/workspaces/published/amenities` | `GET` | Public | Active verified amenities |
| Expansion opportunities page (`expansion.html`) | `/api/v1/expansion-opportunities` | `GET` | Public | `city`, `subtype` (`ALL`, `NEW_BUILDING`, `OFFICE_COMMERCIAL`), `q` |
| Workspace details (`/space/{id}`) & gallery | `/api/v1/workspaces/published/{workspace}`, `/api/v1/workspaces/published/{workspace}/images/{image}/file` | `GET` | Public | Approved workspace public fields, pricing configs, working hours, amenities, public provider brand summary (zero private/bank/internal fields) |
| Workspace reviews | `/api/v1/workspaces/published/{workspace}/reviews` | `GET`, `POST` | Public (`GET`) / Customer (`POST`) | Rating `1-5`, comment; one review per customer per workspace (upsert) |
| Compare up to 3 spaces | `/api/v1/workspaces/published/compare` | `GET` | Public | `ids[]` (max 3) returning normalized comparison attributes |
| Enterprise consultation & Expansion inquiry | `/api/v1/consultations` | `POST` | Public (rate-limited) | `name`, `company_name`, `phone`, `city`, `seats_range` / `category`, `notes` |
| Partner inquiries (`workspace-providers`, `building-owners`, `developers`) | `/api/v1/partner-inquiries` | `POST` | Public (rate-limited) | Type (`WORKSPACE_PROVIDER`, `BUILDING_OWNER`, `DEVELOPER`), property/brand details, area/desks, rent/price, contact details |
| Support FAQ & Ticket escalation (`/support`) | `/api/v1/support/faqs`, `/api/v1/support/tickets` | `GET`, `POST` | Public (`GET` FAQs) / Authenticated (`POST`/`GET` tickets) | Subject, category (`booking`, `payment`, `cancellation`, `facility`, `general`), booking ref, message |

### 2.2 Authentication & Account Flows (Public/Customer + Provider + Admin)
| Flow | Endpoint | Method | Auth / Scope | Notes |
|---|---|---|---|---|
| Customer Register (`register.html`) | `/api/v1/auth/register` | `POST` | Guest | `first_name`, `last_name`, `email`, `mobile` (10 digits), `password` (strong policy), `password_confirmation`, `terms_accepted`; sends activation/verification link |
| Customer / Shared Login (`login.html`) | `/api/v1/auth/login` | `POST` | Guest | `identifier` (email/mobile/username) or `email`, `password`, `remember`, `redirect_to` (validated safe relative path only — open redirect protection) |
| Resend Client Activation | `/api/v1/auth/resend-client-activation` | `POST` | Guest (throttled) | `email`; generic safe response preventing enumeration |
| Email Verification / Activation | `/api/v1/auth/verify-email` | `POST` / `GET` | Signed / Token | Marks `email_verified_at` |
| OTP Request & Verify (Modal `authModal`) | `/api/v1/auth/send-otp`, `/api/v1/auth/verify-otp` | `POST` | Guest (throttled) | Generates time-limited hashed OTP (never hardcoded `123456` in production) |
| Password Reset & Change | `/api/v1/auth/forgot-password`, `/api/v1/auth/reset-password`, `/api/v1/auth/change-password` | `POST` | Guest / Auth | Standard Laravel token reset + current password check |
| Token Refresh / Logout / Current User | `/api/v1/auth/refresh`, `/api/v1/auth/logout`, `/api/v1/auth/me` | `POST` / `GET` | Auth (Sanctum) | Revokes tokens cleanly on logout |
| Provider Registration (`register.html` vendor) | `/api/v1/auth/register-provider` | `POST` | Guest | Transactional creation of `User`, `Provider`, and `ProviderUser` (owner role), initial `PENDING_VERIFICATION` status |
| Provider Activation / Resend | `/api/v1/auth/activate-provider`, `/api/v1/auth/resend-provider-activation` | `POST` | Guest / Token | Activates provider account securely |
| Super Admin Login | `/api/v1/admin/auth/login`, `/api/v1/admin/auth/logout` | `POST` | Super Admin only | Dedicated admin login check ensuring `is_super_admin` / `super_admin` role |

### 2.3 Customer Booking, Reserve Requests, Vault & Profile (`spaceworq-clone/` + `spaceworq.com`)
| Action | Endpoint | Method | Auth / Role | Requirements |
|---|---|---|---|---|
| Check Instant Booking Availability | `/api/v1/workspaces/published/{workspace}/instant-book/availability` | `GET` | Public / Customer | Validates date, slot/times, seat count against workspace working hours, capacity, and active reservations |
| Authoritative Quote & Coupon Preview | `/api/v1/workspaces/published/{workspace}/quote` | `POST` | Customer (or Public quote preview) | Calculates unit price, slot multiplier (per approved rules), discount from DB `coupons`, tax from workspace/platform config, total |
| Create Instant Booking | `/api/v1/workspaces/published/{workspace}/instant-book` | `POST` | Customer | DB transaction + `lockForUpdate()`, `Idempotency-Key` / duplicate protection, creates `bookings` + `booking_items` + `inventory_reservations` |
| Customer Instant Bookings & Details | `/api/v1/workspaces/instant-bookings/me`, `/api/v1/bookings/{booking}` | `GET` | Customer (Owner) | Policy enforces `booking.user_id === auth.id` |
| Complete / Cancel Booking | `/api/v1/workspaces/instant-bookings/{booking}/complete`, `/api/v1/bookings/{booking}/cancel` | `PATCH`, `POST` | Customer (Owner) | Enforces status transition & cancellation policy |
| Initiate & Verify Payment | `/api/v1/bookings/{booking}/payments/initiate`, `/api/v1/bookings/{booking}/payments/verify` | `POST` | Customer (Owner) | Gateway abstraction (Razorpay/configured driver); fail-closed if unconfigured; verifies cryptographic signature before confirming paid |
| Payment Webhook | `/api/v1/webhooks/payments/{gateway}` | `POST` | Signed Webhook | Signature verified, `webhook_events` idempotency table prevents double-processing |
| Reserve Request (Long-term / Virtual Office) | `/api/v1/workspaces/published/{workspace}/reserve-request`, `/api/v1/workspaces/published/{workspace}/reserve-request/reminder`, `/api/v1/workspaces/reserve-requests/me` | `GET`, `POST` | Customer | Submit inquiry/reserve request, send follow-up reminder, view own history |
| Saved Spaces / Vault (`vault`) | `/api/v1/workspaces/favorites`, `/api/v1/workspaces/{workspace}/favorite` | `GET`, `PUT`, `DELETE` | Customer | Saved workspaces list, heart toggle |
| Customer Profile & Notifications | `/api/v1/customer/profile`, `/api/v1/notifications` | `GET`, `PATCH` | Customer | Profile (`first_name`, `last_name`, `mobile`, `company_name`, `gstin`), notification read state |

### 2.4 Provider / Vendor Panel (`spaceworq-vendor` + `partners.spaceworq.com`)
| Screen | Endpoints | Permissions Required | Notes |
|---|---|---|---|
| Dashboard (`dashboard.html`) | `GET /api/v1/provider/dashboard` | `providers.dashboard.view` | Scoped to authenticated provider's `provider_id` (+ optional workspace filter); returns KPI counts (`users`, `providers`, `workspaces`, `floors`, `mapped_templates`, `total_spaces`) & recent workspaces |
| Workspace Management (`workspace-management.html`) | `GET/POST/PUT/DELETE /api/v1/provider/workspaces`, `GET /api/v1/provider/workspaces/{workspace}/floors`, `POST /api/v1/provider/workspaces/{workspace}/submit`, images & working hours endpoints | `providers.workspaceManagement.view`, `providers.floors.view` | Auto-generates workspace code (e.g. `LTL-CHECS0001`), sanitizes rich-text HTML, stores private/public images safely, enforces provider ownership & approval workflow (`DRAFT` -> `IN_REVIEW` -> `ACTIVE` via Admin) |
| Users (`users.html`) | `GET/POST/PUT/DELETE /api/v1/provider/users` | `providers.users.view` | Workspace/provider-scoped staff management (`ProviderUser`), never leaks other providers' staff |
| Roles (`roles.html`) | `GET/POST/PUT/DELETE /api/v1/provider/roles` | `providers.roleManagement.view` | Provider-scoped roles & permissions (`Manage Workspaces`, `Manage Users`, `Manage Roles`, `Approve Bookings`, `View Reports`, `Layout Designer`) |
| Reserve Requests (`reserve-requests.html`) | `GET /api/v1/provider/reserve-requests`, `PATCH /api/v1/provider/reserve-requests/{reserveRequest}/status` | `providers.reserveRequests.view` | Filter by workspace/status (`Open`, `In Progress`, `Review`, `Discussion`, `Completed`, `Cancelled`, `Rejected`), move status, assign staff, add remarks |
| Instant Booking Requests (`instant-booking-requests.html`) | `GET /api/v1/provider/instant-booking-requests`, `PATCH /api/v1/provider/instant-booking-requests/{booking}/status`, `POST /api/v1/provider/instant-booking-requests/reset-past` | `providers.instantBookingRequests.view` | Filter by status (`Upcoming`, `Active`, `In Progress`, `Review`, `Completed`, `Cancelled`), status transitions, reset expired past bookings |
| Reports (`reservation-requests-report.html`, `instant-booking-requests-report.html`) | `GET /api/v1/provider/reports/reserve-requests`, `GET /api/v1/provider/reports/instant-booking-requests`, CSV export | `providers.reports.view` | Server-aggregated KPIs, status distribution, daily counts, CSV download |
| Layout Designer (`template-management.html`, `template-mapping.html`) | `GET/POST/PUT/DELETE /api/v1/provider/templates`, `POST /api/v1/provider/templates/{template}/clone`, `GET/PUT/DELETE /api/v1/provider/template-mappings` | `providers.layoutDesigner.view`, `providers.templateMapping.view` | Strict provider ownership; floor-to-template mapping |
| Provider Profile, KYC & Payout Bank Details | `GET/PATCH /api/v1/provider/profile`, `GET/POST /api/v1/provider/documents`, `GET/PUT /api/v1/provider/bank-account` | Provider Owner / Admin | Private storage for KYC docs; bank details masked on read and never returned in public APIs |

### 2.5 Super Admin Backend
| Module | Endpoints | Notes |
|---|---|---|
| Admin Dashboard | `GET /api/v1/admin/dashboard` | Platform KPIs across customers, providers, workspaces, bookings, payments |
| Provider Verification & Management | `GET /api/v1/admin/providers`, `PATCH /api/v1/admin/providers/{provider}/status`, `PATCH /api/v1/admin/providers/{provider}/kyc` | Approve/reject/suspend providers, inspect private KYC documents |
| Workspace Approval Queue | `GET /api/v1/admin/workspaces`, `PATCH /api/v1/admin/workspaces/{workspace}/approval` | Approve/reject/suspend workspaces with `workspace_approval_histories` audit trail |
| Master Data CRUD | `/api/v1/admin/cities`, `/api/v1/admin/localities`, `/api/v1/admin/categories`, `/api/v1/admin/subcategories`, `/api/v1/admin/amenities` | Manage platform reference data |
| Coupons, Bookings, Payments, Refunds | `/api/v1/admin/coupons`, `/api/v1/admin/bookings`, `/api/v1/admin/payments`, `/api/v1/admin/refunds` | Coupon CRUD, booking oversight, payment/webhook monitoring, refund workflow |
| Customer, Inquiry, Support, Settings & Audit | `/api/v1/admin/customers`, `/api/v1/admin/consultations`, `/api/v1/admin/partner-inquiries`, `/api/v1/admin/support-tickets`, `/api/v1/admin/settings`, `/api/v1/admin/audit-logs`, `/api/v1/admin/roles` | Full RBAC and immutable audit logs |

---

## 3. Business Decisions Requiring Explicit Approval (Fail-Closed / Configurable Until Approved)

1. **GST & Tax Calculation:** `spaceworq-clone/js/main.js` uses `18%` GST and `spaceworq-vendor/workspace-management.html` includes `GST/VAT enabled` + `GST/VAT Rate (%)` (default input `18`). Backend will allow per-workspace/platform configurable tax settings and default to `0%` / unconfigured unless explicitly enabled in settings/workspace configuration.
2. **Booking Hold Duration:** Unpaid booking inventory hold timeout (e.g., 10 vs 15 minutes) — configurable via `system_settings` (`booking_hold_minutes`), disabled/requiring explicit setting when unconfigured.
3. **Half-Day / Full-Day Availability & Pricing Multiplier:** `spaceworq-clone` displays `0.65` of daily rate for `morning` / `afternoon`. Requires business confirmation before enforcing a fixed platform multiplier; configurable per `workspace_listing_configs`.
4. **Seat Capacity Limits:** `spaceworq-clone` caps online instant booking at `25` seats (above 25 routes to Enterprise consultation), while workspaces track `total_seats` and `available_seats`.
5. **Coupon Conditions:** Exact eligibility rules (min order, max discount cap, per-user limit, category restrictions, valid dates) — implemented via `coupons` table without hardcoding promo codes in application logic.
6. **Cancellation & Refund Policy:** Official bundle support text states "full refund if cancelled 2+ hrs before start time", pending business sign-off via configurable policy settings (`cancellation_cutoff_hours`, refund fee %).
7. **Payment Gateway:** Official web bundle shows Razorpay; backend implements gateway driver interface failing closed when credentials (`RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, `RAZORPAY_WEBHOOK_SECRET`) are missing.
8. **Pay-at-Venue Availability:** Disabled (`false`) by default until explicitly enabled.
9. **Non-Day-Pass Booking Rules:** Official platform separates `instant-book` (Day Pass / Meeting Room) from `reserve-request` (Long-term Leasing / Virtual Office), whereas `spaceworq-clone` enables the Instant Booking modal across cards. Backend supports explicit `booking_mode` (`instant_book`, `reserve_request`, `both`) on category/workspace listing configuration.
