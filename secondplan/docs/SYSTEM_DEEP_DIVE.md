# SecondPlan System Deep Dive

A mentor-style walkthrough for understanding every layer of the system.

---

## 1. Big Picture First

### What This System Really Does

SecondPlan is a **band management platform** for a Malaysian live music group. It solves three problems at once: it gives **customers** a way to discover the band and book them for events (weddings, corporate gigs, private parties), it gives **band members** a way to see their upcoming schedule, manage assigned tasks, and submit expenses, and it gives the **admin** (band manager) a central dashboard to approve bookings, generate invoices, track revenue, manage merchandise sales, and monitor everything happening across the system. Think of it as Shopify + Trello + an invoicing tool, all stitched together for a single band's operations.

### What Problem It Solves

Without this system, the band would manage bookings via WhatsApp messages, track expenses on spreadsheets, assign tasks through group chats, and sell merchandise manually. SecondPlan replaces all of that with a single web application where every interaction is recorded, every booking follows a defined lifecycle, and every user gets a portal tailored to their role.

### What Would Break If This System Disappeared

- Customers would have no way to submit booking inquiries or buy merchandise online
- The band manager would lose visibility into revenue, pending bookings, and overdue payments
- Band members would have no central place to see their tasks or upcoming gigs
- All historical data (past events, booking records, payment history, activity logs) would be lost

---

## 2. User Journey (Mental Model)

### Journey A: A Customer Books the Band

1. A visitor lands on `index.php` (the public landing page). The page queries the database for upcoming events, band members, merchandise, and settings (social links, Spotify embeds). No login required.

2. The visitor scrolls to the "Book Us" section and fills out a form: their name, email, event type, date, location, and budget.

3. On form submit, the PHP backend in `index.php` validates the input (name, email, event type, date are required). If validation passes, it generates a **quotation number** like `QT-20260210-A3F2` using the current date + random bytes.

4. A new row is inserted into the `bookings` table with `status = 'pending'` and `user_id = NULL` (because this visitor may not have an account). An email is sent to the visitor confirming receipt (in development mode, this is logged to `logs/emails.log` instead of actually sent).

5. The admin sees this new booking appear on `admin/bookings.php`. The page loads all bookings via an AJAX call (`fetch('bookings.php?api=list')`), and the JavaScript renders them into an HTML table.

6. The admin clicks "Approve", which opens a modal asking for the final price. On confirmation, the backend generates an **invoice number** like `INV-20260210-B7C1`, sets `payment_status = 'unpaid'`, calculates a `payment_due_date` (14 days from today), and creates a notification for the customer.

7. If the customer has an account and is logged in, they see the notification badge update (the notification system polls every 30 seconds). They can view their booking status on `user/my_bookings.php`, download the invoice, and upload a payment receipt.

8. Once the admin confirms payment (`mark_paid` action), the booking's `payment_status` changes to `'paid'`, `paid_at` is set to the current timestamp, and the customer gets another notification + email.

### Journey B: A Band Member Checks Their Schedule

1. A band member logs in at `auth/login.php`. The login query joins `users`, `user_roles`, and `roles` tables to determine the user's role. If the role is `band_member`, it's normalized to `member`, and the session is set.

2. The `login.php` switch statement redirects to `band/dashboard.php`. The `require_role()` call at the top of that file checks the session; if the role doesn't match, the user gets a 403.

3. The dashboard queries: pending tasks assigned to this user, upcoming events in the next 30 days, expense totals, and task completion percentages.

4. A FullCalendar widget loads events from `api/events.php` and tasks from `api/tasks.php`. These API endpoints return JSON arrays that FullCalendar renders as calendar entries.

5. The band member can click a task to view details, mark it as complete, or start it. They can also submit expenses through `band/expenses.php`.

### Journey C: Admin Manages the System

1. Admin logs in, gets redirected to `admin/dashboard.php`. This page runs ~10 database queries to populate stat cards (total revenue, bookings, pending tasks, monthly expenses), a CSS bar chart of monthly revenue, a donut chart of booking statuses, recent bookings table, upcoming events list, and an activity feed.

2. From the sidebar, the admin can navigate to 11 different pages: Users, Bookings, Events, Tasks, Expenses, Merchandise, Orders, Reports, Activity Log, and Settings. Each page follows the same pattern: PHP at the top handles API requests, HTML below renders the UI, and a page-specific JS file handles client-side interactions.

