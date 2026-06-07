<?php
include "db_connect.php";
include "auth.php";
$unread = isLoggedIn() ? getUnreadCount($conn) : 0;
$su     = sessionUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Privacy Policy — WorkForce Manager</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{--bg:#0f1117;--surface:#181c27;--surface2:#1e2333;--border:#2a3045;--accent:#4f8ef7;--accent2:#7c5cfc;--text:#e8eaf0;--muted:#8891aa;--danger:#f87171;--success:#4ade80;--navbar-h:62px;}
body{background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;min-height:100vh;display:flex;flex-direction:column;}
</style>
<style><?php include "navbar.css"; ?></style>
<style><?php include "footer.css"; ?></style>
<style>
.page{padding-top:calc(var(--navbar-h)+48px);padding-bottom:0;flex:1;}
.page-inner{max-width:780px;margin:0 auto;padding:0 24px 60px;}

/* Hero */
.doc-hero{margin-bottom:48px;}
.doc-eyebrow{font-size:11.5px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:var(--accent);margin-bottom:12px;}
.doc-title{font-family:'Syne',sans-serif;font-size:36px;font-weight:800;letter-spacing:-1px;line-height:1.15;margin-bottom:14px;}
.doc-meta{font-size:13px;color:var(--muted);display:flex;gap:20px;flex-wrap:wrap;align-items:center;}
.doc-meta span{display:flex;align-items:center;gap:6px;}
.doc-meta svg{width:14px;height:14px;}

/* TOC */
.toc{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:22px 26px;margin-bottom:48px;}
.toc-title{font-family:'Syne',sans-serif;font-size:12px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--muted);margin-bottom:14px;}
.toc ol{list-style:none;counter-reset:toc-counter;display:flex;flex-direction:column;gap:8px;}
.toc ol li{counter-increment:toc-counter;display:flex;align-items:baseline;gap:10px;font-size:13.5px;}
.toc ol li::before{content:counter(toc-counter,decimal-leading-zero);font-family:'Syne',sans-serif;font-size:11px;font-weight:700;color:var(--accent);flex-shrink:0;}
.toc ol li a{color:var(--muted);text-decoration:none;transition:color .2s;}
.toc ol li a:hover{color:var(--text);}

