# SPACEWORQ – Phase 0 Frontend Inventory & Reference Audit

**Audit Date:** 2026-11-26  
**Laravel Project Path:** `D:\xampp-8.2\htdocs\New folder (5)\spaceworq`  
**Audit Status:** COMPLETED (Read-only inspection; zero reference files modified)

---

## 1. Environment & Path Reconciliation

### 1.1 Confirmed Runtime Versions
- **PHP:** `8.2.12` (`D:\xampp-8.2\php\php.exe`, CLI ZTS Win64)
- **Laravel Framework:** `12.74.0` (`laravel/laravel` skeleton, `"laravel/framework": "^12.0"`)
- **Composer:** `2.8.4` (`2024-12-11`)
- **Node.js:** `v22.22.2`
- **npm:** `10.9.7`
- **MySQL / MariaDB:** `10.4.32-MariaDB` (`D:\xampp-8.2\mysql\bin\mysql.exe` Ver 15.1)
- **Active Fresh Starter Test Check (`php artisan test`):** `2 passed (2 assertions)` (`0.61s`)

### 1.2 Directory & Reference Path Audit
| Reference / Target | Prompt Expected Path | Actual Observed Path on Disk | Notes |
|---|---|---|---|
| Fresh Laravel Project | `D:\xampp-8.2\htdocs\New folder (5)\spaceworq-fresh` | `D:\xampp-8.2\htdocs\New folder (5)\spaceworq` | `spaceworq-fresh` does not exist; `PHASE_0_FRESH_SETUP_REPORT.md` inside `spaceworq/` confirms `spaceworq` is the fresh Laravel 12 directory. |
| Public/Customer Frontend | `spaceworq/spaceworq-clone/` | `D:\xampp-8.2\htdocs\New folder (5)\spaceworq\spaceworq-clone\` | Contains all 24 files (matching parent `spaceworq-clone/`). |
| Provider/Vendor Frontend | `spaceworq/spaceworq-vendor/` | `D:\xampp-8.2\htdocs\New folder (5)\` (loose HTML + `assets/`) and `D:\xampp-8.2\htdocs\New folder (5)\spaceworq-vendor.zip` | Inside `spaceworq/`, `spaceword-vendor/` (spelled `spaceword-vendor`) exists and is empty; `spaceworq-vendor/` does not exist inside `spaceworq/`. All 32 vendor frontend files in `D:\xampp-8.2\htdocs\New folder (5)\` match `spaceworq-vendor.zip` byte-for-byte. |
| Legacy Project (Do Not Copy) | `D:\xampp-8.2\htdocs\New folder (5)\spaceworq-laravel` | Preserved untouched | Not copied from. |
| Legacy Database (Do Not Touch) | `spaceworq_db` | Preserved untouched | Automated tests use isolated SQLite `:memory:`. |

---

## 2. SHA-256 Integrity Baseline (Recorded Before Phase 1)

### 2.1 `spaceworq-clone/` (24 Files)
| File | SHA-256 |
|---|---|
| `spaceworq-clone/README.md` | `D8F59D69455565BA0B5E95137E1501A345703AED7AF8EEEE109AEC41D37F8FED` |
| `spaceworq-clone/assets/images/cities/bangalore.jpg` | `8F458FEFA3B40413015B29754E68F9DAA5DF5078D3F2A0E6A69F2EFB6AE729CB` |
| `spaceworq-clone/assets/images/cities/chennai.jpg` | `AFDA1C770BC5B9E3B89276C8BD8A8DC343A9C821D17358617A221B3C0B742EA2` |
| `spaceworq-clone/assets/images/cities/hyderabad.jpg` | `F0A662125F58BE23442E9054AFB206728546FA455B34B2F90B7E95AFA3A2BCBD` |
| `spaceworq-clone/assets/images/logo.png` | `65F5AD4EC0A948312748CA0B83B6EC74BE738E348A23B705923D2EDFB771774A` |
| `spaceworq-clone/assets/images/space1.png` | `B8A4B9BB89E5952828AF273E287702C064BE59C4B69607E11D5365AD967E8DFE` |
| `spaceworq-clone/assets/images/space2.png` | `2AD75C7F577695AF413DCAC485E61BBBD0B74E20959AFB5F78FFBD1385710769` |
| `spaceworq-clone/assets/images/space3.png` | `EE08D6AAA48F9BE2B0FC4781F1965AF8BC57CA4F4A2A735C1F12B3C3B263FFB5` |
| `spaceworq-clone/auth/login.html` | `4D559D20AB093BCF6DC746EC0DC7AA412B6E2974D2D9263E9CF3A11DBA28DCA3` |
| `spaceworq-clone/auth/register.html` | `5AB3AF31273940EA6B0CFD309DF5BD27D113F7D0E6C76FE30731D242040EF430` |
| `spaceworq-clone/building-owners.html` | `783A65A4153C301034C7750641AC953839A847716A975942531FB80D4755A811` |
| `spaceworq-clone/css/responsive.css` | `D95173C397451E1A55876BD8EA53AEE2F503A6341384D329C5C0E2B60AD2AEF0` |
| `spaceworq-clone/css/style.css` | `55BE4153C789BC566D1394C57E8D1A2D1C7EC0B05139DAF0CD471F951002089B` |
| `spaceworq-clone/developers.html` | `E30400CFDB20621C4F9DC1AFCB8F9C6CA9D2E2B9387904CDE5E24F372041DDC4` |
| `spaceworq-clone/expansion.html` | `32B963F4DE827AC2EE422373103117DE0856B7C72992D81B80734DD4921978C8` |
| `spaceworq-clone/index.html` | `879AA546DE19AA60881605BFCE9861907779CE27C8418CDDB47082B6D4FCDE00` |
| `spaceworq-clone/js/data.js` | `DAC933790A558FAF64F0D3788DFC182947E0F6BD2E5DA008767A6E03AB538FD5` |
| `spaceworq-clone/js/main.js` | `6E2BA375B3CE01A9DE169FA2B56C442720A8992365FE9D511A9F7B572068C8B9` |
| `spaceworq-clone/login.html` | `82FCA4ABF3CAF3BE2C57D3A02C584B8A169F0CCE30E4F980356E6C34F4B4C516` |
| `spaceworq-clone/partner/building-owners.html` | `A504394AE97C3917A0C3446D008D36701C12AEAEC33BCAE081BC277ED06CB80A` |
| `spaceworq-clone/partner/developers.html` | `E6E19CE2520A24EA7BBDD4B544D72F95B6E86C40C63C84BBD17CE3C96AD6832F` |
| `spaceworq-clone/partner/workspace-providers.html` | `C8018A0BBB5BBFE10A4559D5AD519602898A2F29241C304DE5A00FE7B910B91F` |
| `spaceworq-clone/register.html` | `2F12082B2E864AE13ED0C8FB790718F343F66FE74C534A7B570D727ADBCC33A5` |
| `spaceworq-clone/workspace-providers.html` | `79E2F270B0A7AAE2C9C118AFD506DAFD4E47C754893EB336D61A3DBF27B92D75` |

### 2.2 `spaceworq-vendor` Reference Files (32 Files in `D:\xampp-8.2\htdocs\New folder (5)\`, matching `spaceworq-vendor.zip`)
| File | SHA-256 |
|---|---|
| `assets/css/auth.css` | `484C4A5A0B8BACE16A4116BEBB028B3C7D5A28C9A25748382879EA4EB202F815` |
| `assets/css/dashboard.css` | `A423F3270F05AF72032CE138AD1825D22FAB371BDE2BF1BFA83B7B0CB00A3089` |
| `assets/css/reports.css` | `02BE448F16B7CE2BBDA3CFDA1A1D3C8FBC42E65416EE1A222E577BB6619D1BAD` |
| `assets/css/reserve-requests.css` | `93723E169F2AC431D03022091EF47C1C95E080FD559D2DD456ED522CF2325958` |
| `assets/css/roles.css` | `BC7CE8FB1B59F74E9FCB69ABB3E822DA0DE698F92AA1AC1D40BAF7311EFC60B4` |
| `assets/css/template-management.css` | `62AAEA881BF7F53832A582EE79C100A00E8182795DB4FA659B8D38512BF1F3DC` |
| `assets/css/template-mapping.css` | `487E45828A2349F9A7105E698D03591587EC8D3DA1D6717F995432CDFC4FECBE` |
| `assets/css/users.css` | `0DBEF488BDD45ADED66030C673A8CD8ED1EC71735A7198E1D9FE79A468E2ACAD` |
| `assets/css/workspace-management.css` | `5282B337E46A861DEA85BCE1781E6E571D92CF209B6D6B6AD599758A8CB56106` |
| `assets/js/auth.js` | `0A97D8DC379DB8C953947EF8FC66F7BA4C88EE9DAD8748688714A0936B2D32E8` |
| `assets/js/dashboard.js` | `D2EE38A595D93D0C60EE13AB018DECDEE2E14EABF7E4E16AE9D23B730B530553` |
| `assets/js/instant-booking-requests.js` | `F08AF1F49510993FAD630EC888BC0D611F2BF56153F082C5E08397604F93FB6B` |
| `assets/js/reports.js` | `CB98A8E96AE11F5DAEC813AEC0B6E8D85C4F1D99DE879B1592C810692F8491D8` |
| `assets/js/reserve-requests.js` | `DBBE9D0CD29F9D0C0C08D4BE5D5B4E1D755DC46010766E6B55C1AA81FB786EDE` |
| `assets/js/roles.js` | `3C3A33E6D1A514ED97E9D46BE7AE5D2301829C4B00F6F62C6451B8BB5B2221E8` |
| `assets/js/template-management.js` | `5DC2DC76946354A156D6052492EC959BD280263316DAA1C6899D50A390D0DCFE` |
| `assets/js/template-mapping.js` | `F67DA8D8B9D88E7FE5557B9554C313C469F888C92E41F67A1FADE7300101D5A4` |
| `assets/js/users.js` | `2BF62DE0184D86393D100148A2BBE872B5985366FB7026D7F2C5E95FC7902CFF` |
| `assets/js/workspace-management.js` | `BD00490A20662DC2B765904B4BD458669ADB36DC129964DB497DD391CD753C35` |
| `dashboard.html` | `E5521C6366F21C8AD31F7233FA3163735AE7F4FF2B85EFAEF3489264CCD2491E` |
| `index.html` | `64B0E4D92A3F0234793D3188FA0FFEBF22BC77CD6298A877383BD3925C944FA6` |
| `instant-booking-requests.html` | `0D95023310541549D14A15FAE448CFA099B5C2C2C44F4DFAFE0373C3AC5A3BB9` |
| `instant-booking-requests-report.html` | `0D4A8163FC04D70C9A606FAD1A1FA860A280D35BCC3D4D6306902108BD63C20F` |
| `login.html` | `E670C5CF03BAC7B03FA49270664B7A09C88AFC6643167A371BCE8860ED7D8056` |
| `register.html` | `BA4BCC938C33B4D31A5556C5A3CCC72893B4FC4607E7C7BEF8B27A5E4AC35886` |
| `reservation-requests-report.html` | `EFE44B2AA2D6D7E1CFD6E336BC8B4BC1B5A5FC4C0CC4227C3ABFE55D1784F2A2` |
| `reserve-requests.html` | `43CAC8753F6A53DAE9D04CC653685B375FED39CAAA23AED34C5FA43ADEB74741` |
| `roles.html` | `CB2220AC3390B4741CF2CDA748C44093095FD7BAE4AF8D7322DEA332C8A0A87A` |
| `template-management.html` | `06E7A8B1FF8E9A7EB8E4CD0A79D90D9303226A0DBBA4F54A270EA7607F2AD5D2` |
| `template-mapping.html` | `94558948E9505DADDE6A01D6D5610346545E5073E9A30BF07A9E69689197C330` |
| `users.html` | `747F4559A8F55383154301C7FE24FF027659B41C2B1C6BA356A72C6C4DFE15E1` |
| `workspace-management.html` | `C25701E5BD0A59236AA4C895CDDA39CB3DF922533568B9B1C4437BC089C3AEBB` |

---

## 3. `spaceworq-clone/` Page-by-Page & JS/CSS Inventory

### 3.1 Pages & Mirror Routes
1. `index.html` – Public Workspace Discovery & Booking Home
2. `expansion.html` – Expansion Opportunities (New Building & Office/Commercial search)
3. `workspace-providers.html` & `partner/workspace-providers.html` – Workspace Provider Partner Onboarding
4. `building-owners.html` & `partner/building-owners.html` – Building Owner Submission & Lease Models
5. `developers.html` & `partner/developers.html` – Commercial Developer Upcoming Project Submission
6. `login.html` & `auth/login.html` – Email/Password Sign In, `?redirectTo=` handling, Resend Activation Email modal
7. `register.html` & `auth/register.html` – Client Registration (`firstName`, `lastName`, `regEmail`, `regMobile`, `regPassword`, `regConfirmPassword`, terms agreement, activation flow)

### 3.2 Shared Assets & Interactive Components
- **CSS:** `css/style.css` (2,458 lines: design tokens, sticky header, hero panel, cards, modals, toast container), `css/responsive.css` (breakpoints & drawer).
- **JS:** `js/data.js` (cities, 4 categories, howItWorks, benefits, sample workspace catalog, amenities), `js/main.js` (1,162 lines: filter/search/sort engine, city carousel, amenities multi-select, Vault favorites counter, 3-space comparison modal, instant booking calculator modal, enterprise consultation modal, OTP sign-in modal, floating Instant Booking + Login Required modal).
- **Important Observation:** All form submissions and data filters in `spaceworq-clone/` currently use static `SpaceWorqData`, `localStorage`, or client-side toast demos without `fetch()` calls.

### 3.3 Exact Forms, Modals & Controls in `spaceworq-clone/`
- **Search & Filter Controls (`index.html` + `main.js`):**
  - Category chips/tabs: `all`, `LONG_TERM_LEASING`, `VIRTUAL_OFFICE`, `MEETINGS_EVENTS`, `DAY_PASS`
  - City selector: `all`, `bengaluru`, `chennai`, `mumbai`, `delhi`, `hyderabad`, `pune`
  - Search input (`heroSearchInput`) + autocomplete suggestions (top 5 matching name/city/locality)
  - Amenities multi-select pills (`High-Speed WiFi`, `Ergonomic Chairs`, `Power Backup`, `Cafeteria`, `Meeting Rooms`, `24/7 Access`, `Parking`, `Metro Nearby`, `Receptionist`)
  - Sort dropdown: `recommended`, `priceLowHigh`, `priceHighLow`, `rating`
  - Vault heart toggle (`favorite-btn`, `vault-count` badge)
  - Compare checkbox (`compare-checkbox`, max 3 spaces) + Comparison Modal
- **Instant Booking Modal (`#bookingForm`):**
  - Fields: `bookingDate`, slot (`morning` 09:00-13:00, `afternoon` 13:00-17:00, `fullday` 09:00-20:00), `seatCount` (1-25), `bookerName`, `bookerEmail`, `bookerPhone`, `couponInput`
  - Client demo pricing: morning/afternoon uses `0.65` multiplier, GST `18%`, demo coupons `WKND20`, `NEW100`, `WORQ500` (must NOT be trusted server-side; all calculations must be server-authoritative and business rules approved).