### System Flow Summary

- Every page starts by loading `config/bootstrap.php`, which initializes the database, session, and all helper functions
- Authentication checks happen at the top of every protected page via `require_login()` and `require_role()`
- Data entry happens via HTML forms (traditional POST for auth pages, `fetch()` API calls for everything else)
- Server-side validation runs in PHP; client-side validation is minimal (HTML `required` attributes)
- Actions that affect other users trigger notifications via `createNotification()` and emails via the email system
- The notification system is a shared IIFE script (`assets/js/notifications.js`) that auto-enhances any button with class `notification-btn`
- All user actions that matter are logged to the `activity_log` table via `logActivity()`

---

## 3. Core Logic (Heart of the System)

### The Single Most Important Logic: The Booking Lifecycle

The booking is the central entity. Almost every feature in the system connects back to it:

- **Revenue** is calculated from approved bookings (`SUM(price) FROM bookings WHERE status = 'approved'`)
- **Invoices** are generated only when a booking is approved
- **Payments** are tracked on bookings
- **Notifications** are triggered by booking status changes
- **Emails** are sent at each lifecycle transition
- **The dashboard charts** visualize booking data
- **Reports** aggregate booking and payment data

The lifecycle looks like this:

```
[Guest submits form] --> status: 'pending', quotation_number generated
        |
        v
[Admin reviews] --> approves or rejects
        |                    |
        v                    v
  status: 'approved'    status: 'rejected'
  invoice_number generated   (end of flow)
  payment_status: 'unpaid'
  payment_due_date: +14 days
        |
        v
[Customer uploads receipt / Admin marks paid]
        |
        v
  payment_status: 'paid'
  paid_at: NOW()
```

### Why Most Features Depend on This Logic

The admin dashboard's revenue card, the bar chart, the donut chart, and the "Recent Bookings" table all query the `bookings` table. The user dashboard shows a booking timeline visualization. The notification system's most common triggers are booking state changes. The email system has 4 out of 7 templates related to bookings.

### What Assumptions This Logic Makes

1. **Every booking goes through admin review.** There is no auto-approval. If the admin doesn't act, bookings stay pending forever.
2. **Price is set at approval time**, not at submission. The customer submits a budget, but the admin sets the real price.
3. **Payment is manual.** There is no payment gateway integration. The customer uploads a receipt, and the admin manually marks it as paid.
4. **`user_id` can be NULL.** Guest bookings from the landing page don't require an account. This means some bookings exist without a user to notify.
5. **Quotation and invoice numbers are random.** They use `bin2hex(random_bytes())`, so there is no sequential numbering. Two bookings made at the same millisecond won't collide, but the numbers are not human-sortable.

### If You Were Debugging This for the First Time

Start at `admin/bookings.php`. The top half of that file is the API handler. Look for `$action = $_GET['api'] ?? $_POST['api'] ?? null;`. Each `if` block handles one action: `list`, `approve`, `reject`, `mark_paid`, `delete`. The bottom half is the HTML. The JavaScript in `admin/assets/js/bookings.js` calls these API endpoints using `fetch()` and renders the results. If bookings aren't loading, check the `list` handler. If approval isn't working, check the `approve` handler. The pattern is: **one PHP file handles both the page render AND the API requests**, differentiated by the `api` parameter.

---

## 4. Data Lifecycle

### Where Data Comes From

Data enters the system from four sources:

| Source | Entry Point | Example |
|--------|-------------|---------|
| Public form | `index.php` booking form | Guest booking submission |
| Auth forms | `auth/register.php`, `auth/login.php` | User creation, session creation |
| Admin forms | `admin/bookings.php`, `admin/events.php`, etc. | Creating events, approving bookings |
| User/Band forms | `user/booking.php`, `band/expenses.php` | Customer bookings, expense claims |

### How and Where It Is Validated

Validation happens in PHP, **server-side only**. There is no frontend validation library. Here is the pattern:

```php
// From register.php - validation is a series of if-checks
$name = sanitize($_POST['name'] ?? '');        // strip_tags + trim
$email = trim($_POST['email'] ?? '');
if ($name === '') $errors[] = 'Name is required';
if (!isValidEmail($email)) $errors[] = 'Valid email is required';
if (strlen($password) < PASSWORD_MIN_LENGTH) $errors[] = 'Password too short';
if ($role !== 'customer') $errors[] = 'Invalid account type';  // hardcoded guard
```