/* Sections */
.doc-section{margin-bottom:44px;scroll-margin-top:80px;}
.section-num{font-size:11px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--accent);margin-bottom:6px;}
.section-title{font-family:'Syne',sans-serif;font-size:20px;font-weight:700;margin-bottom:16px;color:var(--text);}
.doc-section p{font-size:15px;color:#c4c8d8;line-height:1.8;margin-bottom:14px;}
.doc-section p:last-child{margin-bottom:0;}
.doc-section ul,.doc-section ol{padding-left:20px;display:flex;flex-direction:column;gap:10px;margin-bottom:14px;}
.doc-section li{font-size:15px;color:#c4c8d8;line-height:1.7;}
.doc-section li strong{color:var(--text);}

.highlight-box{background:rgba(79,142,247,.07);border:1px solid rgba(79,142,247,.18);border-radius:10px;padding:16px 20px;margin:18px 0;font-size:14px;color:var(--text);line-height:1.7;}
.highlight-box strong{color:var(--accent);}

.section-divider{height:1px;background:var(--border);margin:44px 0;}

/* Back link */
.back-link{display:inline-flex;align-items:center;gap:6px;color:var(--muted);font-size:13px;text-decoration:none;margin-bottom:32px;transition:color .2s;}
.back-link:hover{color:var(--text);}
.back-link svg{width:14px;height:14px;}

@media(max-width:600px){.doc-title{font-size:26px;}.doc-meta{gap:12px;}}
</style>
</head>
<body>
<?php include "navbar.php"; ?>

<div class="page">
<div class="page-inner">

    <a href="index.php" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Back to Home
    </a>

    <div class="doc-hero">
        <div class="doc-eyebrow">Legal</div>
        <h1 class="doc-title">Privacy Policy</h1>
        <div class="doc-meta">
            <span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Last updated: June 2025
            </span>
            <span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                10 min read
            </span>
        </div>
    </div>

    <div class="highlight-box">
        <strong>Summary:</strong> WorkForce Manager is committed to protecting your personal data. We collect only what we need, never sell your information, and give you full control over your data. This policy explains exactly what we collect, why, and how.
    </div>

    <!-- TOC -->
    <div class="toc">
        <div class="toc-title">Table of Contents</div>
        <ol>
            <li><a href="#s1">Information We Collect</a></li>
            <li><a href="#s2">How We Use Your Information</a></li>
            <li><a href="#s3">Information Sharing</a></li>
            <li><a href="#s4">Data Storage & Security</a></li>
            <li><a href="#s5">Cookies & Tracking</a></li>
            <li><a href="#s6">Your Rights & Choices</a></li>
            <li><a href="#s7">Children's Privacy</a></li>
            <li><a href="#s8">Changes to This Policy</a></li>
            <li><a href="#s9">Contact Us</a></li>
        </ol>
    </div>

    <!-- Sections -->
    <div class="doc-section" id="s1">
        <div class="section-num">01</div>
        <div class="section-title">Information We Collect</div>
        <p>We collect information you provide directly to us when you create an account, use our services, or communicate with us. The type of information depends on your role:</p>
        <ul>
            <li><strong>Users:</strong> Name, email address, phone number, location, and password (stored as an encrypted hash).</li>
            <li><strong>Workers:</strong> Name, email address, phone number, location, profession, skills, years of experience, hourly rate, availability status, profile photo, and password hash.</li>
            <li><strong>All accounts:</strong> Booking history, ratings submitted or received, and notification records.</li>
        </ul>
        <p>We also collect limited technical data automatically when you use the platform, such as your IP address, browser type, and pages visited. This data is used solely for security and performance monitoring.</p>
    </div>

    <div class="section-divider"></div>

    <div class="doc-section" id="s2">
        <div class="section-num">02</div>
        <div class="section-title">How We Use Your Information</div>
        <p>We use your information for the following purposes only:</p>
        <ul>
            <li><strong>Account management:</strong> Creating and maintaining your account, verifying your identity when you sign in.</li>
            <li><strong>Service delivery:</strong> Connecting users with workers, managing bookings and their lifecycle, sending booking status notifications.</li>
            <li><strong>Platform integrity:</strong> Reviewing worker applications, approving or rejecting profile edits, moderating content.</li>
            <li><strong>Communication:</strong> Sending in-app notifications about booking updates, approvals, and admin broadcasts.</li>
            <li><strong>Improvement:</strong> Analyzing aggregate usage patterns (never individual data) to improve platform features.</li>
        </ul>
        <p>We will never use your data for advertising, profiling, or any purpose not listed above without your explicit consent.</p>
    </div>

    <div class="section-divider"></div>

    <div class="doc-section" id="s3">
        <div class="section-num">03</div>
        <div class="section-title">Information Sharing</div>
        <p>We do not sell, rent, or trade your personal information to any third party. Period.</p>
        <p>Information is shared within the platform only as necessary for the service to work:</p>
        <ul>
            <li>When a user books a worker, the user's name is visible to that worker for the duration of the booking.</li>
            <li>Worker profile information (name, profession, photo, rating, location) is visible to all visitors of the platform.</li>
            <li>Admin users can view all accounts and bookings for platform management purposes only.</li>
        </ul>
        <p>We may disclose your information if required by law, court order, or government authority. We will notify you of such requests to the extent permitted by law.</p>
    </div>

    <div class="section-divider"></div>

    <div class="doc-section" id="s4">
        <div class="section-num">04</div>
        <div class="section-title">Data Storage & Security</div>
        <p>Your data is stored in a secured MySQL database hosted locally or on a protected server. We implement the following security measures:</p>
        <ul>
            <li><strong>Password hashing:</strong> All passwords are hashed using PHP's bcrypt algorithm. We never store plain-text passwords.</li>
            <li><strong>Prepared statements:</strong> All database queries use parameterized prepared statements to prevent SQL injection.</li>
            <li><strong>Session management:</strong> User sessions are managed server-side with PHP's native session system.</li>
            <li><strong>File uploads:</strong> Uploaded photos are stored with unique filenames and validated by MIME type before acceptance.</li>
        </ul>
        <p>While we take security seriously, no system is 100% secure. We encourage you to use a strong, unique password and to log out when using shared devices.</p>
    </div>

    <div class="section-divider"></div>

    <div class="doc-section" id="s5">
        <div class="section-num">05</div>
        <div class="section-title">Cookies & Tracking</div>
        <p>We use only a single, essential session cookie to keep you logged in during your visit. This cookie is:</p>
        <ul>
            <li>Session-based (deleted when you close your browser)</li>
            <li>Never used for tracking or advertising</li>
            <li>Not shared with any third party</li>
        </ul>
        <p>We do not use analytics cookies, advertising cookies, or any third-party tracking scripts. We use Google Fonts for typography, which may set its own cookies subject to Google's privacy policy.</p>
    </div>

    <div class="section-divider"></div>

    <div class="doc-section" id="s6">
        <div class="section-num">06</div>
        <div class="section-title">Your Rights & Choices</div>
        <p>You have the following rights regarding your personal data:</p>
        <ul>
            <li><strong>Access:</strong> You can view all your profile information by visiting your account page.</li>
            <li><strong>Correction:</strong> Workers can submit profile edit requests; users can update their details at any time.</li>
            <li><strong>Deletion:</strong> You may request deletion of your account by contacting us. Workers can delete their own profiles from within the platform.</li>
            <li><strong>Portability:</strong> Contact us to receive a copy of your personal data in a readable format.</li>
        </ul>
        <p>To exercise any of these rights, contact us at <a href="mailto:support@workforce.com" style="color:var(--accent)">support@workforce.com</a>.</p>
    </div>

    <div class="section-divider"></div>

    <div class="doc-section" id="s7">
        <div class="section-num">07</div>
        <div class="section-title">Children's Privacy</div>
        <p>WorkForce Manager is not intended for use by anyone under the age of 16. We do not knowingly collect personal information from children. If you believe we have inadvertently collected information from a minor, please contact us immediately and we will delete it promptly.</p>
    </div>

    <div class="section-divider"></div>

    <div class="doc-section" id="s8">
        <div class="section-num">08</div>
        <div class="section-title">Changes to This Policy</div>
        <p>We may update this Privacy Policy from time to time. When we do, we will update the "Last updated" date at the top of this page and, where appropriate, notify users via the in-app notification system. Your continued use of the platform after changes constitutes your acceptance of the revised policy.</p>
    </div>

    <div class="section-divider"></div>

    <div class="doc-section" id="s9">
        <div class="section-num">09</div>
        <div class="section-title">Contact Us</div>
        <p>If you have any questions, concerns, or requests regarding this Privacy Policy or your personal data, please reach out to us:</p>
        <ul>
            <li><strong>Email:</strong> <a href="mailto:support@workforce.com" style="color:var(--accent)">support@workforce.com</a></li>
            <li><strong>Phone:</strong> +880 170 000 0000</li>
            <li><strong>Address:</strong> WorkForce Manager, Khulna, Bangladesh</li>
        </ul>
    </div>

</div>
</div>

<?php include "footer.php"; ?>

<script>
function toggleNotifPanel(){document.getElementById('notifPanel')?.classList.toggle('open');document.getElementById('notifOverlay')?.classList.toggle('open');}
function closeNotifPanel(){document.getElementById('notifPanel')?.classList.remove('open');document.getElementById('notifOverlay')?.classList.remove('open');}
function toggleUserMenu(){document.getElementById('userDropdown')?.classList.toggle('open');}
document.addEventListener('click',e=>{const m=document.getElementById('userMenu');if(m&&!m.contains(e.target))document.getElementById('userDropdown')?.classList.remove('open');});
function markAllRead(){fetch('mark_read.php');return true;}
</script>
</body>
</html>