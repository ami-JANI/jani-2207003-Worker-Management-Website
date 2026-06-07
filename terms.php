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
<title>Terms of Service — WorkForce Manager</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{--bg:#0f1117;--surface:#181c27;--surface2:#1e2333;--border:#2a3045;--accent:#4f8ef7;--accent2:#7c5cfc;--text:#e8eaf0;--muted:#8891aa;--danger:#f87171;--success:#4ade80;--warn:#f59e0b;--navbar-h:62px;}
body{background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;min-height:100vh;display:flex;flex-direction:column;}
</style>
<style><?php include "navbar.css"; ?></style>
<style><?php include "footer.css"; ?></style>
<style>
.page{padding-top:calc(var(--navbar-h)+48px);flex:1;}
.page-inner{max-width:780px;margin:0 auto;padding:0 24px 60px;}
.doc-eyebrow{font-size:11.5px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:var(--accent2);margin-bottom:12px;}
.doc-title{font-family:'Syne',sans-serif;font-size:36px;font-weight:800;letter-spacing:-1px;line-height:1.15;margin-bottom:14px;}
.doc-meta{font-size:13px;color:var(--muted);display:flex;gap:20px;flex-wrap:wrap;align-items:center;margin-bottom:36px;}
.doc-meta span{display:flex;align-items:center;gap:6px;}
.doc-meta svg{width:14px;height:14px;}
.back-link{display:inline-flex;align-items:center;gap:6px;color:var(--muted);font-size:13px;text-decoration:none;margin-bottom:32px;transition:color .2s;}
.back-link:hover{color:var(--text);}
.back-link svg{width:14px;height:14px;}
.warn-box{background:rgba(245,158,11,.07);border:1px solid rgba(245,158,11,.2);border-radius:10px;padding:16px 20px;margin-bottom:36px;font-size:14px;color:var(--text);line-height:1.7;}
.warn-box strong{color:var(--warn);}
.toc{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:22px 26px;margin-bottom:48px;}
.toc-title{font-family:'Syne',sans-serif;font-size:12px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--muted);margin-bottom:14px;}
.toc ol{list-style:none;counter-reset:toc;display:flex;flex-direction:column;gap:8px;}
.toc ol li{counter-increment:toc;display:flex;align-items:baseline;gap:10px;font-size:13.5px;}
.toc ol li::before{content:counter(toc,decimal-leading-zero);font-family:'Syne',sans-serif;font-size:11px;font-weight:700;color:var(--accent2);flex-shrink:0;}
.toc ol li a{color:var(--muted);text-decoration:none;transition:color .2s;}
.toc ol li a:hover{color:var(--text);}
.doc-section{margin-bottom:44px;scroll-margin-top:80px;}
.section-num{font-size:11px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--accent2);margin-bottom:6px;}
.section-title{font-family:'Syne',sans-serif;font-size:20px;font-weight:700;margin-bottom:16px;}
.doc-section p{font-size:15px;color:#c4c8d8;line-height:1.8;margin-bottom:14px;}
.doc-section p:last-child{margin-bottom:0;}
.doc-section ul,.doc-section ol.list{padding-left:20px;display:flex;flex-direction:column;gap:10px;margin-bottom:14px;}
.doc-section li{font-size:15px;color:#c4c8d8;line-height:1.7;}
.doc-section li strong{color:var(--text);}
.section-divider{height:1px;background:var(--border);margin:44px 0;}
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

    <div class="doc-eyebrow">Legal</div>
    <h1 class="doc-title">Terms of Service</h1>
    <div class="doc-meta">
        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Last updated: June 2025</span>
        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>15 min read</span>
    </div>

    <div class="warn-box">
        <strong>Please read carefully.</strong> By creating an account or using WorkForce Manager, you agree to these Terms of Service. If you do not agree, please do not use the platform.
    </div>

    <div class="toc">
        <div class="toc-title">Table of Contents</div>
        <ol>
            <li><a href="#t1">Acceptance of Terms</a></li>
            <li><a href="#t2">Description of Service</a></li>
            <li><a href="#t3">User Accounts</a></li>
            <li><a href="#t4">Worker Accounts & Approval</a></li>
            <li><a href="#t5">Booking & Payment Terms</a></li>
            <li><a href="#t6">Ratings & Reviews</a></li>
            <li><a href="#t7">Prohibited Conduct</a></li>
            <li><a href="#t8">Content & Intellectual Property</a></li>
            <li><a href="#t9">Termination</a></li>
            <li><a href="#t10">Disclaimers & Limitation of Liability</a></li>
            <li><a href="#t11">Governing Law</a></li>
            <li><a href="#t12">Changes to Terms</a></li>
        </ol>
    </div>

    <div class="doc-section" id="t1">
        <div class="section-num">01</div>
        <div class="section-title">Acceptance of Terms</div>
        <p>These Terms of Service ("Terms") constitute a legally binding agreement between you ("User," "Worker," or "you") and WorkForce Manager ("we," "our," or "the platform"). By accessing or using WorkForce Manager — including registering an account, browsing workers, or placing a booking — you confirm that you have read, understood, and agreed to these Terms in their entirety.</p>
        <p>If you are using the platform on behalf of an organization, you represent that you have the authority to bind that organization to these Terms.</p>
    </div>

    <div class="section-divider"></div>

    <div class="doc-section" id="t2">
        <div class="section-num">02</div>
        <div class="section-title">Description of Service</div>
        <p>WorkForce Manager is an online marketplace platform that connects individuals seeking skilled labor ("Users") with service providers ("Workers"). The platform provides:</p>
        <ul>
            <li>A searchable directory of approved Workers with profiles, skills, and availability</li>
            <li>A booking system for Users to request Worker services</li>
            <li>A booking lifecycle management system including approval, completion, and dispute handling</li>
            <li>A rating and review system for completed work</li>
            <li>An administrative panel for platform governance</li>
        </ul>
        <p>WorkForce Manager acts solely as an intermediary platform. We are not a party to any service agreement between a User and a Worker, and we do not employ, direct, or supervise any Worker on the platform.</p>
    </div>

    <div class="section-divider"></div>

    <div class="doc-section" id="t3">
        <div class="section-num">03</div>
        <div class="section-title">User Accounts</div>
        <p>To access most features of WorkForce Manager, you must register an account. By registering, you agree to:</p>
        <ul>
            <li><strong>Provide accurate information:</strong> All registration information must be truthful, current, and complete. Providing false information may result in immediate account suspension.</li>
            <li><strong>Maintain account security:</strong> You are responsible for maintaining the confidentiality of your password. Do not share your credentials with anyone.</li>
            <li><strong>One account per person:</strong> You may not create multiple accounts. Duplicate accounts will be removed without notice.</li>
            <li><strong>Notify us of breaches:</strong> If you believe your account has been compromised, contact us immediately at support@workforce.com.</li>
            <li><strong>Accept responsibility:</strong> You are responsible for all activity that occurs under your account.</li>
        </ul>
        <p>User accounts are approved automatically upon registration. Workers require admin approval before their profile becomes visible on the platform.</p>
    </div>

    <div class="section-divider"></div>

    <div class="doc-section" id="t4">
        <div class="section-num">04</div>
        <div class="section-title">Worker Accounts & Approval</div>
        <p>Workers must submit an application through the Sign Up page and await admin approval before their profile is activated. By applying as a Worker, you agree to:</p>
        <ul>
            <li>Provide accurate information about your skills, experience, profession, and hourly rate</li>
            <li>Upload a genuine, professional profile photo if desired</li>
            <li>Respond to booking requests in a timely manner</li>
            <li>Mark jobs as "Done" only when the work has been genuinely completed</li>
            <li>Maintain professional conduct in all interactions with Users</li>
        </ul>
        <p>We reserve the right to reject any Worker application or revoke approval at any time, without obligation to provide a reason. Workers whose profiles are edited must resubmit those edits for admin approval before changes go live.</p>
    </div>

    <div class="section-divider"></div>

    <div class="doc-section" id="t5">
        <div class="section-num">05</div>
        <div class="section-title">Booking & Payment Terms</div>
        <p>WorkForce Manager facilitates the connection between Users and Workers but is <strong>not involved in any financial transactions</strong>. All payment arrangements are made directly between the User and the Worker outside the platform.</p>
        <p>Regarding bookings:</p>
        <ul>
            <li>A booking request does not constitute a contract until the Worker explicitly approves it</li>
            <li>Workers may reject any booking request without providing a reason</li>
            <li>Users may cancel a pending booking request before the Worker has responded</li>
            <li>Once a Worker marks a job as "Done," the User must confirm completion to release the Worker's availability status</li>
            <li>Disputes between Users and Workers are to be resolved directly between the parties. WorkForce Manager offers no mediation or financial recourse</li>
        </ul>
        <p>The hourly rates displayed on Worker profiles are indicative only and may be subject to negotiation between the parties.</p>
    </div>

    <div class="section-divider"></div>

    <div class="doc-section" id="t6">
        <div class="section-num">06</div>
        <div class="section-title">Ratings & Reviews</div>
        <p>Users may submit one rating per completed booking. Ratings must be:</p>
        <ul>
            <li><strong>Honest:</strong> Ratings must reflect a genuine assessment of the service received</li>
            <li><strong>Fair:</strong> Retaliatory or manipulated ratings are prohibited</li>
            <li><strong>Based on completed work only:</strong> Only Users who have completed a booking with a Worker may rate that Worker for that booking</li>
        </ul>
        <p>We reserve the right to remove ratings that we determine, in our sole discretion, to be fraudulent, abusive, or otherwise in violation of these Terms. Workers may not attempt to coerce, incentivize, or manipulate Users into submitting or changing ratings.</p>
    </div>

    <div class="section-divider"></div>

    <div class="doc-section" id="t7">
        <div class="section-num">07</div>
        <div class="section-title">Prohibited Conduct</div>
        <p>You agree not to use WorkForce Manager to:</p>
        <ul>
            <li>Impersonate another person or entity, or misrepresent your identity or qualifications</li>
            <li>Submit false, misleading, or fraudulent information at any point</li>
            <li>Harass, threaten, or discriminate against any other user of the platform</li>
            <li>Attempt to gain unauthorized access to other accounts, the database, or the server</li>
            <li>Upload malicious files, scripts, or content of any kind</li>
            <li>Use automated tools, bots, or scrapers to access or extract platform data</li>
            <li>Create bookings with no genuine intent to use the service</li>
            <li>Circumvent or attempt to circumvent any platform security mechanism</li>
        </ul>
        <p>Violation of any of the above may result in immediate account termination and, where applicable, referral to law enforcement authorities.</p>
    </div>

    <div class="section-divider"></div>

    <div class="doc-section" id="t8">
        <div class="section-num">08</div>
        <div class="section-title">Content & Intellectual Property</div>
        <p>All content on WorkForce Manager — including its design, code, logo, and text — is the intellectual property of WorkForce Manager and may not be copied, reproduced, or used without written permission.</p>
        <p>By uploading a profile photo or any other content to the platform, you grant WorkForce Manager a non-exclusive, royalty-free license to display that content solely for the purpose of operating the platform. You retain full ownership of any content you upload.</p>
    </div>

    <div class="section-divider"></div>

    <div class="doc-section" id="t9">
        <div class="section-num">09</div>
        <div class="section-title">Termination</div>
        <p>Either party may terminate the service relationship at any time:</p>
        <ul>
            <li><strong>By you:</strong> You may delete your account at any time by contacting support or using the delete option in the platform (available for Workers).</li>
            <li><strong>By us:</strong> We may suspend or permanently terminate your account without prior notice if we determine that you have violated these Terms, engaged in harmful conduct, or for any other reason at our sole discretion.</li>
        </ul>
        <p>Upon termination, your profile and associated data may be deleted from the platform. Booking history records may be retained for up to 12 months for audit purposes.</p>
    </div>

    <div class="section-divider"></div>

    <div class="doc-section" id="t10">
        <div class="section-num">10</div>
        <div class="section-title">Disclaimers & Limitation of Liability</div>
        <p>WorkForce Manager is provided on an "as is" and "as available" basis. We make no warranties, express or implied, regarding the reliability, availability, or fitness of the platform for any particular purpose.</p>
        <p>We are not liable for:</p>
        <ul>
            <li>The quality, safety, or legality of any services performed by Workers</li>
            <li>Any disputes, damages, or losses arising from agreements between Users and Workers</li>
            <li>Any loss of data, revenue, or business resulting from platform downtime or errors</li>
            <li>Any actions taken by third parties using the platform</li>
        </ul>
        <p>To the maximum extent permitted by applicable law, our total liability for any claim arising from the use of this platform shall not exceed the amount you have paid us in the 30 days preceding the claim (which, as this is a free platform, is zero).</p>
    </div>

    <div class="section-divider"></div>

    <div class="doc-section" id="t11">
        <div class="section-num">11</div>
        <div class="section-title">Governing Law</div>
        <p>These Terms are governed by and construed in accordance with the laws of Bangladesh. Any disputes arising from or related to these Terms or your use of the platform shall be subject to the exclusive jurisdiction of the courts of Khulna, Bangladesh.</p>
    </div>

    <div class="section-divider"></div>

    <div class="doc-section" id="t12">
        <div class="section-num">12</div>
        <div class="section-title">Changes to Terms</div>
        <p>We reserve the right to modify these Terms at any time. Material changes will be communicated via the in-app notification system at least 7 days before taking effect. Your continued use of the platform after the effective date of any changes constitutes your acceptance of the revised Terms.</p>
        <p>For questions about these Terms, contact us at <a href="mailto:support@workforce.com" style="color:var(--accent2)">support@workforce.com</a>.</p>
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