- **Enterprise Consultation Modal (`#consultationForm`):**
  - Fields: `consultName`, `consultCompany`, `consultPhone`, `consultCity`, `consultSeats` (`10-25`, `25-50`, `50-100`, `100+`)
- **OTP Sign-In Modal (`#authFormStep1` & `otpInput`):**
  - Fields: `authContactInput` (phone/email), `otpInput` (6 digits)
- **Expansion Page (`expansion.html`):**
  - Filters: city (`all`, `bengaluru`, `mumbai`, `delhi`, `chennai`, `hyderabad`, `pune`), subtype (`ALL`, `NEW_BUILDING`, `OFFICE_COMMERCIAL`), search query (`expSearchInput`)
  - Consultation Modal adds category selector (`Workspace Provider`, `Building Owner`, `Developer`)
- **Partner Modals:**
  - `workspace-providers.html` (`#partnerListingForm`): `centreName`, `providerCity`, `locality`, `totalDesks`, `deskPrice`, `contactPerson`, `contactPhone`, `contactEmail`
  - `building-owners.html` (`#buildingSubmitForm`): `bldgName`, `bldgCity`, `bldgLocality`, `bldgArea`, `bldgFloorPlate`, `bldgModel` (`Straight Lease`, `Revenue Share`, `Management Contract`, `Open to discussion`), `bldgRent`, `bldgContact`, `bldgPhone`, `bldgEmail`
  - `developers.html` (`#projectSubmitForm`): `projName`, `projCity`, `projMicroMarket`, `projTotalArea`, `projFloorPlate`, `projStage`, `projDate`, `projRent`, `projDevBrand`, `projContact`, `projPhone`, `projEmail`
