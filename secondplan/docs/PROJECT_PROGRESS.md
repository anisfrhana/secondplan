# SECONDPLAN - Project Progress

## Project Overview

**SECONDPLAN** is a band management and event booking platform built with PHP (procedural), MySQL, and vanilla JS. It supports three user portals: Admin, Band Member, and Customer.

**Version:** 2.0.0
**Stack:** PHP 8.x / MySQL / HTML5 / CSS3 / Vanilla JS
**Currency:** MYR (Malaysian Ringgit)
**Timezone:** Asia/Kuala_Lumpur

---

## Architecture

```
secondplan/
├── admin/          Admin panel (dashboard, CRUD APIs, assets)
├── api/            JSON API endpoints (notifications, events, tasks, cart)
├── auth/           Login, register, logout, forgot/reset password
├── band/           Band member portal (tasks, expenses, events, profile)
├── user/           Customer portal (dashboard, bookings, merchandise, cart, tasks, profile)
├── config/         Configuration, bootstrap, SQL schema (.htaccess protected)
├── includes/       Database, session, functions, auth helpers (.htaccess protected)
├── uploads/        User-uploaded files (posters, receipts)
├── logs/           Application error logs (.htaccess protected)
├── docs/           Project documentation
├── index.php       Public landing page
├── main.css        Landing page styles
└── main.js         Landing page scripts
```

---

## Implemented Changes

### 2026-02-04 - Foundation Overhaul

#### 1. Eliminated duplicate function definitions
- **functions.php**: Removed duplicates of `isLoggedIn`, `getUserRole`, `currentUser`, `generateCSRF`, `verifyCSRF`, `get_current_user` (PHP built-in collision), and `format_date` alias
- **auth_functions.php**: Removed duplicates of `startSession`, `destroySession`, `isLoggedIn`, `getUserId`, `getUserRole`
- **session.php** remains the single source of truth for auth/session/CSRF functions

#### 2. Fixed config loading architecture
- **config.php**: Removed auto-loading of includes (lines 103-114). Now contains only constant definitions.
- **bootstrap.php**: Single entry point that loads config -> database -> session -> functions -> auth_functions
- All files now use `require_once bootstrap.php` instead of mixing config.php/bootstrap.php/db.php

#### 3. Environment-based error display
- `display_errors` now conditional on `APP_ENV` (on in development, off in production)
- Error logging always enabled to `logs/error.log`

#### 4. Directory and access protection
- Created `uploads/` directory with `.gitkeep`
- Created `logs/` directory with `.gitkeep`
- Created `api/` directory for JSON endpoints
- Added `.htaccess` (Deny from all) to `config/`, `includes/`, `logs/`

#### 5. Fixed all include paths
- `admin/dashboard.php`: Changed from config.php to bootstrap.php
- `band/my_tasks.php`, `my_expenses.php`, `events.php`, `expenses.php`, `update_task_status.php`, `profile.php`: Changed from config.php to bootstrap.php
- `auth/login.php`: Removed double include (config.php + bootstrap.php), now single bootstrap.php
- `auth/register.php`: Cleaned up, removed duplicate CSRF token outside form, fixed role values
- `user/dashboard.php`, `booking.php`, `booking_save.php`, `merchandise.php`, `tasks.php`: Rewritten from broken mysqli/db.php to PDO/bootstrap.php
- `band/dashboard.php`: Replaced `get_current_user()` with `getUserData()`, `format_date()` with `formatDate()`, removed non-existent header.php/footer.php includes

#### 6. Login rate limiting
- Added `getLoginAttempts()`, `isLoginLocked()`, `logFailedLogin()` to auth_functions.php
- Login.php now enforces `MAX_LOGIN_ATTEMPTS` (5) with `LOGIN_LOCKOUT_TIME` (15 min)
- Failed attempts tracked in `activity_log` table
- Shows remaining attempts warning when down to 3
- Updates `last_login` timestamp on successful login
- Respects `redirect_after_login` session key

#### 7. File upload validation
- Added `uploadFile()` to functions.php
- Validates MIME type via finfo (not file extension)
- Enforces size limits from config constants
- Generates cryptographically random filenames
- Auto-creates upload directory if missing
- Used in: booking_save.php, band/expenses.php

#### 8. Forgot password / Reset password flow
- **forgot-password.php**: Complete rewrite. Uses bootstrap, real CSRF, database lookup. Generates secure token stored in `users.reset_token` with 1-hour expiry. Timing-safe (always shows same message).
- **reset-password.php**: New file. Validates token + expiry, allows password change, clears token after use. Activity logged.

#### 9. Notification system
- Added `createNotification()` and `getUnreadNotificationCount()` to functions.php
- Created `api/notifications.php` endpoint (GET list/count, POST mark_read/mark_all_read)
- Notifications created on: booking submission, expense submission, order placement
- Admin receives notifications for new bookings and expenses

#### 10. Shopping cart and checkout
- Created `api/cart.php` with full cart management (add, update, remove, checkout)
- Created `user/cart.php` with cart display, quantity editing, and checkout form
- Checkout: transactional (BEGIN/COMMIT/ROLLBACK), validates stock, creates order + order_items, decrements stock, clears cart
- Order number format: `SP-YYYYMMDD-XXXXXXXX`
- Added `generateOrderNumber()` to functions.php

#### 11. Admin settings page
- Created `admin/settings.php` with CRUD for settings table
- Manages: site_name, site_email, timezone, currency, enable_registrations
- Added `getSetting()` and `setSetting()` to functions.php
- Activity logged on save

#### 12. User profile editing (both portals)
- **band/profile.php**: Full rewrite with name/phone editing, password change, member-since display
- **user/profile.php**: New file with same functionality for customer portal
- Both validate current password before allowing change
- Session `user_name` updated on profile save

#### 13. FullCalendar API endpoints
- **api/events.php**: Returns events in FullCalendar JSON format. Supports date range filtering.
- **api/tasks.php**: Returns tasks in FullCalendar JSON format. Color-coded by priority. Supports list format too.

#### 14. User portal rewrite
- **user/dashboard.php**: Complete rewrite. Stats cards (bookings, orders, tasks), recent bookings table, recent orders table. Dark theme matching admin/band.
- **user/merchandise.php**: Complete rewrite. Product grid with images, search, category filtering, add-to-cart via async JS. Uses PDO + `merchandise` table.
- **user/tasks.php**: Complete rewrite. Task cards with priority badges, status, due dates. Uses PDO + `tasks` table.
- **user/booking.php**: Complete rewrite. Clean booking form with poster preview, CSRF, proper validation.
- **user/booking_save.php**: Complete rewrite. Uses PDO, `bookings` table, `uploadFile()`, creates admin notifications.

#### 15. Register page cleanup
- Removed duplicate CSRF token field outside form
- Fixed role values to match DB (`customer`, `band_member` instead of `user`, `member`)
- Added `isLoggedIn()` redirect
- Activity logged on registration

### 2026-02-05 - User & Band Portal UI/UX Rebuild

#### 24. User portal - complete UI rebuild with sidebar layout
- Created `user/assets/css/user.css` - comprehensive shared CSS file (700+ lines) with dark theme design system matching admin/band portals
- Created `user/assets/js/common.js` - icon system (15 SVG icons), toast notifications, sidebar toggle
- Created `user/includes/sidebar.php` - reusable sidebar partial with SVG nav icons and auto-active state detection
- Converted all 6 user pages from topbar+inline styles to sidebar layout:
  - `dashboard.php` - sidebar layout, stat cards with SVG icons, quick actions, recent bookings/orders tables
  - `booking.php` - sidebar layout, form grid, file upload with preview, submit icon
  - `merchandise.php` - sidebar layout, product grid with SVG placeholders, add-to-cart with success animation and toast
  - `cart.php` - sidebar layout, cart items with SVG remove buttons, checkout form, toast notifications
  - `tasks.php` - sidebar layout, task stats summary cards, task cards with status+priority badges, SVG meta icons
  - `profile.php` - sidebar layout, grid-2 profile edit + password change, icon buttons