Key validation functions in `includes/functions.php`:
- `sanitize()` - strips HTML tags and trims whitespace
- `e()` - escapes output with `htmlspecialchars()` to prevent XSS
- `isValidEmail()` - wraps `filter_var()` with `FILTER_VALIDATE_EMAIL`
- `verifyCSRF()` - uses `hash_equals()` to compare tokens (timing-safe)

### Where It Is Transformed

- **Passwords** are hashed using `password_hash()` with `PASSWORD_DEFAULT` (bcrypt) before storage
- **Dates** are formatted using `formatDate()` for display but stored as raw `DATE` / `DATETIME` in MySQL
- **Money** is stored as `DECIMAL(10,2)` and formatted with `formatMoney()` for display (adds "RM" prefix)
- **Reference numbers** are generated at specific lifecycle moments: quotation number at booking creation, invoice number at approval, order number at checkout

### Where It Is Stored

All data lives in a single MySQL database called `secondplan` with 10 tables:

| Table | Purpose | Key Relationships |
|-------|---------|-------------------|
| `users` | All user accounts | Referenced by almost everything |
| `roles` | Role definitions (admin, band_member, customer) | Linked via `user_roles` |
| `user_roles` | Many-to-many user-role mapping | FK to users + roles |
| `events` | Band gigs and performances | `created_by` -> users |
| `bookings` | Customer booking requests | `user_id` -> users (nullable) |
| `tasks` | To-do items for band members | `assigned_to`, `assigned_by` -> users |
| `expenses` | Band expense claims | `submitted_by`, `approved_by` -> users |
| `merchandise` | Products for sale | Standalone |
| `orders` / `order_items` / `cart` | E-commerce flow | `user_id` -> users, `merch_id` -> merchandise |
| `notifications` | In-app notification queue | `user_id` -> users |
| `activity_log` | Audit trail | `user_id` -> users (nullable) |
| `settings` | Key-value configuration store | Standalone |

### Where It Is Read Again

- **Dashboard pages** aggregate data with `COUNT()`, `SUM()`, and `GROUP BY` queries
- **List pages** fetch all records with `ORDER BY ... DESC`
- **API endpoints** return JSON for JavaScript to render (bookings list, events for calendar, notifications)
- **The landing page** reads events, merchandise, band members, and settings to display publicly

### Risky Points for Data Corruption

1. **No database transactions around multi-step operations.** When a booking is approved, the system updates the booking, then queries it again, then creates a notification, then sends an email. If any step fails midway, you get partial state (e.g., booking approved but no notification sent).

2. **No optimistic locking.** If two admins approve the same booking simultaneously, both updates will succeed. The second one overwrites the first without warning.

3. **`user_id` NULL on guest bookings.** If a guest later creates an account with the same email, there is no mechanism to link their old bookings to their new account.

4. **Stock is not decremented in a transaction.** When a merchandise order is placed, the stock update and order creation are separate queries. Under high concurrent load, overselling is possible.

### What Happens If Validation Fails

- **Auth forms**: Error messages are stored in `$errors` array and displayed inline on the same page. The form preserves entered values using `value="<?= e($_POST['name'] ?? '') ?>"`.
- **API calls**: The handler returns `jsonError('message', 400)`, and the JavaScript `catch` block calls `showToast('message', 'error')` to display a red toast notification.
- **CSRF failure**: Returns HTTP 403 with a hard `die('CSRF token validation failed')`. The user must reload the page.

---

## 5. Why It Was Built This Way

### Design Choice 1: Procedural PHP Instead of a Framework

**Why chosen:** Speed of development. No need to learn Laravel's conventions, Eloquent ORM, or Blade templates. Each file is self-contained: open it, and you can see everything from the SQL query to the HTML output.

**Simpler alternatives:** None simpler. This IS the simple approach.

**What a framework gives you:** Routing, middleware, ORM, migrations, queue workers, rate limiting, validation classes, and automated testing. All of these are done manually (or not at all) in this system.

**Trade-off accepted:** Maintainability. In a framework, changing how authentication works means editing one middleware. Here, you must update `bootstrap.php`, `session.php`, AND every page that calls `require_login()`. But for an FYP with a single developer, procedural code is faster to write and easier to explain.