- **Client Auth (`login.html`, `register.html`):**
  - Login (`#loginForm`): `email`, `password`, `redirectTo` query param; `#resendForm`: `resendEmail`
  - Register (`#registerForm`): `firstName`, `lastName`, `regEmail`, `regMobile` (10 digits), `regPassword` (8+ with upper/lower/number/special), `regConfirmPassword`, `agreeRow` terms checkbox; resend activation button on success screen.

---

## 4. `spaceworq-vendor` Page-by-Page & JS/CSS Inventory

### 4.1 Pages
1. `index.html` – Meta refresh redirect to `login.html`
2. `login.html` (`assets/css/auth.css`, `assets/js/auth.js`) – Identifier (`Email / Mobile / Username`), `password`, `remember`, Forgot Password modal (`forgotEmail`), Resend Activation modal (`activationEmail`), Contact Admin modal
3. `register.html` (`assets/css/auth.css`, `assets/js/auth.js`) – Partner Account Registration (`first_name`, `last_name`, `email`, `mobile`, `password`, `confirm_password`, password strength meter)
4. `dashboard.html` (`assets/css/dashboard.css`, `assets/js/dashboard.js`) – Provider/Workspace Context Selector (`All Workspaces` or specific provider/workspace), 5 KPI cards (`System Users`, `Active Providers`, `All Workspaces`, `All Floors`, `Mapped Templates`), Recent Workspaces list, Workspace Overview (`Total Workspaces`, `Total Floors`, `Total Spaces`), Profile Settings / Sign out dropup
5. `workspace-management.html` (`assets/css/workspace-management.css`, `assets/js/workspace-management.js`) – Summary chips (`workspaces`, `floors`, `cities`), Category Filter, Paginated Table (`5/10/25` rows, columns: Name/Code/City/Landmark, Category/Subcategory, Address, Floors, Created By, Created At, Updated At, Status [`ACTIVE`, `In Review`, `INACTIVE`], Actions: Floors Modal, Edit Drawer, Delete Modal), Full Add/Edit Slide-Over Drawer (Workspace Name, Rich Text Description, Near By Place, Total Floors, Total Seats, Available Seats, Full Address, Location Search + Leaflet Map + Lat/Lng + Place ID + City + Country + Pincode, Working Hours Mon-Sun with Closed toggle, Up to 5 Images, 12 Amenity checkboxes, Workspace Type pills + Listing Configuration cards + Subcategory price or Upcoming date, Tax Config `GST/VAT enabled` + `Rate (%)`)
6. `users.html` (`assets/css/users.css`, `assets/js/users.js`) – Workspace-scoped Staff Directory, Register/Edit/Delete User modal (`firstName`, `lastName`, `email`, `mobile`, `role`, `status`: `ACTIVE`/`INACTIVE`)
7. `roles.html` (`assets/css/roles.css`, `assets/js/roles.js`) – Workspace-scoped Role Definitions (`name`, `description`, `permissions`: `Manage Workspaces`, `Manage Users`, `Manage Roles`, `Approve Bookings`, `View Reports`, `Layout Designer`), Create/Delete Role
8. `reserve-requests.html` (`assets/css/reserve-requests.css`, `assets/js/reserve-requests.js`) – Filter by Provider Workspace & Status (`Open`, `In Progress`, `Review`, `Discussion`, `Completed`, `Cancelled`, `Rejected`), Status count pill bar, Table (`Request ID`, `Requester`, `Workspace`, `Request`, `Reminders`, `Current Status`, `Remarks`, `Assigned To`, `Move To` select, `Updated`)
9. `instant-booking-requests.html` (`assets/css/reserve-requests.css`, `assets/js/instant-booking-requests.js`) – Filter by Provider Workspace & Status (`Upcoming`, `Active`, `In Progress`, `Review`, `Completed`, `Cancelled`), `Reset Past Bookings` button, Status count pill bar, Table (`Request ID`, `Requester`, `Workspace`, `Instant Booking`, `Current Status`, `Remarks`, `Assigned To`, `Move To` select, `Updated`)
10. `reservation-requests-report.html` (`assets/css/reports.css`, `assets/js/reports.js`) – Filters (`fromDate`, `toDate`, `status`, `workspace`, `location`, `category`), 3 KPI cards (`Total Requests`, `Workspaces`, `Locations`), Donut Chart, Requests by Date bars, Summary pills, Report table, Export CSV
11. `instant-booking-requests-report.html` (`assets/css/reports.css`, `assets/js/reports.js`) – Same analytics layout specialized for Instant Booking statuses
12. `template-management.html` (`assets/css/template-management.css`, `assets/js/template-management.js`) – Interactive Drag-and-Drop Floor Plan Canvas (`desk`, `meeting`, `cabin`, `lounge`, `reception`, `booth` with `label`, `x`, `y`, `width`, `height`, `seats`, `color`), Zoom, Save New Template modal (`name`, `description`, optional `assignedFloor`), Load / Clone / Update / Delete template
13. `template-mapping.html` (`assets/css/template-mapping.css`, `assets/js/template-mapping.js`) – Search provider workspaces, expand floor accordions `1..N`, Map / Update / Unmap template per floor