- Removed ~300 lines of duplicated inline CSS across 6 pages
- Replaced all `alert()` calls with `showToast()` for consistent UX
- Added SVG image placeholders for products/cart items without images
- Role badge shows "Customer" to distinguish from admin/band

#### 25. Band portal - icon system and UX polish
- Updated `band/assets/js/common.js` with full SVG icon system (13 icons) matching admin portal
- Added `.btn-icon`, `.nav-icon`, `.page-header`, `.detail-grid`, `.filter-tabs` CSS to `band/assets/css/band.css`
- Created `band/includes/sidebar.php` - reusable sidebar partial with SVG nav icons and auto-active state
- Replaced emoji-based sidebar navigation with SVG icons across all 6 band pages
- Replaced hardcoded sidebar HTML in all band pages with `include sidebar.php`
- Added SVG icons to action buttons: Start (play), Complete (check), Details (eye), Submit Expense (upload), Update Profile (save), Change Password (key), Mark Complete (check)
- Added logout icon to sidebar footer

### 2026-02-05 - Polish, Fixes & Dummy Data

#### 26. Admin sidebar extracted to include
- Created `admin/includes/sidebar.php` with SVG nav icons and auto-active state via `basename($_SERVER['PHP_SELF'])`
- Replaced hardcoded emoji sidebars in all 8 admin pages with `include sidebar.php`
- Added `.nav-icon` CSS to `admin.css`
- Removed emoji from `.empty-state::before` and `.loading::after` in `admin.css`

#### 27. Index page emoji-to-SVG conversion
- Replaced all 9 emojis in `index.php` with inline SVG icons (event location pin, clock, 6 service icons, merch placeholder)
- Added `.service-icon`, `.event-icon`, `.merch-placeholder svg` styles to `main.css`

#### 28. Event countdown timer on landing page
- Replaced hero-stats section with live countdown timer showing days/hours/minutes/seconds to next upcoming event
- Added countdown CSS to `main.css` (responsive at 768px and 480px breakpoints)
- Added `initCountdown()` to `main.js` with 1-second interval updates
- Fallback message when no upcoming events exist

#### 29. Fixed band member login passwords
- Generated proper bcrypt hash for "Admin@123" and updated all 5 users (admin + 4 band members) in both DB and seed SQL files
- The old hash `$2y$10$92IXU...` was for the string "password", not "Admin@123"

#### 30. Registration form - removed band_member role
- Replaced role dropdown with hidden input `value="customer"`
- Simplified validation to only accept `customer` role

#### 31. Admin modal reliability fix
- Normalized all modal open/close functions across events.js, merchandise.js, bookings.js, tasks.js, users.js, expenses.js to use both `style.display = 'flex'/'none'` and `classList.add/remove('active')` for reliable modal toggling

#### 32. Dummy data seeding
- Created `config/schema/seed_dummy.sql` with 5 events (mix of scheduled/completed, Malaysian venues), 5 bookings (various statuses), 5 tasks (assigned to band members), 5 expenses (various categories), 5 merchandise items (apparel, accessories, music), 5 orders with 10 order items
- All foreign keys properly linked using variable-based lookups

### 2026-02-05 - UI Icon System for Action Buttons

#### 23. Inline SVG icon system for admin action buttons
- Added `icons` object and `icon()` helper to `admin/assets/js/common.js` with 14 SVG icons: check, x, trash, download, plus, edit, eye, key, play, save, close, receipt, upload
- Added `.btn-icon` CSS rules to `admin.css` for proper sizing (14px default, 12px inside `.btn-small`)
- Added `<script src="assets/js/common.js">` to all admin pages that were missing it: bookings.php, expenses.php, merchandise.php, tasks.php, users.php, events.php
- **bookings.js**: Approve (check), Reject (x), Delete (trash) buttons now have icons
- **expenses.js**: Approve (check), Reject (x), Delete (trash), View receipt (eye) buttons now have icons
- **merchandise.js**: Edit (edit), Delete (trash) card buttons now have icons
- **tasks.js**: Start (play), Complete (check), Delete (trash) buttons now have icons
- **users.js**: Edit (edit), Reset (key), Delete (trash) buttons now have icons
- **events.js**: Edit (edit), Cancel (x), Delete (trash) buttons now have icons
- **PHP hardcoded buttons updated**: Export (download), Add/New (plus), Save (save), Approve (check), Reject (x), Edit (edit) across bookings.php, expenses.php, merchandise.php, tasks.php, users.php, events.php, settings.php
- Zero external dependencies; all icons are inline SVG using stroke-based paths matching Lucide/Feather style

### 2026-02-04 - Admin & Band Portal Fixes

#### 16. Admin bookings.js - replaced hardcoded data with API
- Removed hardcoded sample booking array that was displayed instead of real data
- `loadBookings()` now fetches from `bookings.php?api=list` on page load
- POST actions (approve/reject/delete) use FormData with `api=xxx` field name
- Added debounced search filtering and status filter dropdown
- Stats (total, pending, approved, rejected) computed from live data

#### 17. Admin expenses.js - fixed broken template literal + API integration
- Fixed broken template literal (missing opening backtick on line 118)
- Removed hardcoded sample expense array
- `loadExpenses()` now fetches from `expenses.php?api=list`
- POST actions use FormData with `action=approve/reject/delete`
- Added `saveExpense()`, modal open/close functions, CSV export

#### 18. Admin merchandise.js - fixed API paths + API integration
- Fixed API path from non-existent `add_merchandise.php` to `merchandise.php`
- Removed hardcoded sample merchandise array
- `loadMerchandise()` now fetches from `merchandise.php?api=list`
- Fixed `editMerchandise()` to use modal form instead of redirecting to non-existent `add_merchandise.html`
- `saveMerchandise()` handles both create (no ID) and update (with ID) via FormData
- Fixed `deleteMerchandise()` to call correct endpoint

#### 19. Admin tasks.js + tasks.php - modal view + dynamic assignees
- Replaced `alert()` in `viewTask()` with proper modal (`taskDetailModal`)
- Added `loadAssignees()` that fetches from `tasks.php?api=users` to populate dropdown dynamically
- Replaced hardcoded 4-name assignee dropdown with dynamic `<select id="assigneeSelect">`
- Added task detail modal HTML to tasks.php
- Unified input parsing in tasks.php: reads JSON or FormData into `$postData`
- All `$_POST` references changed to `$postData` for consistent handling
- Fixed `php://input` double-read risk by reading once at the top

#### 20. Admin dashboard - server-rendered stats
- Added revenue query: `SUM(price) FROM bookings WHERE status = 'approved'`
- Added monthly expenses query with MONTH/YEAR filter
- Added recent bookings query (last 5)
- Fixed events query from `ORDER BY date DESC` to `WHERE date >= CURDATE() ORDER BY date ASC`
- All stat card values rendered from PHP (removed JS dependency on non-existent `../api/dashboard_stats.php`)
- Recent bookings table rendered server-side with PHP foreach
- Upcoming events list rendered server-side with PHP foreach
- Replaced broken `<script src="../assets/js/dashboard.js">` with inline `toggleNotifications()`

