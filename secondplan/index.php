<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SECONDPLAN - Professional Band Management System</title>
    <link rel="stylesheet" href="main.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <span class="brand-icon">⚡</span>
                <span class="brand-text">SECONDPLAN</span>
            </div>
            <div class="nav-menu" id="navMenu">
                <a href="#home" class="nav-link">Home</a>
                <a href="#events" class="nav-link">Events</a>
                <a href="#services" class="nav-link">Services</a>
                <a href="#merchandise" class="nav-link">Merchandise</a>
                <a href="#contact" class="nav-link">Contact</a>
            </div>
            <div class="nav-actions">
                <a href="auth/login.php" class="btn btn-secondary">Login</a>
                <a href="auth/register.php" class="btn btn-primary">Sign Up</a>
            </div>
            <button class="nav-toggle" id="navToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-overlay"></div>
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Professional Band Management Made Simple</h1>
                <p class="hero-subtitle">
                    Book events, manage merchandise, track expenses, and coordinate your team - all in one powerful platform
                </p>
                <div class="hero-actions">
                    <a href="auth/register.php" class="btn btn-primary btn-lg">Get Started Free</a>
                    <a href="#services" class="btn btn-outline btn-lg">Learn More</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="services">
        <div class="container">
            <div class="section-header">
                <h2>Everything You Need to Manage Your Band</h2>
                <p>Powerful features designed for professional musicians and event organizers</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📅</div>
                    <h3>Event Management</h3>
                    <p>Schedule performances, manage bookings, and coordinate with clients seamlessly</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">👥</div>
                    <h3>Team Coordination</h3>
                    <p>Assign tasks, track progress, and keep your band members aligned</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">💰</div>
                    <h3>Expense Tracking</h3>
                    <p>Monitor costs, approve expenses, and maintain financial clarity</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🛍️</div>
                    <h3>Merchandise Store</h3>
                    <p>Sell your merchandise online with built-in inventory management</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Analytics & Reports</h3>
                    <p>Get insights into bookings, revenue, and performance metrics</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h3>Secure & Reliable</h3>
                    <p>Enterprise-grade security to protect your data and transactions</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Events Section -->
    <section class="events" id="events">
        <div class="container">
            <div class="section-header">
                <h2>Upcoming Events</h2>
                <p>Join us at our upcoming performances</p>
            </div>
            
            <div class="events-grid" id="eventsGrid">
                <div class="loading-message">Loading events...</div>
            </div>
            
            <div class="section-footer">
                <a href="user/events.php" class="btn btn-primary">View All Events</a>
            </div>
        </div>
    </section>

    <!-- Merchandise Section -->
    <section class="merchandise" id="merchandise">
        <div class="container">
            <div class="section-header">
                <h2>Official Merchandise</h2>
                <p>Get your exclusive band merchandise</p>
            </div>
            
            <div class="merch-grid" id="merchGrid">
                <div class="loading-message">Loading merchandise...</div>
            </div>
            
            <div class="section-footer">
                <a href="user/merchandise.php" class="btn btn-primary">Shop Now</a>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Streamline Your Band Management?</h2>
                <p>Join hundreds of bands already using SECONDPLAN to grow their business</p>
                <a href="auth/register.php" class="btn btn-primary btn-lg">Start Your Free Trial</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h4>SECONDPLAN</h4>
                    <p>Professional band management system for modern musicians</p>
                </div>
                
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="#home">Home</a></li>
                        <li><a href="#events">Events</a></li>
                        <li><a href="#merchandise">Merchandise</a></li>
                        <li><a href="auth/login.php">Login</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Contact Us</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>Connect</h4>
                    <div class="social-links">
                        <a href="#" class="social-link">📘</a>
                        <a href="#" class="social-link">📷</a>
                        <a href="#" class="social-link">🐦</a>
                        <a href="#" class="social-link">▶️</a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2026 SECONDPLAN. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="main.js"></script>
</body>
</html>