### Design Choice 2: Same PHP File for Page + API

Look at `admin/bookings.php`. The top half handles API requests (approve, reject, delete). The bottom half renders HTML. The `?api=approve` parameter differentiates API from page requests.

**Why chosen:** It keeps related logic together. You don't need to look in a separate `api/bookings.php` file to understand what happens when a booking is approved.

**Alternative:** Separate API files in an `api/` folder (which the system does for some things like `api/notifications.php` and `api/events.php`). The codebase is inconsistent here - some APIs are in dedicated files, some are embedded in page files.

**Trade-off:** Mixing concerns. The bookings page file is 293 lines because it handles both display AND business logic. In a larger system, this becomes hard to maintain.

### Design Choice 3: Roles Stored in a Separate Table with Many-to-Many Mapping

The system uses three tables: `users`, `roles`, and `user_roles`. This is a classic RBAC (Role-Based Access Control) pattern.

**Why chosen:** It allows a user to have multiple roles (though this feature isn't actively used). It also makes the roles extensible without altering the users table.

**Simpler alternative:** A single `role` column on the `users` table. This would eliminate two JOINs on every login query.

**Trade-off:** The login query is more complex (`GROUP_CONCAT(r.role_name)` with joins), but the schema is properly normalized and follows relational design principles.

### Design Choice 4: CSS-Only Charts Instead of a JavaScript Library

The admin dashboard uses pure CSS for its bar chart (percentage-height divs) and donut chart (conic-gradient).

**Why chosen:** Zero external dependencies. No Chart.js or D3 to load. The charts render instantly with the page.

**Alternative:** Chart.js would give interactive tooltips, animations, and responsive resizing.

**Trade-off:** The charts are static. They can't be hovered, zoomed, or dynamically updated without a page reload. But they are lightweight and visually effective for an FYP demo.

### Design Choice 5: CSRF Token in a Cookie

The system stores the CSRF token both in the session AND in a cookie. JavaScript reads it from the cookie via `document.cookie.match()` for AJAX requests.

**Why chosen:** This allows JavaScript `fetch()` calls to include the CSRF token without needing a `<meta>` tag in every page's `<head>`.

**Alternative:** A `<meta name="csrf">` tag (which the code also supports as a fallback). Some pages use one approach, some use the other.

**Trade-off:** Putting CSRF in a cookie with `httponly: false` means JavaScript can read it, which is the point. But it also means XSS vulnerabilities could read the CSRF token. The `SameSite: Strict` attribute helps mitigate this.

---

## 6. What Confuses Me (Parts That Feel Like Magic)

### Magic 1: How Does `bootstrap.php` Make Everything Available?

Every page starts with:
```php
require_once __DIR__ . '/../config/bootstrap.php';
```

This single line sets off a chain reaction:

```
bootstrap.php
  |-> config.php         (defines constants: DB_HOST, APP_URL, ROLE_ADMIN, etc.)
  |-> includes/database.php   (creates $pdo - a global PDO database connection)
  |-> includes/session.php     (calls initSession() - starts PHP sessions immediately)
  |-> includes/functions.php   (defines all helper functions: e(), sanitize(), etc.)
  |-> includes/auth_functions.php  (defines logActivity(), getLoginAttempts(), etc.)
  |-> includes/email.php       (defines all email-sending functions)
```

After this one `require_once`, every page has access to:
- `$pdo` (database connection, as a global variable)
- All session functions (`isLoggedIn()`, `getUserId()`, etc.)
- All helper functions (`e()`, `formatMoney()`, `createNotification()`, etc.)
- All constants (`APP_URL`, `ROLE_ADMIN`, etc.)

The `$pdo` variable is global. Functions that need it use `global $pdo;` at the top. This is the procedural PHP equivalent of dependency injection.

### Magic 2: How Does the Sidebar Know Which Page Is Active?

In `admin/includes/sidebar.php`:
```php
<a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
```

`$_SERVER['PHP_SELF']` contains the path of the currently executing PHP file (e.g., `/secondplan/admin/dashboard.php`). `basename()` extracts just the filename (`dashboard.php`). If it matches, the class `active` is added, which CSS styles with a highlight color.

This is a simple but effective pattern. No JavaScript, no framework routing. Just PHP comparing the current filename.

### Magic 3: How Does the Notification System Auto-Attach to Every Page?

The notification script (`assets/js/notifications.js`) is an **IIFE** (Immediately Invoked Function Expression):

```javascript
(function() {
    // All code here runs immediately when the script loads
    function init() {
        var btns = document.querySelectorAll('.notification-btn');
        // Attaches bell icon, dropdown, and click handler to each button
    }
    // Auto-runs on DOMContentLoaded
})();
```

Every page includes an empty `<button class="notification-btn"></button>` in its header. When `notifications.js` loads, the `init()` function finds that button, injects the bell SVG icon, creates the dropdown HTML, attaches click handlers, and starts polling `api/notifications.php` every 30 seconds for unread counts.

The beauty of this design: you don't need to write ANY notification-related code on individual pages. Just add the button with the right class and include the script.

### Magic 4: How Does Login Redirect Work After Session Timeout?

When you try to access a protected page while logged out:

1. `require_login()` checks `isLoggedIn()`, which returns `false`
2. Before redirecting, it saves the URL you were trying to visit: `$_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];`
3. You get redirected to `login.php`
4. After successful login, `login.php` checks: `$redirectTo = $_SESSION['redirect_after_login'] ?? null;`
5. If set, it redirects you there instead of the default dashboard

This is why you end up on the page you originally wanted after logging in, not always on the dashboard.

### Magic 5: How Does the "Same File = Page + API" Pattern Work?

In `admin/bookings.php`:

```php
$action = $_GET['api'] ?? $_POST['api'] ?? null;

if ($method === 'GET' && $action === 'list') {
    // Return JSON and exit
    echo json_encode(['success' => true, 'data' => $data]);
    exit;  // <--- THIS is the key
}

if ($method === 'POST' && $action === 'approve') {
    // Process and return JSON
    echo json_encode(['success' => true]);
    exit;  // <--- stops execution here
}

// If we reach here, no API action matched, so render the HTML page
?>
<!DOCTYPE html>
...
```

The `exit;` statements are critical. When JavaScript calls `fetch('bookings.php?api=list')`, PHP processes the API block, outputs JSON, and **stops**. The HTML below never runs. When a browser navigates to `bookings.php` (no `api` parameter), all the `if` blocks are skipped, and the HTML renders normally.

---

## 7. If I Change This, What Happens?

### Critical Part 1: `config/bootstrap.php`

**What depends on it:** Every single page in the system. All 30+ PHP files start with `require_once bootstrap.php`.

**What breaks if modified:**
- Remove `database.php` include -> Every database query fails. The entire system is dead.
- Remove `session.php` include -> Sessions don't start. Login breaks. CSRF breaks. Every protected page becomes inaccessible.
- Remove `functions.php` include -> `e()` undefined. Every page that outputs user data crashes with a fatal error. XSS protection disappears.
- Change `csrf_token()` function signature -> Every form with `value="<?= csrf_token() ?>"` breaks. All form submissions fail with CSRF errors.

**User-facing impact:** Total system failure for most changes.

**Data/security impact:** Removing `session.php` removes all authentication. Anyone can access admin pages by navigating directly to the URL.

**How to safely change it:**
- Add new includes at the end, never remove existing ones
- Test every portal (admin, band, user) and the landing page after any change
- Never rename the function signatures that bootstrap defines (`csrf_token`, `verify_csrf`, `require_login`, `require_role`)

**What should NOT be changed at the same time:** Don't simultaneously change `bootstrap.php` and any file it includes. Change one file at a time, test, then move to the next.

### Critical Part 2: The `bookings` Table Schema

**What depends on it:**
- `index.php` (guest booking form inserts here)
- `admin/bookings.php` (full CRUD + lifecycle management)
- `admin/dashboard.php` (revenue calculations, stats, charts)
- `user/dashboard.php` (booking counts, timeline)
- `user/my_bookings.php` (customer's booking list)
- `user/booking.php` (logged-in customer booking form)
- `admin/invoice.php` (reads booking data for PDF-style invoice)
- `admin/reports.php` (booking aggregation)
- `api/booked_dates.php` (returns dates that are already booked)

**What breaks if modified:**
- Rename `status` column -> Every query filtering by status fails. The admin can't approve or reject bookings. The dashboard charts crash.
- Remove `quotation_number` column -> Guest bookings fail to insert. The user portal can't display quotation references.
- Change `price` from DECIMAL to INT -> All money calculations lose decimal precision. Invoices show wrong amounts.
- Remove `user_id` nullable -> Guest bookings from `index.php` fail with a foreign key constraint error.

**User-facing impact:** Booking submission fails. Admin dashboard shows errors. Revenue reporting breaks. Invoices generate incorrectly.

**Data/security impact:** Changing the `payment_status` enum without migrating existing data could lock out payment tracking. Adding `NOT NULL` constraints to columns that have existing NULL values will fail.

**How to safely change it:**
- Always use `ALTER TABLE` migrations, never drop and recreate
- Back up the table before schema changes
- Update ALL PHP files that query this table (search for `bookings` across the codebase)
- Test the full booking lifecycle: submit -> approve -> mark paid

### Critical Part 3: `includes/session.php` (Authentication)

**What depends on it:**
- Every protected page (through `require_login()` and `require_role()`)
- The login process (through `setUserSession()`)
- The logout process (through `destroySession()`)
- CSRF protection (through `generateCSRF()` and `verifyCSRF()`)
- User identity on every page (through `getUserId()`, `getUserRole()`, `getUserData()`)

**What breaks if modified:**
- Change `$_SESSION['user_role']` key name -> `getUserRole()` returns null. `require_role()` denies everyone. All portals become inaccessible.
- Change `setUserSession()` parameter order -> Login succeeds but stores wrong data. A user's name appears as their email, or their role is wrong, granting unauthorized access.
- Break `generateCSRF()` -> All form submissions fail. Users can't log in, register, submit bookings, or perform any POST action.
- Remove `session_regenerate_id(true)` from `setUserSession()` -> Session fixation vulnerability. An attacker who knows a pre-login session ID can hijack the post-login session.

**User-facing impact:** Authentication failures. Users locked out. Or worse: users accessing the wrong portal.

**Data/security impact:** This is the most security-critical file. Incorrect changes can expose admin functionality to regular users, or break CSRF protection allowing cross-site attacks.

**How to safely change it:**
- Write down every function name and its callers before editing
- Test login flow for all three roles (admin, band, customer) after any change
- Test CSRF by submitting forms
- Test session timeout behavior (wait 30 minutes)
- Never change session key names without updating all files that read them

---

## 8. Where Should Logic Live?

This system doesn't use the MVC pattern, but the same principles apply. Let me map it to what your code actually does:

### Controller Logic (Decides WHAT to Do)

In your system, this is the **top section of each PHP page** - the part before the `?>` and HTML. It:
- Checks authentication (`require_login()`, `require_role()`)
- Reads the request (`$_GET['api']`, `$_POST`)
- Decides which action to take (the `if ($action === 'approve')` blocks)
- Calls functions that do the work
- Returns responses (JSON for APIs, page render for browsers)

**Example:** `admin/bookings.php` lines 1-181 are controller logic.

### Service/Helper Logic (Does THE Work)

In your system, this lives in the `includes/` folder:
- `functions.php` - utility operations (sanitize input, format dates, upload files, create notifications)
- `auth_functions.php` - authentication operations (log activity, check login attempts)
- `email.php` - all email composition and sending
- `session.php` - session management and CSRF

**Example:** `createNotification()` in `functions.php` is service logic. It doesn't decide WHEN to send a notification (that's the controller's job), it just knows HOW to insert one into the database.

### Model Logic (Talks to the Database)

In your system, there are **no model files**. Database queries are written directly in the controller logic using raw PDO:

```php
$stmt = $pdo->prepare("UPDATE bookings SET status = 'approved' WHERE booking_id = ?");
$stmt->execute([$id]);
```

In a framework, this would be:
```php
Booking::where('booking_id', $id)->update(['status' => 'approved']);
```

### Why These Boundaries Matter

Even without formal MVC, understanding these layers helps you answer viva questions like:

> "If you needed to add SMS notifications alongside email, where would you change?"

Answer: You'd add the SMS logic in `includes/` (service layer) and call it from the same places that call `createNotification()` (controller layer). You would NOT put SMS-sending code inside an HTML template.

> "If you needed to switch from MySQL to PostgreSQL, what would change?"

Answer: Only `includes/database.php` (the connection DSN) and any MySQL-specific SQL syntax. The helper functions, controllers, and HTML wouldn't change because they don't know about the database engine directly - they talk to PDO, which is database-agnostic.

---

## 9. System Weaknesses (Honest Assessment)

### Weakness 1: No Input Validation Library

Validation is scattered across files as ad-hoc `if` checks. There's no centralized validation system. This means:
- Validation rules for the same field (e.g., email) are duplicated across files
- It's easy to forget a validation check when adding a new form
- There's no client-side validation beyond HTML `required` attributes

### Weakness 2: SQL Queries Are Not Parameterized Everywhere

Most queries use prepared statements correctly (`$pdo->prepare("... WHERE id = ?")`, `$stmt->execute([$id])`). However, some dashboard queries use string concatenation or direct `query()` calls without parameters. While these specific queries don't include user input (so they're not vulnerable to injection), the inconsistent pattern makes it easy to introduce vulnerabilities when adding new features.

### Weakness 3: No Database Transactions

Multi-step operations (approve booking + create notification + send email) are not wrapped in database transactions. If the notification insert fails but the booking update succeeded, the system is in an inconsistent state. This is unlikely to happen in normal use but is a real risk under database errors or server crashes.

### Weakness 4: Global `$pdo` Variable

Every function that needs the database uses `global $pdo;`. This is the procedural equivalent of a global singleton. It works, but it makes unit testing impossible (you can't mock the database connection) and creates hidden dependencies - you can't tell from a function's signature that it needs a database connection.

### Weakness 5: No Rate Limiting on API Endpoints

The login page has brute-force protection (`MAX_LOGIN_ATTEMPTS` / `LOGIN_LOCKOUT_TIME`), but API endpoints like bookings, notifications, and merchandise have no rate limiting. A malicious user could spam booking submissions or overwhelm the notification system.

### Weakness 6: Inconsistent API Pattern

Some APIs are in dedicated files (`api/notifications.php`, `api/events.php`, `api/cart.php`), while others are embedded in page files (`admin/bookings.php?api=list`). This makes it hard to know where to look when debugging API issues.

### Weakness 7: No Automated Tests

There are no unit tests, integration tests, or end-to-end tests. Every change must be tested manually by clicking through the UI. This makes refactoring risky because you can't verify that existing functionality still works.

### Weakness 8: Email System in Development Mode

The email system logs to a file instead of sending real emails when `APP_ENV === 'development'`. This is fine for development, but the `mail()` function used in production mode is unreliable. Most production PHP applications use an SMTP library like PHPMailer or send via an API (Mailgun, SendGrid). The `mail()` function often gets blocked by spam filters.

### Weakness 9: No Password Complexity Enforcement

Registration only checks `strlen($password) < PASSWORD_MIN_LENGTH` (8 characters). There's no requirement for uppercase, lowercase, numbers, or symbols. A user could register with "aaaaaaaa" as their password.

### Weakness 10: Client-Side Rendering of Sensitive Data

The admin bookings page fetches ALL bookings as JSON and renders them in JavaScript. This means every booking (including company names, prices, and payment statuses) is sent to the browser in one API call. For a small band this is fine, but it doesn't scale and exposes all data to anyone who can access the admin panel.

---

## 10. Learning Check (Viva-Style Questions)

### Question 1: The Booking Status Lifecycle

A customer submits a booking from the landing page, and two hours later the admin approves it. Walk me through every database change that happens from submission to approval. Which tables are affected, what values change, and in what order?

### Question 2: Authentication Bypass Scenario

If you accidentally deleted the `require_login()` call from the top of `admin/bookings.php`, what would happen? Could a non-logged-in user see the booking data? Could they approve or reject bookings? Why or why not?

### Question 3: The CSRF Protection Flow

Explain how your CSRF protection works step by step: where the token is created, how it reaches the browser, how it's sent back with requests, and how it's verified. Then explain: what specific attack does CSRF protection prevent, and give a concrete example of how an attacker could exploit your system if CSRF protection was removed.

### Question 4: Impact Analysis

You need to add a new field `number_of_guests` to the booking form on the landing page. List every file you would need to modify and explain why each one needs to change. What would happen if you added the column to the database but forgot to update one of those files?

### Question 5: Design Decision Defense

Your examiner says: "Why didn't you use Laravel? It would have been more professional." How would you defend your choice of procedural PHP? What specific trade-offs did you accept, and what would you gain or lose by switching to Laravel at this point?