#### 21. Band portal - api/tasks.php + band.js + band.css
- **api/tasks.php**: Added `action=view` GET handler for single task view with ownership validation (`assigned_to = current user`). Added POST handlers for `update_status` and `complete` actions. Unified input parsing (JSON + FormData).
- **band/assets/js/band.js**: `viewTaskDetails()` calls `../api/tasks.php?action=view&id=X`. `markTaskComplete()` and `updateTaskStatus()` use FormData POST. Added `loadNotificationCount()` fetching from notifications API. Added `esc()` HTML escaping helper.
- **band/assets/css/band.css**: Complete rewrite (~712 lines). Full dark theme matching admin CSS design system. Added all missing styles: CSS variables, stat-icon color variants, priority/status badges, form elements, tables, modals, alerts, grid layouts, quick-actions, task-list/event-list, button variants, responsive breakpoints (1200px, 768px, 480px).

#### 22. Admin sidebar - Settings link added
- Added Settings nav-item (`settings.php`) to sidebar in all 8 admin pages: dashboard.php, users.php, bookings.php, events.php, tasks.php, expenses.php, merchandise.php, settings.php

---

## Decisions Log

| Date | Decision | Rationale |
|------|----------|-----------|
| 2026-02-04 | session.php is single source of truth for auth functions | It loads first and has the most complete implementations |
| 2026-02-04 | bootstrap.php is the only entry point for all pages | Prevents partial loading of dependencies |
| 2026-02-04 | Rate limiting uses activity_log table | Avoids adding a new table; JSON_EXTRACT for email matching |
| 2026-02-04 | Upload validation uses finfo MIME check | More secure than checking file extension |
| 2026-02-04 | Cart checkout is transactional | Prevents partial orders and stock inconsistencies |
| 2026-02-04 | User portal rewritten from mysqli to PDO | Old code referenced non-existent db.php and old table names |
| 2026-02-04 | Notifications are DB-based (polling) | Simpler than WebSockets; sufficient for this scale |
| 2026-02-04 | Admin JS files rewritten to use API endpoints | Original files had hardcoded sample data, broken paths, and never called the backend |
| 2026-02-04 | Dashboard stats server-rendered instead of JS-fetched | No separate dashboard API exists; PHP already has the data; eliminates an unnecessary round-trip |
| 2026-02-04 | FormData for simple POST actions, JSON for complex creates | FormData populates `$_POST` natively; JSON used only when structured nested data is needed |
| 2026-02-04 | Band CSS full rewrite to match admin theme | Original band.css was missing 80%+ of required styles (badges, modals, forms, grids, buttons) |
| 2026-02-04 | api/tasks.php handles both FullCalendar and band CRUD | Single endpoint avoids duplication; FullCalendar format is the default, list format via `?format=list` |
| 2026-02-05 | User portal converted from topbar to sidebar layout | Consistent with admin and band portals; better navigation UX; mobile-responsive with toggle |
| 2026-02-05 | Sidebar extracted to PHP includes for all portals | Eliminates sidebar HTML duplication across pages; auto-active state via PHP_SELF |
| 2026-02-05 | SVG nav icons replace emoji in band sidebar | Consistent with admin; renders reliably across all platforms; no emoji rendering differences |

---

## Remaining / Future Work

### Completed
- [x] Admin sidebar settings link across all admin pages
- [x] Admin JS files connected to backend APIs (bookings, expenses, merchandise, tasks)
- [x] Admin dashboard server-rendered with real data
- [x] Band portal JS functions connected to working API endpoints
- [x] Band CSS complete dark theme matching admin portal
- [x] Admin tasks: dynamic assignee dropdown, proper modal view
- [x] User portal complete UI rebuild - sidebar layout, shared CSS, SVG icons, toast notifications
- [x] Band portal UX polish - SVG icon system, sidebar include, action button icons
- [x] Band members seeded: Ameer (Vocalist), Zimi (Guitarist), Fairuz (Bassist), One (Drummer)
- [x] Band logo (SP) integrated across all portals (admin, band, user, auth, landing)
- [x] Landing page updated with real band identity and member positions
- [x] Added `position` column to users table for band member roles
- [x] Shared assets directory created (`assets/images/`)
- [x] Admin sidebar extracted to reusable include with SVG icons (all 8 pages)
- [x] Index page emojis replaced with inline SVG icons
- [x] Event countdown timer on landing page hero section
- [x] Band member and admin passwords fixed (bcrypt hash for Admin@123)
- [x] Registration form locked to customer role only
- [x] Admin modal open/close normalized with display+class for reliability
- [x] Dummy data seeded (5 events, 5+ bookings, 5 tasks, 5 expenses, 5 merch items, 5 orders)
- [x] Admin API headers fixed for reliable JSON responses
- [x] Expenses "Submitted By" column added to admin table
- [x] Expense category filter aligned with seed data
- [x] Silent error handling replaced with toast notifications in admin JS
- [x] User portal tasks page removed, orders page added
- [x] Band portal schedule/calendar page created
- [x] Public booking form fixed (nullable user_id)
- [x] Full system audit completed

### 2026-02-05 - Debug, Audit & New Features

#### 33. Fixed admin API pattern for bookings/expenses/merchandise
- Removed redundant `$isApi` block pattern from `bookings.php`, `expenses.php`, `merchandise.php` that was setting headers inconsistently
- Added explicit `header('Content-Type: application/json')` to every individual API handler
- Ensures all API responses (GET and POST) have correct Content-Type regardless of request path

#### 34. Expenses "Submitted By" column
- Added `Submitted By` column to HTML table header in `expenses.php` (9 columns total)
- Updated `expenses.js` to render `submitted_by_name` from the API response
- Added submitter name to search filter and CSV export
- Updated loading placeholder colspan from 8 to 9

#### 35. Expense category filter alignment
- Updated category filter dropdown and add-expense form to include all actual categories: Equipment, Food, Marketing, Rental, Transport, Venue, Other
- Previously only had Equipment, Marketing, Transportation, Venue, Other (mismatched seed data)

#### 36. Admin JS error handling
- Replaced silent `catch (e) {}` blocks across all admin JS files (bookings, expenses, merchandise, tasks) with `showToast()` error notifications
- Users now see feedback when API calls fail instead of silent failures

#### 37. Task cards - assignee visibility improved
- Updated task card rendering in `tasks.js` to show assignee with icon tag for better visual prominence
- Task detail modal now shows assignee name in bold

