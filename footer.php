<!-- ── FOOTER ─────────────────────────────────────────────── -->
<footer class="site-footer">
    <div class="footer-inner">

        <!-- Brand col -->
        <div class="footer-col footer-brand-col">
            <div class="footer-brand">
                <div class="footer-dot"></div>
                WorkForce Manager
            </div>
            <p class="footer-tagline">Connecting skilled workers with people who need them — fast, simple, and reliable.</p>
            <div class="footer-social">
                <a href="#" aria-label="Facebook">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                </a>
                <a href="#" aria-label="Twitter/X">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 4l16 16M4 20L20 4" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                </a>
                <a href="#" aria-label="LinkedIn">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                </a>
            </div>
        </div>

        <!-- Quick links -->
        <div class="footer-col">
            <div class="footer-col-title">Quick Links</div>
            <ul class="footer-links">
                <li><a href="index.php">Browse Workers</a></li>
                <?php if (isLoggedIn()): ?>
                <li><a href="bookings.php">My Bookings</a></li>
                <?php endif; ?>
                <?php if (isAdmin()): ?>
                <li><a href="admin.php">Admin Panel</a></li>
                <li><a href="add_worker.php">Add Worker</a></li>
                <?php endif; ?>
                <?php if (!isLoggedIn()): ?>
                <li><a href="signin.php">Sign In</a></li>
                <li><a href="signup.php">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- For workers -->
        <div class="footer-col">
            <div class="footer-col-title">For Workers</div>
            <ul class="footer-links">
                <li><a href="signup.php">Register as Worker</a></li>
                <li><a href="signin.php">Worker Login</a></li>
                <?php if (isWorker()): ?>
                <li><a href="worker_details.php?id=<?= $_SESSION['uid'] ?? '' ?>">My Profile</a></li>
                <li><a href="bookings.php">My Bookings</a></li>
                <?php endif; ?>
                <li><a href="faq.php">How It Works</a></li>
            </ul>
        </div>

        <!-- Contact -->
        <div class="footer-col">
            <div class="footer-col-title">Contact Us</div>
            <ul class="footer-contact">
                <li>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <a href="mailto:support@workforce.com">support@workforce.com</a>
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.18 2 2 0 0 1 3.6 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.6a16 16 0 0 0 6.06 6.06l.9-.9a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <a href="tel:+8801700000000">+880 170 000 0000</a>
                </li>
                <li>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span>Khulna, Bangladesh</span>
                </li>
            </ul>
        </div>

    </div>

    <!-- Bottom bar -->
    <div class="footer-bottom">
        <span>© <?= date('Y') ?> WorkForce Manager. All rights reserved.</span>
        <div class="footer-bottom-links">
            <a href="privacy.php">Privacy Policy</a>
            <a href="terms.php">Terms of Service</a>
            <a href="faq.php">FAQ</a>
        </div>
    </div>
</footer>