---

## 5. Official Website (`spaceworq.com` & `partners.spaceworq.com`) Verification Comparison

Live inspection of the deployed official bundles (`https://www.spaceworq.com` and `https://partners.spaceworq.com`) verified additional real-world platform capabilities beyond the static HTML clones:
1. **Verified Customer Routes & API Contracts:**
   - Auth: `/auth/login`, `/auth/register`, `/auth/register-provider`, `/auth/activate-provider`, `/auth/resend-client-activation`, `/auth/change-password`, `/auth/refresh`, `/auth/send-otp`, `/auth/verify-otp`
   - Discovery & Details: `GET /workspaces/published`, `GET /workspaces/published/amenities`, `GET /workspaces/published/{id}`, `GET /workspaces/published/{id}/images/{imageId}/file`, `GET/POST /workspaces/published/{id}/reviews`
   - Reserve Requests (Non-instant workspaces): `GET/POST /workspaces/published/{id}/reserve-request`, `POST /workspaces/published/{id}/reserve-request/reminder`, `GET /workspaces/reserve-requests/me`
   - Instant Bookings: `GET /workspaces/published/{id}/instant-book/availability`, `POST /workspaces/published/{id}/instant-book`, `GET /workspaces/instant-bookings/me`, `PATCH /workspaces/instant-bookings/{id}/complete`
   - Saved Spaces / Vault: `GET /workspaces/favorites`, `PUT/DELETE /workspaces/{id}/favorite`
   - Customer Tabs: `/(tabs)/bookings`, `/(tabs)/vault`, `/(tabs)/wallet`, `/(tabs)/profile`, `/space/[id]`, `/support`
   - Payment Gateway UI: **Razorpay** UI modal present; support mentions payment retry, refund timelines, GST invoice, and cancellation flow.
