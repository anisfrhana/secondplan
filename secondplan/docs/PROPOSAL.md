# SECONDPLAN - Project Proposal

## TABLE OF CONTENTS

1. [INTRODUCTION](#1-introduction)
2. [PROBLEM DESCRIPTION](#2-problem-description)
3. [OBJECTIVES](#3-objectives)
4. [BENEFITS](#4-benefits)
5. [LITERATURE SURVEY & BENCHMARKING](#5-literature-survey--benchmarking)
6. [METHODOLOGY](#6-methodology)
   - 6.1 Approach: Agile
   - 6.2 System Design
   - 6.3 Database Design
   - 6.4 Data Dictionary
7. [WORK PLAN](#7-work-plan)
8. [CONCLUSION](#8-conclusion)
9. [REFERENCES](#9-references)

---

## 1. INTRODUCTION

Independent bands often operate without a centralized system, relying heavily on manual processes to manage bookings, expenses, merchandise, and communication. This leads to inefficiencies, errors, and inconsistent information sharing. The Second Plan System is a web-based platform designed to streamline these operations by providing a structured and user-friendly solution for band administrators, band members, and customers.

The system is organized into three distinct portals. The Admin Portal allows the band manager to oversee all operations including event management, booking approvals with quotation-to-invoice workflows, expense tracking, task assignment, merchandise catalog management, order fulfillment, and financial reporting. The Band Member Portal provides musicians with a personal schedule calendar, assigned task management, expense claim submissions with receipt uploads, and event visibility. The Customer Portal enables clients to browse upcoming events, submit performance booking requests with an availability calendar, purchase merchandise through an integrated shopping cart and checkout system, track order deliveries, and view invoices with payment instructions.

By integrating these core features into a single platform with role-based access control, real-time notifications, and comprehensive activity logging, the Second Plan System enhances operational efficiency, improves financial transparency, and strengthens interaction between the band, clients, and fans. Its scalable architecture and responsive design ensure it can support the band's growth across desktop and mobile devices.

---

## 2. PROBLEM DESCRIPTION

Independent bands such as Sofarz face several operational challenges due to the absence of an integrated management system. The key issues include:

### 1. Inefficient Event and Task Management
Bookings, schedules, and task assignments are currently handled manually through WhatsApp messages and spreadsheets, leading to double bookings, missed responsibilities, and weak coordination among band members. There is no centralized calendar to view all commitments, and no automated system to assign and track task completion.

### 2. Limited Financial and Merchandise Oversight
Expense tracking, receipt storage, reimbursement monitoring, and merchandise stock updates are done manually, increasing the risk of errors, missing records, and delays in accountability. Booking payments lack a formal quotation-to-invoice workflow, making it difficult to track outstanding payments and due dates.

### 3. Lack of Structured Fan and Client Engagement
Fans rely solely on social media without a dedicated platform for updates, while clients have no centralized system to book performances, check date availability, or receive formal quotations and invoices. This results in lost engagement and business opportunities.

---

## 3. OBJECTIVES

The objectives of the Second Plan System are:

1. To develop a centralized web-based system with three role-based portals (Admin, Band Member, Customer) for managing events, bookings, and tasks efficiently.
2. To enhance financial oversight through a structured quotation-to-invoice booking workflow, expense approval pipeline, and merchandise order management with payment tracking.
3. To improve customer accessibility through an integrated booking system with availability calendar, e-commerce merchandise shop with shopping cart, and automated notification system.

---

## 4. BENEFITS

The Second Plan System provides significant advantages to the band, clients, and fans by addressing the identified problems:

### i. Improved Operational Efficiency
- Automates event scheduling, booking approvals with quotation and invoice generation, and task assignment with notifications to band members.
- Minimizes errors, prevents double bookings through an availability calendar, and ensures better coordination among band members via a shared schedule calendar.
- Provides a comprehensive reports dashboard with revenue analytics, expense breakdowns, and merchandise sales tracking.

### ii. Enhanced Financial Transparency
- Implements a complete quotation-to-invoice payment workflow with automated due date tracking and payment confirmation notifications.
- Allows band members to submit expense claims with receipt uploads, with an admin approval and rejection pipeline.
- Tracks merchandise inventory with stock level alerts, order fulfillment status, and revenue reporting.

### iii. Better Customer Interaction
- Customers can submit booking requests with an interactive availability calendar, receive quotations and invoices, and upload payment receipts directly through the platform.
- An integrated merchandise shop with shopping cart and checkout enables online purchasing of band merchandise.
- Real-time notifications keep all users informed of booking approvals, payment confirmations, task assignments, and order status changes.

### iv. Scalable and User-Friendly Platform
- Responsive design works across desktop and mobile devices with a sidebar navigation that adapts to screen size.
- Role-based access control ensures each user group sees only relevant features and data.
- Activity logging and audit trails support accountability and operational review.

---

## 5. LITERATURE SURVEY & BENCHMARKING

Independent bands in Malaysia often operate without structured digital systems, relying instead on a mixture of social media platforms, event websites, and manual record-keeping to manage their performances, fan engagement, and finances. Based on the review of existing Malaysian platforms like GoLive, Gigsmore, and LOLAsia, it is evident that while these systems offer useful public-facing functions, none provide a dedicated, end-to-end operational solution for independent bands. This creates significant challenges in coordinating bookings, managing tasks, tracking expenses, and maintaining organized communication with fans and clients.

**GoLive (Malaysia)** is widely used for event ticketing and audience management. Bands often rely on this platform to promote shows and sell tickets. Although GoLive provides robust tools for registration, promotion, and participant analytics, it focuses primarily on public event management. It does not address internal band needs such as coordinating roles, uploading receipts, managing reimbursements, or tracking band-owned merchandise. Bands must therefore combine GoLive with additional manual workflows to complete their operational tasks.

**Gigsmore** is one of the few Malaysian platforms focused on connecting artists and event organizers. It simplifies the process of discovering gigs and allows performers to apply for available slots. However, its features are limited to gig listing and matching; it does not support internal band operations such as expense tracking, merchandise management, or task assignment. As a result, indie bands still handle most operational activities manually through WhatsApp, Excel, and social media announcements.

**LOLAsia**, a Malaysian entertainment ticketing and event promotion platform, offers a centralized space for events and performances, helping entertainers reach wider audiences. It is useful for fans who want to browse upcoming shows. However, it also lacks internal management features for artists or bands. There is no support for expense logging, band communication, multi-role collaboration, or merchandise monitoring, limiting its use to public-facing functions only.

The analysis reveals a clear gap: existing Malaysian platforms support public promotion and ticketing but do not offer tools for internal management, which is essential for independent bands that lack structured administrative systems. Second Plan System aims to fill this gap by providing an integrated solution that combines event management, booking workflows with quotation-to-invoice processing, task coordination, expense tracking with receipt management, merchandise e-commerce, order fulfillment, and role-based portals in a single system. This ensures that indie bands can manage operations more professionally and efficiently without relying on scattered systems and manual workflows.

### BENCHMARKING ANALYSIS

| Feature | Second Plan System | GoLive | LOLAsia | Gigsmore |
|---|---|---|---|---|
| Event Management | YES | YES | YES | YES |
| Booking with Quotation & Invoice | YES | NO | NO | NO |
| Fan/Customer Engagement | YES | YES | YES | NO |
| Merchandise E-Commerce | YES | NO | NO | NO |
| Shopping Cart & Checkout | YES | NO | NO | NO |
| Task Coordination | YES | NO | NO | NO |
| Expense Tracking & Receipts | YES | NO | NO | NO |
| Payment Tracking & Invoicing | YES | NO | NO | NO |
| Role-Based Portals | YES (3 Portals) | NO | NO | NO |
| Notification System | YES | YES | NO | NO |
| Reports & Analytics | YES | YES | NO | NO |
| Activity Audit Log | YES | NO | NO | NO |
| **Relevance to Independent Bands** | Designed specifically for independent bands; all-in-one coordination tool | Useful for organizing fan-attended events | Good for promotion and public listings | Suitable for discovering performance opportunities |
| **Limitations** | Requires hosting and user training | Designed for event organizers, not performers; no internal band operations | Lacks operational and administrative modules for bands | Does not offer internal band management tools |

---

## 6. METHODOLOGY

### 6.1 Approach: Agile

The project follows Agile methodology with iterative development across 11 development sessions:

1. **Requirement Gathering** - Analyze current band workflow and operational needs across all user roles.
2. **System Design** - Develop system architecture, database schema (ERD), and interface wireframes for three portals.
3. **Foundation Build** - Core architecture setup including authentication, session management, CSRF protection, file upload validation, and database connectivity.
4. **Module Development** - Iterative development of Event, Booking, Merchandise, Task, Expense, Order, Notification, and Reporting modules.
5. **UI/UX Refinement** - Responsive sidebar layout implementation, Bootstrap Icons integration, and consistent design system across all portals.
6. **Security Hardening** - SQL injection fixes, XSS protection, CSRF enforcement on all API endpoints, rate limiting, and input validation.
7. **Testing & Documentation** - System-wide audit, bug fixes, and comprehensive project documentation.

### 6.2 System Design

#### 6.2.1 System Architecture

| Layer | Technology |
|---|---|
| Presentation Layer | HTML5, CSS3, JavaScript (Vanilla), Bootstrap Icons 1.11.3 |
| Application Layer | PHP 8.x (Procedural) |
| Database Layer | MySQL 8.0 with PDO |
| Calendar Integration | FullCalendar 6.1.9 |
| PDF Export | html2pdf.js v0.10.1 |

**Architecture Pattern:** Multi-portal application with shared configuration bootstrap, role-based access control, RESTful JSON API endpoints, and server-rendered pages.

#### 6.2.2 User Groups

| User Group | Portal | Key Capabilities |
|---|---|---|
| Admin | `/admin/` | Manage events, bookings (approve/reject with invoice generation), tasks (assign to band members), expenses (approve/reject), merchandise catalog, orders, users, settings, reports, and activity logs. |
| Band Member | `/band/` | View schedule calendar (FullCalendar), manage assigned tasks (update status), submit expenses with receipts, view band events, and edit profile. |
| Customer | `/user/` | Submit booking requests with availability calendar, browse and purchase merchandise (cart & checkout), view booking status and invoices, upload payment receipts, track orders, and edit profile. |

#### 6.2.3 System Modules

**1. Event Management Module**
- Admin creates, updates, cancels, and deletes events with details (title, date, time, venue, location, capacity, price, poster image).
- Events displayed on public landing page with countdown timer to next event.
- Band members view events on a shared FullCalendar schedule.

**2. Booking & Invoice Module**
- Customers submit booking requests through a form with an interactive availability calendar showing booked and pending dates.
- System generates a quotation number (QT-YYYYMMDD-XXXX) on submission.
- Admin approves with price setting, generating an invoice number (INV-YYYYMMDD-XXXX) and setting a 14-day payment due date.
- Customers view invoices with bank payment instructions and upload payment receipts.
- Admin marks bookings as paid with automated notification to customer.
- Printable and PDF-exportable invoices.

**3. Merchandise & E-Commerce Module**
- Admin manages product catalog with SKU, pricing, stock levels, categories (Apparel, Accessories, Music, Collectibles), and images.
- Low stock threshold alerts on admin dashboard.
- Customers browse products with search and category filtering, add to cart, and checkout.
- Transactional checkout that validates stock, creates orders, decrements inventory, and generates order numbers (SP-YYYYMMDD-XXXXXXXX).
- Admin manages order fulfillment with status tracking (Pending, Processing, Shipped, Delivered, Cancelled).

**4. Task Management Module**
- Admin creates and assigns tasks to band members with priority levels (Low, Medium, High, Urgent) and due dates.
- Band members view assigned tasks, update status (Todo, In Progress, Completed), and see tasks on FullCalendar color-coded by priority.
- Automated notifications sent to band members on task assignment.

**5. Expense Tracking Module**
- Band members submit expense claims with category (Equipment, Food, Marketing, Rental, Transport, Venue, Other), amount, vendor, description, and receipt upload.
- Admin reviews, approves, or rejects expense claims.
- Receipt viewing supports both image (JPG/PNG) and PDF formats.
- CSV export for expense reporting.

**6. Notification Module**
- Real-time notification system with bell icon dropdown on all pages across all three portals.
- Notification types: booking approved/rejected, payment confirmed, task assigned, expense submitted, order placed, new booking.
- Polling-based updates every 30 seconds with mark-as-read and mark-all-read functionality.

**7. Reports & Analytics Module**
- Admin reports dashboard with date range filtering (Today, This Week, This Month, Last Month, This Year, All Time, Custom).
- Financial overview: booking revenue, merchandise sales, total expenses, net profit.
- Revenue vs expenses bar chart (last 12 months).
- Booking status donut chart.
- Expenses by category breakdown with progress bars.
- Top selling merchandise products.
- Payment and merchandise summary statistics.
- CSV export for all report data.

**8. Email Notification Module**
- HTML email templates for 7 notification types: password reset, booking submitted, booking approved, booking rejected, payment confirmed, order confirmed, task assigned.
- Development mode logs emails to file; production mode sends via PHP mail().

**9. Authentication & Security Module**
- Login with rate limiting (5 failed attempts trigger 15-minute lockout).
- Password reset flow with secure tokens and 1-hour expiry.
- CSRF protection on all forms and API endpoints.
- File upload validation using MIME type detection with cryptographically random filenames.
- Role-based access control enforced on all portal pages and API endpoints.
- Activity logging for audit trail (login, registration, settings changes, booking actions).

### 6.3 Database Design

The database consists of 10 tables and 3 reporting views.

**Entity Relationship Diagram**

*(Insert ERD diagram here)*

Key relationships:
- `users` connects to `roles` through `user_roles` junction table (many-to-many).
- `events` links to `users` via `created_by` (one-to-many).
- `bookings` links to `users` via `user_id` (nullable for guest bookings) and `approved_by`.
- `tasks` links to `users` via `assigned_to` and `assigned_by`, and to `events` via `event_id`.
- `expenses` links to `users` via `submitted_by` and `approved_by`, and to `events` via `event_id`.
- `orders` links to `users` via `user_id`, and connects to `merchandise` through `order_items`.
- `cart` links to `users` and `merchandise` with a unique constraint per user-item pair.
- `notifications` and `activity_log` link to `users` for tracking.

### 6.4 Data Dictionary

#### Table: roles

| Field Name | Description | Type | Key |
|---|---|---|---|
| role_id | Auto-increment unique role ID | INT UNSIGNED | PRIMARY KEY |
| role_name | Role identifier (admin, band_member, customer, client) | VARCHAR(50) UNIQUE | INDEX |
| description | Role description | TEXT | - |
| created_at | Timestamp of creation | TIMESTAMP | - |

#### Table: users

| Field Name | Description | Type | Key |
|---|---|---|---|
| user_id | Auto-increment unique user ID | INT UNSIGNED | PRIMARY KEY |
| email | Login email address | VARCHAR(255) UNIQUE | INDEX |
| password_hash | Bcrypt hashed password | VARCHAR(255) | - |
| name | Full name | VARCHAR(255) | - |
| phone | Contact number | VARCHAR(20) | - |
| position | Band member role/position | VARCHAR(100) | - |
| profile_image | Profile image file path | VARCHAR(255) | - |
| status | Account status (active, inactive, suspended) | ENUM | INDEX |
| email_verified | Email verification flag | BOOLEAN | - |
| verification_token | Email verification token | VARCHAR(64) | - |
| reset_token | Password reset token | VARCHAR(64) | - |
| reset_expires | Password reset token expiry | DATETIME | - |
| last_login | Last successful login timestamp | DATETIME | - |
| created_at | Account creation timestamp | TIMESTAMP | - |
| updated_at | Last update timestamp | TIMESTAMP | - |

#### Table: user_roles

| Field Name | Description | Type | Key |
|---|---|---|---|
| user_id | Reference to users table | INT UNSIGNED | PRIMARY KEY, FOREIGN KEY |
| role_id | Reference to roles table | INT UNSIGNED | PRIMARY KEY, FOREIGN KEY |
| assigned_at | Role assignment timestamp | TIMESTAMP | - |

#### Table: events

| Field Name | Description | Type | Key |
|---|---|---|---|
| event_id | Auto-increment unique event ID | INT UNSIGNED | PRIMARY KEY |
| title | Event name | VARCHAR(255) | - |
| description | Event details | TEXT | - |
| date | Event date | DATE | INDEX |
| start_time | Starting time | TIME | - |
| end_time | Ending time | TIME | - |
| venue | Event venue name | VARCHAR(255) | - |
| location | Event address/location | VARCHAR(255) | - |
| capacity | Maximum attendee capacity | INT UNSIGNED | - |
| seats_booked | Number of seats booked | INT UNSIGNED | - |
| price | Event price | DECIMAL(10,2) | - |
| status | Event status (scheduled, cancelled, completed, postponed) | ENUM | INDEX |
| poster_image | Event poster file path | VARCHAR(255) | - |
| created_by | Admin user who created the event | INT UNSIGNED | FOREIGN KEY |
| created_at | Creation timestamp | TIMESTAMP | - |
| updated_at | Last update timestamp | TIMESTAMP | - |

#### Table: bookings

| Field Name | Description | Type | Key |
|---|---|---|---|
| booking_id | Auto-increment unique booking ID | INT UNSIGNED | PRIMARY KEY |
| user_id | Customer who made the booking (nullable for guests) | INT UNSIGNED NULL | FOREIGN KEY, INDEX |
| event_id | Related event reference | INT UNSIGNED | FOREIGN KEY |
| company_name | Company or organization name | VARCHAR(255) | - |
| event_name | Requested event title | VARCHAR(255) | - |
| event_date | Requested event date | DATE | INDEX |
| event_time | Requested event time | TIME | - |
| location | Event location | VARCHAR(255) | - |
| address | Full address | TEXT | - |
| postal_code | Postal code | VARCHAR(20) | - |
| city | City | VARCHAR(100) | - |
| state | State | VARCHAR(100) | - |
| price | Approved price set by admin | DECIMAL(10,2) | - |
| quotation_price | Budget proposed by customer (per day) | DECIMAL(10,2) NULL | - |
| status | Booking status (pending, approved, rejected, cancelled, completed) | ENUM | INDEX |
| poster_event | Uploaded event poster file path | VARCHAR(255) | - |
| notes | Additional notes from customer | TEXT | - |
| quotation_number | Auto-generated quotation reference (QT-YYYYMMDD-XXXX) | VARCHAR(50) | - |
| invoice_number | Auto-generated invoice reference (INV-YYYYMMDD-XXXX) | VARCHAR(50) | - |
| payment_status | Payment status (unpaid, paid) | ENUM | - |
| payment_due_date | Payment deadline (14 days from approval) | DATE NULL | - |
| paid_at | Payment confirmation timestamp | DATETIME NULL | - |
| payment_receipt | Uploaded payment receipt file path | VARCHAR(255) NULL | - |
| admin_notes | Internal notes from admin | TEXT | - |
| approved_by | Admin who approved/rejected | INT UNSIGNED | FOREIGN KEY |
| approved_at | Approval/rejection timestamp | DATETIME | - |
| created_at | Submission timestamp | TIMESTAMP | - |
| updated_at | Last update timestamp | TIMESTAMP | - |

#### Table: tasks

| Field Name | Description | Type | Key |
|---|---|---|---|
| task_id | Auto-increment unique task ID | INT UNSIGNED | PRIMARY KEY |
| title | Task title | VARCHAR(255) | - |
| description | Task details | TEXT | - |
| assigned_to | Band member assigned to the task | INT UNSIGNED | FOREIGN KEY, INDEX |
| assigned_by | Admin who assigned the task | INT UNSIGNED | FOREIGN KEY |
| event_id | Related event reference | INT UNSIGNED | FOREIGN KEY |
| priority | Priority level (low, medium, high, urgent) | ENUM | - |
| status | Task status (todo, in_progress, completed, cancelled) | ENUM | INDEX |
| due_date | Task deadline date | DATE | INDEX |
| due_time | Task deadline time | TIME | - |
| completed_at | Completion timestamp | DATETIME | - |
| created_at | Creation timestamp | TIMESTAMP | - |
| updated_at | Last update timestamp | TIMESTAMP | - |

#### Table: expenses

| Field Name | Description | Type | Key |
|---|---|---|---|
| expense_id | Auto-increment unique expense ID | INT UNSIGNED | PRIMARY KEY |
| category | Expense category (Equipment, Food, Marketing, Rental, Transport, Venue, Other) | VARCHAR(100) | INDEX |
| amount | Expense amount in MYR | DECIMAL(10,2) | - |
| expense_date | Date of expense | DATE | INDEX |
| vendor | Vendor or payee name | VARCHAR(255) | - |
| reference | Reference number | VARCHAR(100) | - |
| description | Expense description | TEXT | - |
| notes | Additional notes | TEXT | - |
| receipt | Uploaded receipt file path | VARCHAR(255) | - |
| status | Approval status (pending, approved, rejected, paid) | ENUM | INDEX |
| submitted_by | Band member who submitted | INT UNSIGNED | FOREIGN KEY |
| approved_by | Admin who approved/rejected | INT UNSIGNED | FOREIGN KEY |
| event_id | Related event reference | INT UNSIGNED | FOREIGN KEY |
| approved_at | Approval timestamp | DATETIME | - |
| created_at | Submission timestamp | TIMESTAMP | - |
| updated_at | Last update timestamp | TIMESTAMP | - |

#### Table: merchandise

| Field Name | Description | Type | Key |
|---|---|---|---|
| merch_id | Auto-increment unique item ID | INT UNSIGNED | PRIMARY KEY |
| name | Product name | VARCHAR(255) | - |
| sku | Stock Keeping Unit code | VARCHAR(100) UNIQUE | INDEX |
| description | Product description | TEXT | - |
| price | Selling price in MYR | DECIMAL(10,2) | - |
| cost | Cost price in MYR | DECIMAL(10,2) | - |
| stock | Current stock quantity | INT UNSIGNED | - |
| low_stock_threshold | Alert threshold for low stock | INT UNSIGNED | - |
| category | Product category (Apparel, Accessories, Music, Collectibles) | VARCHAR(100) | INDEX |
| image | Product image file path | VARCHAR(255) | - |
| status | Product status (active, inactive, discontinued) | ENUM | INDEX |
| created_at | Creation timestamp | TIMESTAMP | - |
| updated_at | Last update timestamp | TIMESTAMP | - |

#### Table: orders

| Field Name | Description | Type | Key |
|---|---|---|---|
| order_id | Auto-increment unique order ID | INT UNSIGNED | PRIMARY KEY |
| user_id | Customer who placed the order | INT UNSIGNED | FOREIGN KEY, INDEX |
| order_number | Auto-generated order reference (SP-YYYYMMDD-XXXXXXXX) | VARCHAR(50) UNIQUE | INDEX |
| total_amount | Total order amount in MYR | DECIMAL(10,2) | - |
| status | Order status (pending, processing, shipped, delivered, cancelled) | ENUM | INDEX |
| payment_status | Payment status (unpaid, paid, refunded) | ENUM | - |
| payment_method | Payment method used | VARCHAR(50) | - |
| shipping_address | Delivery address | TEXT | - |
| notes | Order notes | TEXT | - |
| created_at | Order placement timestamp | TIMESTAMP | - |
| updated_at | Last update timestamp | TIMESTAMP | - |

#### Table: order_items

| Field Name | Description | Type | Key |
|---|---|---|---|
| item_id | Auto-increment unique line item ID | INT UNSIGNED | PRIMARY KEY |
| order_id | Reference to orders table | INT UNSIGNED | FOREIGN KEY |
| merch_id | Reference to merchandise table | INT UNSIGNED | FOREIGN KEY |
| quantity | Quantity ordered | INT UNSIGNED | - |
| price | Price per unit at time of purchase | DECIMAL(10,2) | - |
| subtotal | Line item total (price x quantity) | DECIMAL(10,2) | - |

#### Table: cart

| Field Name | Description | Type | Key |
|---|---|---|---|
| cart_id | Auto-increment unique cart entry ID | INT UNSIGNED | PRIMARY KEY |
| user_id | Customer who owns the cart item | INT UNSIGNED | FOREIGN KEY |
| merch_id | Merchandise item in cart | INT UNSIGNED | FOREIGN KEY |
| quantity | Quantity in cart | INT UNSIGNED | - |
| added_at | Timestamp when added to cart | TIMESTAMP | - |

*Unique constraint on (user_id, merch_id) prevents duplicate cart entries.*

#### Table: notifications

| Field Name | Description | Type | Key |
|---|---|---|---|
| notification_id | Auto-increment unique notification ID | INT UNSIGNED | PRIMARY KEY |
| user_id | Recipient user | INT UNSIGNED | FOREIGN KEY |
| type | Notification type (booking_approved, task_assigned, etc.) | VARCHAR(50) | - |
| title | Notification title | VARCHAR(255) | - |
| message | Notification message body | TEXT | - |
| link | Target page URL | VARCHAR(255) | - |
| is_read | Read status flag | BOOLEAN | INDEX (composite) |
| created_at | Creation timestamp | TIMESTAMP | - |

#### Table: activity_log

| Field Name | Description | Type | Key |
|---|---|---|---|
| log_id | Auto-increment unique log entry ID | INT UNSIGNED | PRIMARY KEY |
| user_id | User who performed the action (nullable for system events) | INT UNSIGNED NULL | FOREIGN KEY, INDEX |
| action | Action type (login, login_failed, register, booking_submit, etc.) | VARCHAR(100) | INDEX |
| entity_type | Type of entity affected (user, booking, event, etc.) | VARCHAR(50) | - |
| entity_id | ID of the affected entity | INT UNSIGNED | - |
| details | JSON-formatted action details | TEXT | - |
| ip_address | Client IP address | VARCHAR(45) | - |
| user_agent | Client browser user agent | TEXT | - |
| created_at | Log entry timestamp | TIMESTAMP | INDEX |

#### Table: settings

| Field Name | Description | Type | Key |
|---|---|---|---|
| setting_id | Auto-increment unique setting ID | INT UNSIGNED | PRIMARY KEY |
| key | Setting identifier (site_name, currency, social_instagram, etc.) | VARCHAR(100) UNIQUE | INDEX |
| value | Setting value | TEXT | - |
| type | Value type (string, boolean, etc.) | VARCHAR(50) | - |
| description | Setting description | TEXT | - |
| updated_at | Last update timestamp | TIMESTAMP | - |

#### Database Views (for Reporting)

| View Name | Purpose |
|---|---|
| v_booking_summary | Monthly booking statistics by status with total revenue |
| v_expense_summary | Monthly approved expense totals by category |
| v_merchandise_inventory | Active merchandise with stock status (in_stock, low_stock, out_of_stock) and inventory value |

---

## 7. WORK PLAN

| Week | Phase | Activities |
|---|---|---|
| Week 1-2 | Requirement Analysis & Design | Define user roles and access levels, create ERD with 10 tables, design wireframes for 3 portals, setup project structure |
| Week 3-4 | Foundation & Database Setup | Create database schema, implement authentication (login, register, password reset, rate limiting), configure role-based access, build bootstrap architecture |
| Week 5-6 | Core Module Development | Build Event and Booking modules (CRUD, quotation/invoice workflow, availability calendar), Task module (assignment, FullCalendar integration), Expense module (submission, approval, receipt upload) |
| Week 7-8 | E-Commerce & Portal UI | Build Merchandise module (catalog, cart, checkout), Order management, implement responsive sidebar layout for all 3 portals, design system with Bootstrap Icons |
| Week 9-10 | Notifications, Reports & Integration | Implement real-time notification system across all portals, build Reports dashboard with analytics, add email notification templates, PDF invoice export, CSV data exports |
| Week 11 | Security Hardening & Testing | Fix SQL injection, XSS, CSRF vulnerabilities, enforce API authentication, input validation, system-wide audit and bug fixes |
| Week 12 | Documentation & Deployment | Comprehensive seed data, final testing, project documentation, deployment preparation, and presentation |

---

## 8. CONCLUSION

The Second Plan System provides an effective digital solution for independent bands by transforming traditional manual and unorganized workflows into a structured, centralized management platform. Through its three-portal architecture, the system serves distinct user needs: administrators gain full operational control over events, bookings, finances, merchandise, and team coordination; band members receive clear task assignments, schedule visibility, and expense claim capabilities; and customers enjoy a professional booking experience with quotation-to-invoice workflows, an online merchandise shop, and transparent payment tracking.

The system addresses critical operational gaps identified in the benchmarking analysis of existing Malaysian platforms such as GoLive, Gigsmore, and LOLAsia, none of which provide internal band management tools. By integrating event management, booking workflows with automated quotation and invoice generation, task assignment with calendar visualization, expense tracking with receipt management, merchandise e-commerce with inventory control, and a comprehensive notification system into a single platform, the Second Plan System eliminates the need for scattered tools and manual processes.

The implementation demonstrates strong technical foundations including role-based access control, CSRF protection across all endpoints, SQL injection prevention through parameterized queries, login rate limiting, secure file upload validation, and comprehensive activity logging. The responsive design ensures accessibility across devices, while the modular architecture supports future enhancements such as payment gateway integration, email verification, and multi-language support.

Overall, the Second Plan System empowers independent bands to focus on their creative work while maintaining organized, transparent, and scalable management practices that support professional growth.

---

## 9. REFERENCES

*(Add your references here)*