#### 38. User portal - removed tasks, added orders
- Removed "My Tasks" from user sidebar (customers don't need task management)
- Added "My Orders" nav item linking to new `orders.php`
- Created `user/orders.php` - order history page with table view and detail modal
- Created `api/orders.php` - API endpoint for order details (with ownership validation)
- Updated dashboard: replaced "Pending Tasks" stat with "Total Spent", replaced "View Tasks" quick action with "My Orders"

#### 39. Band portal - schedule calendar page
- Created `band/schedule.php` with full FullCalendar integration
- Displays both events (blue) and tasks (color-coded by priority) on the calendar
- Month, week, and list views available
- Click any event/task to see details in a modal
- Color legend for event types
- Dark theme CSS for FullCalendar matching band portal design
- Added "Schedule" nav item to band sidebar between Events and Submit Expense

#### 40. Public booking form fix
- Made `bookings.user_id` nullable in both schema.sql and live database
- Public booking form on index.php now works for guest users (no user account required)
- Logged-in user bookings via `booking_save.php` still include user_id

#### 41. System audit
- Verified all admin pages (events, users, settings, merchandise, tasks) display data correctly
- Verified user portal pages (merchandise, booking, cart) work correctly
- Verified band portal pages (my_tasks, my_expenses, events) work correctly
- Confirmed notification system API works correctly
- No additional data display bugs found

### 2026-02-05 - Session 3: Major Feature Build

#### 42. Band calendar CDN fix
- Fixed FullCalendar v6 CDN in `band/schedule.php`: removed separate CSS link (v6 bundles CSS in JS), changed `main.min.js` to `index.global.min.js`

#### 43. Booking quotation and invoice system
- Added `quotation_number` and `invoice_number` columns to bookings table (schema + live DB)
- Added `generateQuotationNumber()` and `generateInvoiceNumber()` to `includes/functions.php` (format: QT/INV-YYYYMMDD-XXXX)
- Updated `user/booking_save.php` to generate quotation number on booking submission
- Updated `admin/bookings.php` approve handler: accepts price, generates invoice number, sends booking_approved notification to user
- Updated `admin/bookings.php` reject handler: sends booking_rejected notification to user
- Rewrote `admin/assets/js/bookings.js` with price modal flow for approval

#### 44. Admin expenses page rewrite
- Complete rewrite of `admin/expenses.php` (PHP API handlers + HTML)
- API handlers: list (JOINs users+events), get, stats, create (file upload), update (file upload), approve (with notification), reject (with notification), delete (file cleanup)
- HTML: sidebar include, header with search + notification btn, stats row, category+status+date filters, table (9 cols), unified add/edit modal
- Complete rewrite of `admin/assets/js/expenses.js` with editExpense, saveExpense (create+update), filters, CSV export, showToast error handling

#### 45. Admin merchandise page rewrite
- Complete rewrite of `admin/merchandise.php` (moved inline CSS to admin.css)
- API handlers: list (with stock_status), get, stats, create (image upload), update (image upload), delete (image cleanup)
- HTML: card grid layout, view modal, add/edit modal, stats cards
- Complete rewrite of `admin/assets/js/merchandise.js` with card rendering, view/edit/delete modals, CSV export
- Added merch-grid, merch-card, merch-image, stock-bar, low-stock-badge styles to `admin/assets/css/admin.css`

#### 46. User booking availability calendar
- Added FullCalendar v6 to `user/booking.php` showing booked/pending dates as background events
- Created `api/booked_dates.php` returning pending bookings (yellow), approved bookings (red), scheduled events (orange) as FullCalendar background events
- Clicking a date fills the booking form date input
- Dark theme calendar styles with color legend

#### 47. My Bookings page
- Created `user/my_bookings.php` with server-rendered table: Quotation No, Event Name, Date, Location, Price, Status, Invoice
- Status badges with color classes (pending=warning, approved=success, rejected=danger)
- Invoice link visible only when invoice_number exists
- Added "My Bookings" nav item to `user/includes/sidebar.php`

#### 48. Printable e-invoice
- Created `user/invoice.php` with standalone printable invoice page
- Shows invoice number, quotation reference, client details, event details, price breakdown
- Dark theme for screen, light theme for print via `@media print`
- Validates user_id ownership before displaying

#### 49. Add to cart fix
- Fixed `user/merchandise.php` async/Promise issue: replaced `onsubmit="return addToCart()"` with class-based event listeners
- Added `!res.ok` check to handle session expiry (server returns HTML redirect instead of JSON)

#### 50. System-wide notification component
- Created `assets/js/notifications.js` shared IIFE component
- Auto-enhances `.notification-btn` elements with bell SVG icon + badge count
- Creates dropdown HTML dynamically with notification list, mark-all-read button
- Polls unread count every 30 seconds from `api/notifications.php`
- Click notification marks as read and navigates to link
- TimeAgo helper for relative timestamps
- Close on outside click

#### 51. Notification integration across all portals
- Added notification CSS to all 3 portal stylesheets (`admin.css`, `band.css`, `user.css`): dropdown panel, badge count, item styles, unread highlight
- Added `<button class="notification-btn"></button>` to all page headers that were missing it
- Added `<script src="../assets/js/notifications.js"></script>` to all pages:
  - Admin: dashboard, events, tasks, users, settings, bookings, expenses, merchandise (8 pages)
  - Band: dashboard, events, my_tasks, expenses, my_expenses, profile, schedule (7 pages)
  - User: dashboard, booking, merchandise, cart, orders, tasks, profile, my_bookings (8 pages)
- Cleaned up old inline `toggleNotifications()` functions and notification badge spans replaced by shared component
- Task assignment notifications already wired in `admin/tasks.php` create handler

### 2026-02-05 - Session 4: Ella-Inspired Gold Theme & UI Enhancements

#### 52. Global color scheme migration (Blue to Gold)
- Migrated all 4 CSS files from blue (#3b82f6) to gold (#F59E0B) accent color
- Updated :root variables across `main.css`, `admin.css`, `band.css`, `user.css`:
  - `--accent: #F59E0B`, `--accent-hover: #D97706`, `--accent-light: rgba(245,158,11,0.1)`, `--accent-glow: rgba(245,158,11,0.3)`
  - `--gradient` / `--gradient-primary` updated to gold gradient
  - Separated `--info: #38bdf8` (sky blue) from accent in admin/user CSS
- Replaced all hardcoded `#3b82f6` and `#2563eb` across all stylesheets
- Updated FullCalendar event color in `band/dashboard.php` to `#F59E0B`
- Updated inline calendar styles in `user/booking.php` to gold

#### 53. Public landing page enhancements
- Added scroll reveal animations: `.reveal` class with IntersectionObserver at threshold 0.15
- Added stats counter section between About and Events: Total Events, Band Members, Years Active, Bookings Done
- Stats counter animated from 0 to target using requestAnimationFrame
- Added past events section after upcoming events (query `WHERE date < CURDATE() LIMIT 6`)
- Added social media links in footer (Instagram, Facebook, TikTok, YouTube, Twitter/X) from settings table
- Added gold hover glow on band member cards
- Added gold particle dots in hero via `::before`/`::after` with `@keyframes float`
- Added section dividers between content sections
- Updated `main.js` with `initScrollReveal()` and `initStatsCounter()`
- Updated `index.php` with new sections, `.reveal` classes, and PHP queries

#### 54. Admin dashboard enhancements
- Rewrote `admin/dashboard.php` with enhanced data sections:
  - Quick actions row: links to Events, Tasks, Expenses, Bookings with SVG icons
  - Monthly revenue bar chart (last 6 months, CSS-only, gold gradient bars)
  - Booking status donut chart (CSS conic-gradient: pending=gold, approved=green, rejected=red)
  - Activity feed with color-coded dots (green=approved, red=rejected/delete, orange=pending/login)
  - Recent bookings table with price column
  - Upcoming events list with date badges
- Added CSS to `admin.css`: chart-section, bar-chart, donut-chart, activity-feed, quick-actions-row

#### 55. Band portal dashboard enhancements
- Added next gig countdown card with live timer (days/hours/minutes)
- Added task progress bar showing completed/total percentage with gold gradient fill
- Added expense summary mini-cards: Total Submitted, Pending, Approved
- Added CSS to `band.css`: countdown-card, progress-bar, expense-summary-row

#### 56. User portal enhancements
- Added booking timeline to `user/dashboard.php`: visual step indicator (Submitted -> Under Review -> Approved/Rejected)
- Timeline shows status of latest booking with dynamic CSS classes
- Added SVG stat card icons to dashboard
- Restructured `user/booking.php` into 3-step wizard form:
  - Step 1: Event Details (company, title, notes)
  - Step 2: Date & Venue (date, time, address, postal, city, state)
  - Step 3: Upload & Review (poster file, submit)
- Added step indicator with numbered dots and connecting lines
- Added `goToStep()` JS function for step navigation with checkmark SVGs on completed steps
- Added CSS to `user.css`: booking-timeline, step-indicator, step-group, step-nav

#### 57. Settings - Social Media fields
- Added Social Media section to `admin/settings.php` with 5 URL fields: Instagram, Facebook, TikTok, YouTube, WhatsApp
- Social media settings loaded/saved via AJAX (fetch) for smooth UX
- Created `admin/assets/js/settings.js` with `loadSocialSettings()` on page load and form submit handler
- Uses existing `getSetting()`/`setSetting()` with keys: `social_instagram`, `social_facebook`, `social_tiktok`, `social_youtube`, `social_whatsapp`
- Social media URLs displayed in public landing page footer

### 2026-02-05 - Session 5: System Enhancements & Bug Fixes

#### 58. Auth pages - Back to Home link
- Added "Back to Home" link below the card on all 4 auth pages: login, register, forgot-password, reset-password
- Gold color, left arrow, centered, inline CSS for consistency with auth page styling
- Links to `../index.php`

#### 59. Booking Payment System
- Added `payment_status` (ENUM: unpaid/paid), `payment_due_date` (DATE), `paid_at` (DATETIME) columns to bookings table
- Created `config/schema/migration_payment.sql` for existing databases
- Updated `config/schema/schema.sql` for fresh installs
- Admin approve handler: auto-sets `payment_due_date = CURDATE() + 14 days`, `payment_status = 'unpaid'`
- New `mark_paid` API handler: sets `payment_status = 'paid'`, `paid_at = NOW()`, sends notification to user
- Admin bookings list query: now includes payment_status, payment_due_date, paid_at
- Admin bookings JS: Payment column with Paid/Unpaid badges, due date display (red if overdue), "Mark Paid" button for unpaid approved bookings, "Invoice" link for approved bookings
- Admin bookings HTML: added Payment column between Status and Actions, added Unpaid stat counter
- Created `admin/invoice.php` for admin invoice view (no user_id restriction, shows payment info)
- Updated `user/my_bookings.php`: added Payment column with Paid/Unpaid badges and due dates
- Updated `user/invoice.php`: added payment status banner and due date display

#### 60. Cart bug fix
- Changed form `action` from `<?= APP_URL ?>/api/cart.php` to empty string in `user/merchandise.php`
- API URL stored in `data-action` attribute
- JS `fetch()` reads from `form.dataset.action` instead of `form.action`
- If JS fails, form posts to same page (harmless reload) instead of redirecting to cart.php/login/dashboard

#### 61. Booking page side-by-side layout
- Wrapped calendar + form in `.booking-layout` flex container in `user/booking.php`
- Calendar in `.booking-calendar` (45% width), form in `.booking-form-section` (flex: 1)
- Removed `max-width:720px` inline styles
- Added responsive CSS: stacks vertically below 900px
- CSS added to `user/assets/css/user.css`

#### 62. Music section on landing page
- Added "Our Music" section with Spotify and YouTube embed iframes between Past Events and Services
- Embeds loaded from settings: `spotify_embed_url`, `youtube_embed_url`
- Section only renders if at least one URL is set
- Added "Music" nav link to navbar
- Responsive: 2-column grid on desktop, stacked on mobile

#### 63. Gallery section on landing page
- Added placeholder gallery section with 6 gradient-background items
- Hover effect with scale transform and overlay text
- Responsive: 3-column grid on desktop, 2-column on mobile
- CSS: `.gallery-section`, `.gallery-grid`, `.gallery-item`, `.gallery-overlay` in `main.css`

#### 64. Admin media settings
- Added "Media & Embeds" section to `admin/settings.php` with Spotify and YouTube embed URL fields
- PHP handlers: `save_media` and `load_media` actions
- Updated `admin/assets/js/settings.js` with `loadMediaSettings()` and media form submit handler
- Settings loaded on page load alongside social media settings

#### 65. Comprehensive seed data
- Created `config/schema/seed_data.sql` with realistic Malaysian test data
- Users: 1 admin + 4 band members + 3 customers (sarah@email.com, michael@email.com, aina@email.com)
- Events: 20 total (12 past completed, 7 upcoming scheduled, 1 cancelled) across KL, Penang, JB, Shah Alam, Putrajaya
- Bookings: 20 total with mix of statuses (5 pending, 4 approved with payment data, 3 rejected, 8 completed)
- Tasks: 20 assigned to band members (5 todo, 5 in_progress, 8 completed, 2 cancelled) with various priorities
- Expenses: 20 across all categories (Equipment, Food, Marketing, Rental, Transport, Venue, Other)
- Merchandise: 20 items (apparel, accessories, music, collectibles) with realistic prices RM 9.90-249.90
- Orders: 10 with 24 order items from customer users
- Notifications: 20 across all user types
- Activity log: 20 entries (logins, bookings, settings)
- Settings: social media URLs, Spotify/YouTube embeds, years_active
- All passwords: `Admin@123`

### 2026-02-05 - Session 6: Bootstrap Icons, Red Color Scheme, Auth Fix

#### 66. Auth navigation fix
- All auth-related links now use absolute `APP_URL` paths instead of relative paths
- Fixed: index.php navbar Login/Sign Up links, hero Get Started, footer Login/Register, View Full Store
- Fixed: 3 sidebar logout links (admin, band, user) from `../auth/logout.php` to `APP_URL/auth/logout.php`
- Fixed: 4 auth file logos, cross-links (login/register/forgot/reset), and back-to-home links
- Auth file logo images also use `APP_URL` for correct resolution

#### 67. Bootstrap Icons migration
- Added Bootstrap Icons 1.11.3 CDN link to all 30 pages (index, 9 admin, 7 band, 9 user, 4 auth)
- Replaced all sidebar SVG icons with `<i class="bi bi-xxx nav-icon">` tags across 3 sidebar includes
- Replaced `icon()` function SVG strings with Bootstrap Icon `<i>` tags in 3 common.js files (admin, band, user)
- Replaced inline SVGs in index.php: 6 service cards, 2 event meta icons, 5 footer social icons, merch placeholder
- Replaced inline SVGs in admin/dashboard.php: 4 quick-action button icons
- Replaced inline SVGs in admin/settings.php: 3 save button icons
- Replaced password toggle eye SVGs with Bootstrap Icons in 4 auth files
- Updated CSS: `.nav-icon` uses `font-size: 18px` instead of SVG width/height in all 3 portal CSS files
- Updated CSS: `.btn-icon` uses `font-size: 14px` instead of SVG sizing in all 3 portal CSS files
- Updated main.css: `.service-icon` (32px), `.event-icon` (14px), `.footer-social i` (18px), `.merch-placeholder i` (48px)
- Updated admin.css: `.quick-action-btn i` (18px)

#### 68. Color scheme change - gold to red
- Changed accent color from gold (#F59E0B) to red (#DC2626) across entire project
- CSS variables updated in all 4 CSS files: `--accent`, `--accent-hover`, `--accent-light`, `--accent-glow`, `--gradient`/`--gradient-primary`
- Replaced all hardcoded `rgba(245,158,11,X)` accent values with `rgba(220,38,38,X)` in 4 CSS files
- Warning colors preserved as gold/amber: `--warning: #f59e0b`, badge.status-pending, badge.priority-medium/high, toast-warning
- Auth inline styles: updated accent vars, rgba shadows, background gradient endpoints `#fef3c7` to `#fecaca`
- Invoice inline styles (admin + user): `#F59E0B` to `#DC2626`, `#D97706` to `#B91C1C`, `#fffbeb` to `#fef2f2`
- Gallery gradients in index.php: gold replaced with red tones
- Band dashboard FullCalendar event color: `#F59E0B` to `#DC2626`
- User booking calendar: pending legend dot changed to orange `#f97316` to distinguish from red approved
- API booked_dates.php: pending color from `#f59e0b` to `#f97316` (orange)
- Admin users.js: "Not Verified" color from `#f59e0b` to `#f97316` (orange warning state)
- Free Entry price tag changed from accent (red) to success (green) via `var(--success)`

### 2026-02-05 - Session 7: Admin Order Management & Activity Log Viewer

#### 69. Admin Order Management
- Created `admin/orders.php` with PHP API handlers + HTML page
- API handlers: list (JOINs users, item count subquery), get (order + items JOIN merchandise), update_status (with notification), update_payment (with notification), delete (restores stock before removing)
- HTML: sidebar include, header with search, filter tabs (All/Pending/Processing/Shipped/Delivered/Cancelled), stats row (5 cards), orders table (8 cols), detail modal with items breakdown
- Created `admin/assets/js/orders.js` with loadOrders, renderOrders, updateStats, filterOrders, viewOrder (detail modal with item images), updateStatus (inline select dropdown), markPaid, deleteOrder, setupSearch
- Status changes via inline `<select>` dropdown per row
- Mark Paid button shown only for unpaid orders
- Delete handler restores merchandise stock quantities before removing order
- Notifications sent to customer on status change and payment update

#### 70. Activity Log Viewer
- Created `admin/activity_log.php` with PHP API handlers + HTML page
- API handlers: list (JOINs users, LIMIT 500, ORDER BY created_at DESC), actions (SELECT DISTINCT for filter dropdown)
- HTML: sidebar include, header with search, filters row (action dropdown, date from/to), stats row (4 cards), logs table (5 cols)
- Created `admin/assets/js/activity_log.js` with loadLogs, loadActions (populates dropdown), renderLogs, updateStats, filterLogs, formatAction (color-coded badges), formatDetails (JSON parse), formatTime, setupSearch
- Action badges color-coded: green (login, register, order_placed), red (login_failed, logout), blue (profile_updated, settings_updated), orange (booking_submit, password_changed)
- Read-only viewer (no CRUD actions)
- Updated `admin/includes/sidebar.php` with Orders and Activity Log nav items (after Merchandise, before Settings)

### 2026-02-06 - Session 8: Booking Enhancements, Receipt System, Icon Cleanup & Bug Fixes

#### 71. Booking quotation price & receipt upload schema
- Added `quotation_price` DECIMAL(10,2) NULL column to bookings table (after `price`)
- Added `payment_receipt` VARCHAR(255) NULL column to bookings table (after `paid_at`)
- Updated `config/schema/schema.sql` for fresh installs
- Migration SQL run on live database

#### 72. User booking quotation price
- Added "Budget / Quotation Price (RM per day)" input field to Step 1 of `user/booking.php`
- Updated `user/booking_save.php` to read and save `quotation_price` to bookings table
- Admin notification includes "Budget: RM X per day" when quotation price is provided
- Fixed inline SVG on submit button and step checkmarks to Bootstrap Icons

#### 73. User receipt upload & enhanced My Bookings
- Rewrote `user/my_bookings.php` with POST handler for `upload_receipt` API action
- Receipt upload validates booking ownership, uses `uploadFile()`, updates `bookings.payment_receipt`
- Admin notified on receipt upload via `createNotification()`
- Added "Quotation" column showing user's proposed price per day
- Replaced single "Invoice" column with "Actions" column: View Invoice button, Upload Receipt button, "Receipt Uploaded" badge
- Added receipt upload modal with file input (JPG/PNG/PDF)
- Fixed flash message handling: `getFlash()` returns array, access `$flash['type']` and `$flash['message']` separately

#### 74. Invoice bank payment details
- Added Maybank payment instructions box to `user/invoice.php` (shown only when payment is unpaid)
- Displays: Bank (Maybank), Account Name (Sofarz Manager), Account Number, Reference (invoice number)
- Styled with amber background matching warning theme

#### 75. Admin booking quotation price display
- Added `quotation_price` to list SQL query in `admin/bookings.php`
- Updated `admin/assets/js/bookings.js` approve modal: shows "Customer's budget: RM X/day" hint
- Pre-fills price input with customer's quotation price when available

#### 76. Band expense receipt viewing
- Rewrote `band/my_expenses.php` with Receipt column and view button (eye icon)
- Added receipt modal supporting both images (JPG/PNG) and PDFs (iframe)
- Uses `../uploads/` path prefix for receipt file URLs

#### 77. Notification link navigation fix
- Root cause: notification links stored as root-relative (`/admin/bookings.php`) but app runs in `/secondplan/` subfolder
- Added `getBaseUrl()` function to `assets/js/notifications.js` that detects subfolder from `window.location.pathname`
- Prepends base URL to notification links before navigation

#### 78. Merchandise delete FK constraint handling
- Wrapped DELETE in try/catch in `admin/merchandise.php`
- On foreign key constraint error (items with existing orders): auto-sets item to `status='inactive'` instead of deleting
- Returns friendly message: "Cannot delete this item because it has existing orders. It has been marked as inactive instead."

#### 79. Index merchandise cards link to register
- Wrapped each `.merch-card` div in `index.php` with `<a href="register.php">` anchor tag
- Links guest users to registration page when clicking merchandise cards

#### 80. Complete Bootstrap Icons migration (SVG cleanup)
- Replaced ALL remaining inline `<svg class="btn-icon">` elements with `<i class="bi bi-xxx btn-icon">` across all 3 portals (14 PHP files)
- Band portal (4 files): dashboard.php (play-circle, eye, check-circle), my_tasks.php (play-circle, check-circle), profile.php (floppy, key), expenses.php (send)
- User portal (5 files): dashboard.php (plus-circle, tag, box-seam), cart.php (tag, check-circle), merchandise.php (cart, cart-plus + JS inline SVGs), orders.php (tag), profile.php (floppy, key)
- Admin portal (5 files): events.php (plus-circle, floppy, pencil-square), expenses.php (download, plus-circle, floppy), merchandise.php (download, plus-circle, pencil-square, floppy), users.php (plus-circle, floppy), tasks.php (plus-circle, floppy)
- Zero inline SVGs remaining in button contexts across entire project

#### 81. Admin dashboard stat icons fix
- Added Bootstrap Icons inside empty `<div class="stat-icon">` elements in `admin/dashboard.php`
- Revenue: `bi-wallet2` (blue), Bookings: `bi-journal-check` (green), Pending Tasks: `bi-list-task` (red), Expenses: `bi-cash-stack` (red)

#### 82. Admin users edit/reset password fix
- Root cause: JS sends `Content-Type: application/json` but PHP `$_POST` only populates for form-encoded data
- Added JSON input parsing at top of `admin/users.php`: reads `php://input`, decodes to `$jsonInput`, extracts `$jsonAction`
- Changed all handler conditions from `$_POST['action']` to `$jsonAction`
- Reused `$jsonInput` inside handlers instead of re-reading `php://input`
- Both edit user and reset password now work correctly

### 2026-02-10 - Session 9: Critical Security Fixes

#### 83. JavaScript page initialization fix
- Fixed critical typo in `user/assets/js/user.js` lines 262, 349: `mappage` changed to `map[page]()`
- This typo broke ALL JavaScript page initialization in the user portal (dashboard, booking, merchandise, tasks pages)
- Cleaned up dead/orphaned code at end of file (lines 338-382)
- Removed comments per project rules
- Added `booking_form` to page map

#### 84. SQL injection vulnerability fix
- Fixed SQL injection in `admin/tasks.php` lines 161-162
- Changed from string interpolation (`completed_at = $completedAt`) to parameterized query (`completed_at = ?`)
- `$completedAt` now uses PHP `date('Y-m-d H:i:s')` instead of SQL `NOW()` literal

#### 85. Events API authentication fix
- Added `require_login()` to `api/events.php` line 3
- API was previously exposing all scheduled events to unauthenticated users
- Band/user calendar features require login to view event data

#### 86. CSRF protection for public booking form
- Added `verify_csrf()` validation to `index.php` booking form handler
- Added hidden CSRF token field to form HTML
- Added email validation using `isValidEmail()` function
- Added negative budget protection with `max(0, ...)` wrapper

#### 87. XSS vulnerability fix in notifications
- Added `isSafeLink()` function to `assets/js/notifications.js`
- Blocks `javascript:`, `data:`, and `vbscript:` protocol URLs in notification links
- Applied validation in `handleNotificationClick()` before navigation

#### 88. CSRF protection for API endpoints
- Added CSRF validation to `api/cart.php`, `api/notifications.php`, `api/tasks.php` POST handlers
- Uses `verifyCSRF()` function to validate tokens and returns JSON error on failure
- Updated `includes/session.php` to set CSRF token as cookie for JavaScript access
- Added `getCSRF()` helper function to all 3 portal common.js files
- Updated `notifications.js` to include CSRF token in POST requests
- Updated `user/cart.php` to send CSRF token with cart operations

#### 89. Task API authorization fix
- Fixed `api/tasks.php` line 31: removed user-controllable `assigned_to` parameter
- Users can now only query their own tasks, preventing information disclosure

#### 90. Admin header consistency fix
- Added missing sidebar toggle button to `admin/events.php` and `admin/users.php`
- Fixed hardcoded "A" avatar to use dynamic `getUserData()['name']` in both files

#### 91. Band events time formatting fix
- Fixed `band/events.php` line 51: changed `formatDate()` on time fields to `date('H:i', strtotime())`
- Semantically correct time formatting instead of date formatting on time values

#### 92. Dead code cleanup
- Deleted unused `admin/assets/js/dashboard.js` (dashboard uses server-rendered stats)

#### 93. Band dashboard dead code removal
- Removed redundant line 45 in `band/dashboard.php` that always returned 0
- Ternary `$pdo->prepare()->execute() ? 0 : 0` was useless and immediately overwritten

#### 94. Duplicate CSS removal
- Removed duplicate `.notification-btn` rules (lines 871-889) from `band/assets/css/band.css`
- Original definition at lines 170-188 was sufficient

#### 95. Stat icon color fix
- Fixed `.stat-icon.orange` in `user/assets/css/user.css` to use orange color (#f97316) instead of red

#### 96. Booking date validation
- Added server-side date validation in `user/booking_save.php`
- Validates date format (YYYY-MM-DD), valid date, and not in the past
- Added missing postal code validation

#### 97. Error logging for index.php
- Added `error_log()` calls to all silent catch blocks in `index.php`
- Errors now logged for: events, merchandise, band members, booking submit, stats, past events queries

#### 98. Modal style cleanup
- Replaced inline styles in `band/my_expenses.php` receipt modal with CSS class-based styling
- Uses existing `.modal`, `.modal-content`, `.modal-header`, `.modal-body` classes

#### 99. Commented dead code removal
- Removed commented-out default response code (lines 343-351) from `admin/users.php`

#### 100. API method restriction
- Added 405 Method Not Allowed response for non-GET requests in `api/events.php`
- Returns JSON error for POST/PUT/DELETE attempts

#### 101. Band.js CSRF tokens
- Added CSRF token to `markTaskComplete()` POST request
- Added CSRF token to `updateTaskStatus()` POST request
- Ensures compatibility with CSRF-protected `api/tasks.php`

### 2026-02-10 - Session 10: Email System, PDF Export, Reports Dashboard

#### 102. Password reset dev mode display
- Added `$devResetLink` variable to `auth/forgot-password.php`
- In development mode (`APP_ENV === 'development'`), reset link displays in a yellow box on screen
- Allows testing password reset without email configuration
- Production mode hides link (users receive via email)

#### 103. Email notification system
- Created `includes/email.php` with comprehensive email templating system
- Added `sendEmail()` function with dev mode logging to `logs/emails.log`
- HTML email templates for 7 notification types:
  - `password_reset` - Reset link email
  - `booking_submitted` - Booking confirmation to customer
  - `booking_approved` - Approval notification with invoice details
  - `booking_rejected` - Rejection notification
  - `payment_confirmed` - Payment receipt
  - `order_confirmed` - Order confirmation with items list
  - `task_assigned` - Task assignment notification to band member
- Helper functions: `sendPasswordResetEmail()`, `sendBookingSubmittedEmail()`, `sendBookingApprovedEmail()`, `sendBookingRejectedEmail()`, `sendPaymentConfirmedEmail()`, `sendOrderConfirmedEmail()`, `sendTaskAssignedEmail()`
- Integrated into: `auth/forgot-password.php`, `index.php`, `user/booking_save.php`, `admin/bookings.php` (approve/reject/mark_paid), `api/cart.php` (checkout), `admin/tasks.php` (create)
- Added to `config/bootstrap.php` require chain
- Dev mode logs email content instead of sending; production uses PHP `mail()`

#### 104. Invoice PDF export
- Added html2pdf.js CDN (v0.10.1) to `user/invoice.php` and `admin/invoice.php`
- Added "Download PDF" button with `<i class="bi bi-file-earmark-pdf">` icon
- `downloadPDF()` JavaScript function generates A4 PDF from invoice container
- PDF filename: `{invoice_number}.pdf` (e.g., `INV-20260210-A1B2.pdf`)
- Options: 10mm margin, JPEG quality 0.98, scale 2x for high resolution
- Existing print functionality preserved with "Print" button

#### 105. Reports Dashboard
- Created `admin/reports.php` - comprehensive analytics page
- Date range filters: Today, This Week, This Month, Last Month, This Year, All Time, Custom Range
- Financial overview stat cards: Booking Revenue (Paid), Merchandise Sales, Total Expenses, Net Profit
- Revenue vs Expenses bar chart (last 12 months, CSS-only, no Chart.js dependency)
- Booking status donut chart (CSS conic-gradient): Approved/Pending/Rejected/Completed
- Expenses by category table with progress bars
- Top selling merchandise products table
- Payment summary: Paid/Unpaid bookings count, outstanding amount
- Merchandise summary: Orders count, items sold, average order value
- Export CSV button downloads all report data as spreadsheet
- Added "Reports" nav item to `admin/includes/sidebar.php` with `bi-graph-up` icon

#### 106. Merchandise image directory structure
- Created `assets/images/merchandise/` directory
- Created `assets/images/merchandise/README.md` with image specifications (800x800px, JPG/PNG, SKU-based naming)
- Updated `config/schema/seed_data.sql` with `image` column paths for all 20 merchandise items
- Image paths: `assets/images/merchandise/{SKU}.jpg`

#### 107. Merchandise image path handling
- Fixed image display across 4 files to support both `assets/` paths (seed data) and `uploads/` paths (admin uploads)
- `user/cart.php`: Added path detection logic
- `user/merchandise.php`: Added path detection logic
- `admin/assets/js/merchandise.js`: Fixed card view and detail modal
- `admin/assets/js/orders.js`: Fixed order items image display
- Pattern: `if (image.indexOf('assets/') === 0)` use direct path, else prepend `uploads/`

### 2026-02-10 - Session 11: System Bug Fixes

#### 108. CSRF token fix in user merchandise
- Fixed add-to-cart failing with CSRF validation error in `user/merchandise.php`
- Added `data.append('csrf', getCSRF())` to JavaScript FormData before fetch
- Added CSRF meta tag to head section for consistency

#### 109. Admin events API error handling
- Added try-catch blocks to CREATE, UPDATE, CANCEL, DELETE handlers in `admin/events.php`
- Added input validation for required fields (title, date, time, venue)
- Returns generic error messages instead of exposing database details

#### 110. Role validation standardization
- Changed `require_role(['admin'])` to `requireRole([ROLE_ADMIN])` in 8 admin files
- Files: events, invoice, tasks, bookings, merchandise, expenses, activity_log, orders
- Consistent pattern now used across all admin portal pages

#### 111. Modal active class fix
- Fixed `band/my_expenses.php` receipt modal missing classList.add/remove('active')
- Ensures proper CSS transitions on modal open/close

#### 112. Band portal header consistency
- Added missing user avatar to 5 band portal pages
- Files: events.php, profile.php, my_expenses.php, my_tasks.php, expenses.php
- All band pages now have consistent header with notification bell + user avatar

#### 113. API security improvements
- Fixed `api/orders.php`: changed `$e->getMessage()` to generic "Failed to load order(s)" message
- Fixed `api/notifications.php`: added `max(1, ...)` to prevent negative limit values

#### 114. Documentation corrections
- Fixed social media setting name: `social_twitter` -> `social_whatsapp` in PROJECT_PROGRESS.md
- Updated MEMORY.md with correct setting names

#### 115. Debug code removal
- Removed `console.error` calls from `admin/assets/js/users.js` (2 instances)
- Replaced with `showToast()` error messages for user feedback

#### 116. Code comments cleanup
- Removed section comments from `admin/assets/js/users.js` (17 comment lines)
- Removed section comments from `admin/assets/js/events.js` (25 comment lines)
- Removed category comments from `admin/assets/js/activity_log.js` (4 comment lines)
- Removed inline comment from `admin/assets/js/bookings.js`
- Removed section comments from `user/assets/js/user.js` (5 comment lines)
- All JS files now follow project rule: NO comments in code

### Pending
- [x] Email delivery (wire SMTP config to actually send reset links, booking confirmations)
- [ ] Email verification on registration
- [ ] Payment gateway integration (Stripe, Billplz, etc.)
- [x] Admin notification bell UI with dropdown
- [x] Reporting dashboard with charts (CSS-only bar/donut charts)
- [x] CSV/PDF export for bookings, expenses, orders (PDF for invoices, CSV for reports)
- [ ] Pagination on all list pages
- [ ] 2FA (two-factor authentication)
- [x] Order management page for users (view order history, track status)
- [x] Admin order management (view/update order status)
- [ ] Image optimization on upload (resize, compress)
- [ ] Remember-me cookie implementation
- [ ] HTTPS enforcement in production
- [x] Booking quotation numbers on submission
- [x] E-invoice generation on booking approval
- [x] Availability calendar on booking page
- [x] My Bookings page for users
- [x] System-wide notifications across all portals
- [x] Gold/warm accent color scheme (Ella-inspired) - changed to red (#DC2626) in Session 6
- [x] Bootstrap Icons migration (replaced all SVG icons)
- [x] Auth navigation fix (absolute APP_URL paths)
- [x] Public landing page scroll reveal animations + stats counter
- [x] Admin dashboard charts (revenue bar, booking donut) + activity feed
- [x] Band dashboard next gig countdown + task progress bar
- [x] User booking multi-step form wizard
- [x] User dashboard booking timeline
- [x] Social media settings in admin + footer display
- [x] Auth back-to-home links
- [x] Booking payment system (payment_status, due_date, mark_paid)
- [x] Admin + User invoice with payment info
- [x] Cart bug fix (form action to data-action)
- [x] Booking page side-by-side layout
- [x] Music section (Spotify/YouTube embeds) on landing page
- [x] Gallery section on landing page
- [x] Media settings in admin (Spotify/YouTube URLs)
- [x] Comprehensive seed data (20 records per table)
- [x] Booking quotation price (user submits budget, admin sees in approve modal)
- [x] Receipt upload system (user uploads payment receipt, admin notified)
- [x] Invoice bank payment details (Maybank instructions on unpaid invoices)
- [x] Band expense receipt viewing (modal with image/PDF support)
- [x] Notification link navigation fix (subfolder-aware base URL detection)
- [x] Merchandise delete FK handling (auto-inactive on constraint error)
- [x] Index merch cards linked to registration
- [x] Complete SVG-to-Bootstrap-Icons cleanup (14 files, zero inline SVGs remaining)
- [x] Admin dashboard stat icons restored
- [x] Admin users JSON routing fix (edit + reset password working)
- [x] System-wide bug fixes and consistency improvements (Session 11)

---

## Enhancement Proposals

### High Priority

**1. Admin Notification Bell with Dropdown**
The notification badge loads a count but there is no dropdown to display notifications. Add a dropdown panel under the bell icon in the admin header that lists recent notifications with mark-as-read functionality. The `api/notifications.php` endpoint already supports `list`, `mark_read`, and `mark_all_read` actions.

**2. Band Portal Dashboard Completion**
The band dashboard (`band/dashboard.php`) has FullCalendar integrated but the task list, expense summary, and upcoming events sections need live data. Connect these widgets to the existing API endpoints (`api/tasks.php?format=list`, `api/events.php`). Add an expense summary card fetching from a new `api/expenses.php` band endpoint.

**3. Admin Order Management**
Orders are created during cart checkout (`api/cart.php`) but there is no admin interface to view or manage them. Create `admin/orders.php` with: order list with search/filter, order detail view (items, customer, totals), status update (pending/processing/shipped/completed/cancelled), and add it to the sidebar.

**4. User Order History**
Customers can place orders but have no way to view them after checkout. Create `user/orders.php` showing order history with status tracking, order detail view with item breakdown, and totals.

### Medium Priority

**5. Reporting Dashboard with Charts**
Add a `admin/reports.php` page with Chart.js visualizations: monthly revenue trend (line chart), bookings by status (doughnut), expenses by category (bar chart), task completion rate (progress bar), and top merchandise by sales (horizontal bar). Data sourced from existing tables.

**6. CSV/PDF Export**
Add export buttons to admin list pages. CSV export is partially implemented in merchandise.js and expenses.js but needs consistent implementation across bookings, tasks, and orders. PDF export could use a library like TCPDF or DomPDF for formatted reports.

**7. Pagination**
All list pages currently load all records. Add server-side pagination with `LIMIT/OFFSET` to: admin bookings, expenses, tasks, merchandise, users, and user-facing lists. Use a shared `paginate()` helper in functions.php that returns `{data, total, page, per_page, total_pages}`.

**8. Band Expense Submission**
The band portal has an expenses page (`band/expenses.php`) but it needs to be connected to a working API. Band members should be able to submit expenses with receipt upload, view their submitted expenses, and see approval status. The admin approval flow already exists in `admin/expenses.php`.

### Low Priority

**9. Remember Me Cookie**
Implement a persistent login cookie using a secure random token stored in a `remember_tokens` table. Hash the token in the DB (like password hashing). Auto-login on session expiry if cookie is valid. Add a "Remember me" checkbox to the login form.

**10. Image Optimization on Upload**
Use PHP GD or Imagick to resize uploaded images (posters, merchandise photos, receipts) to reasonable dimensions (e.g., max 1200px width) and compress to reduce storage and page load times. The `uploadFile()` function in functions.php is the single point to add this.

**11. CSRF Token Rotation**
Currently CSRF tokens persist for the session. Rotate them per-form or per-request to prevent token reuse attacks. Update `generateCSRF()` and `verifyCSRF()` in session.php.

**12. Activity Log Viewer** (Completed - Session 7, item 70)
~~The `activity_log` table captures login attempts, settings changes, and registrations. Create an admin page to view and search the activity log with date filtering. Useful for auditing.~~

**13. Multi-language Support**
Prepare for internationalization by extracting all UI strings into a language file (`config/lang/en.php`). Load the language array in bootstrap.php and use a `t('key')` helper in templates. Start with English and Malay.

---

## Open Questions

1. Is payment processing needed now, and which gateway? (Stripe, Billplz, manual bank transfer)
2. Should email sending use a PHP library like PHPMailer or the built-in mail()?
3. Should the project move toward a front-controller/router pattern?
4. Are there plans for multi-band support?
5. What is the deployment target?