2. **Verified Partner Portal Routes & Permissions:**
   - Auth: `/auth/login`, `/auth/register-provider`, `/auth/activate-provider`, `/auth/resend-provider-activation`, `/auth/forgot-password`, `/auth/reset-password`, `/auth/change-password`, `/auth/logout`, `/auth/refresh`, `/users/role`
   - Permission Keys: `admin.read`, `providers.dashboard.view`, `providers.workspaceManagement.view`, `providers.floors.view`, `providers.users.view`, `providers.roleManagement.view`, `providers.reserveRequests.view`, `providers.instantBookingRequests.view`, `providers.reports.view`, `providers.layoutDesigner.view`, `providers.templateMapping.view`

### Key Design vs Official Gap Summary
- `spaceworq-clone/` covers homepage discovery, search, expansion, partner pages, login/register, OTP modal, booking modal, and vault badge, while **official `spaceworq.com`** also includes Space Details (`/space/{id}`), Reviews, Reserve Request submission/reminders, Customer Bookings/Profile/Vault/Wallet tabs, Razorpay payment modal, and Support FAQ/ticket escalation.
- `spaceworq-vendor` (`AdminPanel`) matches `partners.spaceworq.com` closely across Dashboard, Workspace Management, Users, Roles, Reserve Requests, Instant Booking Requests, Reports, Template Management, and Template Mapping, while the official partner portal additionally exposes Provider Activation, Password Reset, and Workspace Approval / Admin read scopes.
