# SpaceWorq Responsive Web Clone

A faithful, responsive recreation of **[SpaceWorq](https://www.spaceworq.com/)** built with semantic HTML5, pure Vanilla CSS, and modern interactive JavaScript.

---

## 📁 Project Folder Structure

```text
spaceworq-clone/
│
├── index.html                  # Main responsive HTML5 landing page (Find a Workspace)
├── expansion.html              # Expansion Opportunities page
├── workspace-providers.html    # Partner page: For Workspace Providers
├── building-owners.html        # Partner page: For Building Owners
├── developers.html             # Partner page: For Developers
├── login.html                  # Authentication / Sign In page (Supports ?redirectTo=...)
├── register.html               # Registration / Sign Up page (Client user onboarding)
├── auth/
│   ├── login.html              # Dedicated Auth sub-route (/auth/login?redirectTo=...)
│   └── register.html           # Dedicated Auth sub-route (/auth/register)
├── partner/
│   ├── workspace-providers.html# Partner sub-route mirror (/partner/workspace-providers)
│   ├── building-owners.html    # Partner sub-route mirror (/partner/building-owners)
│   └── developers.html         # Partner sub-route mirror (/partner/developers)
├── README.md                   # Project documentation
│
├── css/
│   ├── style.css               # Core styling, design tokens & components
│   └── responsive.css          # Breakpoints for mobile, tablet, and drawer
│
├── js/
│   ├── data.js                 # Workspace listings, cities, categories, amenities
│   └── main.js                 # Interactive controller & toggles
│
└── assets/
    └── images/
        ├── logo.png            # SpaceWorq brand logo
        ├── space1.png          # High-resolution workspace photo 1
        ├── space2.png          # High-resolution workspace photo 2
        ├── space3.png          # High-resolution workspace photo 3
        └── cities/
            ├── bangalore.jpg   # Bengaluru city card image
            └── chennai.jpg     # Chennai city card image
```

---

## 🚀 How to Run

1. **Direct Browser Preview (Zero Installation / No Python Needed):**
   - Double-click [`index.html`](file:///C:/Users/DELL/.gemini/antigravity-ide/scratch/spaceworq-clone/index.html) in Windows File Explorer or open it in any web browser (Chrome, Edge, Firefox).
   - This project is 100% client-side HTML, CSS, and JavaScript. No Python, Node.js, or server build step is required.

2. **Optional Web Server:**
   - You can also open the project folder in VS Code with the Live Server extension.

---

## ⚡ Interactive Toggles & Features

1. **Mobile Menu & Navigation Drawer Toggle:**
   - Animated hamburger button transforms into an "X" icon.
   - Smooth slide-out mobile drawer with backdrop overlay for mobile & tablet screens.

2. **Hero Quick-Nav Category Chips:**
   - Toggle between **Long-term Leasing**, **Virtual Office**, **Meetings & Events**, and **Day Pass**.
   - Dynamically updates the active category and filters the workspace catalog.

3. **City Selector Dropdown Toggle:**
   - Interactive dropdown menu supporting all major Indian tech hubs: **Bengaluru**, **Mumbai**, **Delhi NCR**, **Chennai**, **Hyderabad**, and **Pune**.
   - Filters featured cities and live workspaces instantly.

4. **Live Search with Suggestions Dropdown:**
   - Real-time search by city, locality, or workspace name.
   - Dynamic suggestion popup and one-click clear button (`×`).

5. **Featured Cities Carousel:**
   - Interactive scroll navigation buttons (◀ / ▶).
   - Clicking any city card sets the active filter and smoothly scrolls to the workspace catalog.

6. **Amenities Accordion Toggle:**
   - Expandable/collapsible amenities drawer.
   - Multi-select amenity chips (High-Speed WiFi, Ergonomic Chairs, Power Backup, Cafeteria, Meeting Rooms, 24/7 Access, Metro Nearby, Parking).

7. **Multi-Space Comparison Engine & Modal:**
   - Checkbox toggle on workspace cards (up to 3 spaces).
   - Floating comparison pill showing live selected count.
   - Full side-by-side comparison modal displaying Pricing, Capacity, Ratings, Amenities, and instant booking actions.

8. **Instant Booking Modal & Dynamic Price Calculator:**
   - Interactive date picker and slot selection (Morning, Afternoon, Full Day).
   - Real-time seat counter (`[-]` / `[+]`) with dynamic price, GST (18%), and subtotal recalculation.
   - Working coupon code applicator (supports `WKND20` for 20% off, `NEW100` for ₹100 off, `WORQ500` for ₹500 off).
   - Instant booking confirmation with toast notification.

9. **Save to Vault (Heart Toggle):**
   - Click the heart button on any card to save/remove spaces to your Vault.
   - Live badge counter in the navbar updates dynamically.

10. **Enterprise Consultation Modal:**
    - "Talk to an Expert" consultation form for managed office inquiries.

11. **Sign-In Modal with OTP Verification:**
    - Two-step authentication modal demo with 6-digit code verification.
