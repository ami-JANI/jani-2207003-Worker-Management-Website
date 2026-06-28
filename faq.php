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
<title>FAQ — WorkForce Manager</title>
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
.page{padding-top:40px;flex:1;}
.page-inner{max-width:800px;margin:0 auto;padding:0 24px 60px;}

.back-link{display:inline-flex;align-items:center;gap:6px;color:var(--muted);font-size:13px;text-decoration:none;margin-bottom:32px;transition:color .2s;}
.back-link:hover{color:var(--text);}
.back-link svg{width:14px;height:14px;}

/* Hero */
.faq-hero{text-align:center;margin-bottom:48px;}
.doc-eyebrow{font-size:11.5px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:var(--success);margin-bottom:12px;}
.doc-title{font-family:'Syne',sans-serif;font-size:36px;font-weight:800;letter-spacing:-1px;line-height:1.15;margin-bottom:14px;}
.faq-hero p{font-size:15px;color:var(--muted);line-height:1.7;max-width:520px;margin:0 auto 28px;}

/* Search bar */
.faq-search-wrap{position:relative;max-width:480px;margin:0 auto;}
.faq-search-wrap svg{position:absolute;left:14px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:var(--muted);pointer-events:none;}
#faqSearch{width:100%;background:var(--surface);border:1px solid var(--border);border-radius:10px;color:var(--text);padding:12px 16px 12px 42px;font-size:14px;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .2s,box-shadow .2s;}
#faqSearch:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(79,142,247,.12);}
#faqSearch::placeholder{color:#55607a;}

/* Category tabs */
.cat-tabs{display:flex;gap:8px;flex-wrap:wrap;justify-content:center;margin-bottom:36px;}
.cat-tab{padding:7px 18px;border-radius:20px;border:1px solid var(--border);background:var(--surface2);color:var(--muted);font-size:13px;font-weight:600;cursor:pointer;transition:all .2s;font-family:'DM Sans',sans-serif;}
.cat-tab:hover{color:var(--text);border-color:var(--muted);}
.cat-tab.active{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;border-color:transparent;}

/* Category label */
.cat-label{font-family:'Syne',sans-serif;font-size:12px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;color:var(--muted);margin:32px 0 16px;display:flex;align-items:center;gap:10px;}
.cat-label::after{content:'';flex:1;height:1px;background:var(--border);}

/* FAQ accordion */
.faq-item{background:var(--surface);border:1px solid var(--border);border-radius:12px;margin-bottom:10px;overflow:hidden;transition:border-color .2s;}
.faq-item:hover{border-color:#3a4260;}
.faq-item.open{border-color:rgba(79,142,247,.3);}

.faq-question{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:18px 20px;cursor:pointer;user-select:none;}
.faq-question-text{font-size:15px;font-weight:600;color:var(--text);line-height:1.4;flex:1;}
.faq-icon{flex-shrink:0;width:24px;height:24px;border-radius:6px;background:var(--surface2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;transition:background .2s,transform .3s;}
.faq-icon svg{width:12px;height:12px;color:var(--muted);transition:transform .3s;}
.faq-item.open .faq-icon{background:rgba(79,142,247,.15);border-color:rgba(79,142,247,.3);}
.faq-item.open .faq-icon svg{transform:rotate(180deg);color:var(--accent);}

.faq-answer{max-height:0;overflow:hidden;transition:max-height .35s ease, padding .35s ease;}
.faq-answer-inner{padding:0 20px 18px;font-size:14.5px;color:#c4c8d8;line-height:1.8;}
.faq-item.open .faq-answer{max-height:600px;}
.faq-answer-inner a{color:var(--accent);text-decoration:none;}
.faq-answer-inner a:hover{text-decoration:underline;}
.faq-answer-inner ul{padding-left:18px;margin-top:10px;display:flex;flex-direction:column;gap:6px;}
.faq-answer-inner li{line-height:1.65;}

/* No results */
.no-results{text-align:center;padding:48px 20px;color:var(--muted);display:none;}
.no-results svg{width:40px;height:40px;opacity:.3;margin-bottom:12px;}
.no-results p{font-size:14px;}

/* Contact CTA */
.faq-cta{background:linear-gradient(135deg,rgba(79,142,247,.1),rgba(124,92,252,.1));border:1px solid rgba(79,142,247,.2);border-radius:16px;padding:32px;text-align:center;margin-top:48px;}
.faq-cta h3{font-family:'Syne',sans-serif;font-size:20px;font-weight:700;margin-bottom:8px;}
.faq-cta p{font-size:14px;color:var(--muted);margin-bottom:20px;}
.faq-cta .cta-btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;}
.btn-cta{display:inline-flex;align-items:center;gap:7px;padding:10px 22px;border-radius:9px;font-size:14px;font-weight:600;font-family:'DM Sans',sans-serif;text-decoration:none;transition:opacity .2s;}
.btn-cta.primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;}
.btn-cta.primary:hover{opacity:.88;}
.btn-cta.ghost{background:var(--surface);border:1px solid var(--border);color:var(--muted);}
.btn-cta.ghost:hover{color:var(--text);border-color:var(--muted);}
.btn-cta svg{width:15px;height:15px;}

@media(max-width:600px){.doc-title{font-size:26px;}.cat-tabs{gap:6px;}.faq-cta{padding:24px 18px;}.cta-btns{flex-direction:column;}}
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

    <div class="faq-hero">
        <div class="doc-eyebrow">Support</div>
        <h1 class="doc-title">Frequently Asked Questions</h1>
        <p>Everything you need to know about WorkForce Manager. Can't find your answer? Contact us directly.</p>
        <div class="faq-search-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="faqSearch" placeholder="Search questions..." oninput="filterFAQ(this.value)">
        </div>
    </div>

    <!-- Category tabs -->
    <div class="cat-tabs">
        <button class="cat-tab active" onclick="filterCat('all',this)">All</button>
        <button class="cat-tab" onclick="filterCat('general',this)">General</button>
        <button class="cat-tab" onclick="filterCat('users',this)">For Users</button>
        <button class="cat-tab" onclick="filterCat('workers',this)">For Workers</button>
        <button class="cat-tab" onclick="filterCat('bookings',this)">Bookings</button>
        <button class="cat-tab" onclick="filterCat('account',this)">Account</button>
    </div>

    <!-- FAQ items -->
    <div id="faqList">

        <!-- GENERAL -->
        <div class="cat-label" data-cat="general">General</div>

        <div class="faq-item" data-cat="general" data-q="what is workforce manager">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span class="faq-question-text">What is WorkForce Manager?</span>
                <div class="faq-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></div>
            </div>
            <div class="faq-answer"><div class="faq-answer-inner">
                WorkForce Manager is an online platform that connects people who need skilled labor — such as electricians, plumbers, carpenters, and other tradespeople — with verified local workers. Users can browse worker profiles, check availability, book a worker, and rate the service after completion.
            </div></div>
        </div>

        <div class="faq-item" data-cat="general" data-q="is it free to use workforce manager">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span class="faq-question-text">Is WorkForce Manager free to use?</span>
                <div class="faq-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></div>
            </div>
            <div class="faq-answer"><div class="faq-answer-inner">
                Yes, WorkForce Manager is completely free to use for both Users and Workers. There are no subscription fees, no commission charges, and no hidden costs. Payment for services is arranged directly between the User and Worker outside the platform.
            </div></div>
        </div>

        <div class="faq-item" data-cat="general" data-q="what areas does workforce manager cover">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span class="faq-question-text">What areas does WorkForce Manager cover?</span>
                <div class="faq-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></div>
            </div>
            <div class="faq-answer"><div class="faq-answer-inner">
                WorkForce Manager currently operates in Khulna and surrounding areas of Bangladesh. We are actively expanding to other divisions. You can filter workers by location on the browse page to find someone near you.
            </div></div>
        </div>

        <!-- USERS -->
        <div class="cat-label" data-cat="users">For Users</div>

        <div class="faq-item" data-cat="users" data-q="how do i find a worker book">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span class="faq-question-text">How do I find and book a worker?</span>
                <div class="faq-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></div>
            </div>
            <div class="faq-answer"><div class="faq-answer-inner">
                It's simple:
                <ul>
                    <li><strong>Step 1:</strong> Create a free User account or sign in.</li>
                    <li><strong>Step 2:</strong> Browse the worker directory. Use filters to narrow by profession, location, rating, or availability.</li>
                    <li><strong>Step 3:</strong> Click on a worker's card to view their full profile.</li>
                    <li><strong>Step 4:</strong> If the worker is available, click the <strong>"Book Now"</strong> button.</li>
                    <li><strong>Step 5:</strong> Wait for the worker to approve your request — you'll get a notification when they do.</li>
                </ul>
            </div></div>
        </div>

        <div class="faq-item" data-cat="users" data-q="can i cancel a booking request">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span class="faq-question-text">Can I cancel a booking request?</span>
                <div class="faq-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></div>
            </div>
            <div class="faq-answer"><div class="faq-answer-inner">
                Yes. If your booking is still in "Awaiting Worker" status (the worker hasn't responded yet), you can cancel it from your Bookings page by clicking <strong>"Cancel Request"</strong>. Once a worker has approved your booking, you cannot cancel it through the platform — contact the worker directly to arrange a cancellation.
            </div></div>
        </div>

        <div class="faq-item" data-cat="users" data-q="how do i confirm job is done complete">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span class="faq-question-text">How do I confirm a job is complete?</span>
                <div class="faq-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></div>
            </div>
            <div class="faq-answer"><div class="faq-answer-inner">
                When a worker marks the job as done, you'll receive a notification. Go to your <strong>Bookings page</strong>, find the booking under Active, and click <strong>"Confirm Completion."</strong> Once confirmed, the worker's status will return to Available, and you'll be able to leave a rating for the job.
            </div></div>
        </div>

        <div class="faq-item" data-cat="users" data-q="how does rating work review worker">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span class="faq-question-text">How do I rate a worker?</span>
                <div class="faq-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></div>
            </div>
            <div class="faq-answer"><div class="faq-answer-inner">
                After you confirm a job as complete, a star rating widget will appear under that booking in your <strong>Previous Bookings</strong> section. Select 1–5 stars and click Submit. Each booking can only be rated once, but you can rate the same worker again on a different booking.
            </div></div>
        </div>

        <!-- WORKERS -->
        <div class="cat-label" data-cat="workers">For Workers</div>

        <div class="faq-item" data-cat="workers" data-q="how do i register sign up as a worker">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span class="faq-question-text">How do I register as a worker?</span>
                <div class="faq-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></div>
            </div>
            <div class="faq-answer"><div class="faq-answer-inner">
                Go to the <a href="signup.php">Sign Up</a> page and select the <strong>"Worker"</strong> option. Fill in your details including your profession, skills, experience, hourly rate, and optionally a profile photo. Submit your application and wait for an admin to review and approve it. You'll receive a notification once approved.
            </div></div>
        </div>

        <div class="faq-item" data-cat="workers" data-q="how long does worker approval take">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span class="faq-question-text">How long does approval take?</span>
                <div class="faq-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></div>
            </div>
            <div class="faq-answer"><div class="faq-answer-inner">
                Approval is done manually by our admin team and typically takes 24–48 hours. You'll receive an in-app notification as soon as your application is approved or rejected. You cannot sign in or appear on the platform until approval is granted.
            </div></div>
        </div>

        <div class="faq-item" data-cat="workers" data-q="how do i approve or reject a booking request">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span class="faq-question-text">How do I accept or decline a booking request?</span>
                <div class="faq-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></div>
            </div>
            <div class="faq-answer"><div class="faq-answer-inner">
                When a user books you, you'll receive a notification. Go to your <strong>Bookings page</strong> and find the booking under Active. Click <strong>"Approve"</strong> to accept or <strong>"Reject"</strong> to decline. The user will be notified of your decision automatically.
            </div></div>
        </div>

        <div class="faq-item" data-cat="workers" data-q="how do i update edit my profile">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span class="faq-question-text">How do I update my profile?</span>
                <div class="faq-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></div>
            </div>
            <div class="faq-answer"><div class="faq-answer-inner">
                Go to your profile page and click <strong>"Edit My Profile."</strong> Make your changes and click <strong>"Submit for Approval."</strong> Your edits will be reviewed by an admin before going live on your public profile. This usually takes 24–48 hours. Your current profile remains visible until changes are approved.
            </div></div>
        </div>

        <div class="faq-item" data-cat="workers" data-q="what happens to my availability when booked">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span class="faq-question-text">What happens to my availability status during a job?</span>
                <div class="faq-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></div>
            </div>
            <div class="faq-answer"><div class="faq-answer-inner">
                When you approve a booking, your status automatically changes to <strong>"Busy"</strong> so you won't receive new booking requests. Once the user confirms the job is complete, your status automatically reverts to <strong>"Available"</strong>. You can also manually update your availability from your profile edit page at any time.
            </div></div>
        </div>

        <!-- BOOKINGS -->
        <div class="cat-label" data-cat="bookings">Bookings</div>

        <div class="faq-item" data-cat="bookings" data-q="where can i see my bookings history">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span class="faq-question-text">Where can I see my bookings?</span>
                <div class="faq-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></div>
            </div>
            <div class="faq-answer"><div class="faq-answer-inner">
                Click <strong>"Bookings"</strong> in the top navigation bar. The page is divided into two sections: <strong>Active</strong> (pending, confirmed, and awaiting completion) and <strong>Previous</strong> (completed and cancelled bookings). Both Users and Workers see their own relevant bookings.
            </div></div>
        </div>

        <div class="faq-item" data-cat="bookings" data-q="booking status meaning what does it mean pending confirmed">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span class="faq-question-text">What do the different booking statuses mean?</span>
                <div class="faq-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></div>
            </div>
            <div class="faq-answer"><div class="faq-answer-inner">
                <ul>
                    <li><strong>Awaiting Worker</strong> — Your request has been sent; the worker hasn't responded yet.</li>
                    <li><strong>In Progress</strong> — The worker approved your booking. Work is underway.</li>
                    <li><strong>Awaiting Your Approval</strong> — The worker has marked the job done. You need to confirm completion.</li>
                    <li><strong>Completed</strong> — Both parties confirmed the job is done. You can now leave a rating.</li>
                    <li><strong>Cancelled</strong> — The booking was cancelled by either party before completion.</li>
                </ul>
            </div></div>
        </div>

        <div class="faq-item" data-cat="bookings" data-q="can i book the same worker again second time">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span class="faq-question-text">Can I book the same worker more than once?</span>
                <div class="faq-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></div>
            </div>
            <div class="faq-answer"><div class="faq-answer-inner">
                Yes! Once a previous job with a worker is marked as Completed or Cancelled, you're free to book them again. The platform prevents duplicate active bookings with the same worker — you'll see an "Already Booked" indicator if you try to book someone while you already have an ongoing request with them.
            </div></div>
        </div>

        <!-- ACCOUNT -->
        <div class="cat-label" data-cat="account">Account</div>

        <div class="faq-item" data-cat="account" data-q="forgot password reset lost">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span class="faq-question-text">I forgot my password. How do I reset it?</span>
                <div class="faq-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></div>
            </div>
            <div class="faq-answer"><div class="faq-answer-inner">
                Password reset via email is coming soon. For now, please contact us at <a href="mailto:support@workforce.com">support@workforce.com</a> with your registered email address and we'll assist you in regaining access to your account securely.
            </div></div>
        </div>

        <div class="faq-item" data-cat="account" data-q="how do i delete my account remove">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span class="faq-question-text">How do I delete my account?</span>
                <div class="faq-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></div>
            </div>
            <div class="faq-answer"><div class="faq-answer-inner">
                Workers can delete their own profiles from their profile page. For User account deletion, please contact us at <a href="mailto:support@workforce.com">support@workforce.com</a> with your account email and a deletion request. We will process it within 7 business days.
            </div></div>
        </div>

        <div class="faq-item" data-cat="account" data-q="notifications not working how to receive">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span class="faq-question-text">I'm not receiving notifications. What should I do?</span>
                <div class="faq-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></div>
            </div>
            <div class="faq-answer"><div class="faq-answer-inner">
                Notifications are in-app only (no email or SMS yet). Click the bell icon 🔔 in the top navigation bar to view all your notifications. Make sure you are signed in — notifications are only visible to logged-in users. If the bell isn't showing up, try refreshing the page or signing out and back in.
            </div></div>
        </div>

        <div class="faq-item" data-cat="account" data-q="what roles are there user worker admin difference">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span class="faq-question-text">What are the different account types?</span>
                <div class="faq-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></div>
            </div>
            <div class="faq-answer"><div class="faq-answer-inner">
                <ul>
                    <li><strong>User:</strong> Can browse workers, place bookings, confirm completions, and leave ratings. No approval required.</li>
                    <li><strong>Worker:</strong> Can create a profile, receive and manage bookings, and mark jobs as complete. Requires admin approval before going live.</li>
                    <li><strong>Admin:</strong> Can approve or reject worker applications and profile edits, manage all users and workers, and broadcast notifications. Admin accounts are predefined and not available for self-registration.</li>
                </ul>
            </div></div>
        </div>

    </div><!-- end #faqList -->

    <div class="no-results" id="noResults">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <p>No questions match your search. Try different keywords.</p>
    </div>

    <!-- CTA -->
    <div class="faq-cta">
        <h3>Still have questions?</h3>
        <p>Our support team is happy to help you with anything not covered above.</p>
        <div class="cta-btns">
            <a href="mailto:support@workforce.com" class="btn-cta primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Email Support
            </a>
            <a href="index.php" class="btn-cta ghost">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Back to Home
            </a>
        </div>
    </div>

</div>
</div>

<?php include "footer.php"; ?>

<script>
// Accordion toggle
function toggleFAQ(el) {
    const item = el.closest('.faq-item');
    const isOpen = item.classList.contains('open');
    document.querySelectorAll('.faq-item.open').forEach(i => i.classList.remove('open'));
    if (!isOpen) item.classList.add('open');
}

// Category filter
let currentCat = 'all';
function filterCat(cat, btn) {
    currentCat = cat;
    document.querySelectorAll('.cat-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    filterFAQ(document.getElementById('faqSearch').value);
}

// Search filter
function filterFAQ(query) {
    const q = query.toLowerCase().trim();
    let visible = 0;

    document.querySelectorAll('.faq-item').forEach(item => {
        const cat  = item.dataset.cat;
        const text = item.dataset.q + ' ' + item.querySelector('.faq-question-text').textContent.toLowerCase()
                   + ' ' + item.querySelector('.faq-answer-inner').textContent.toLowerCase();
        const matchCat    = currentCat === 'all' || cat === currentCat;
        const matchSearch = !q || text.includes(q);
        const show = matchCat && matchSearch;
        item.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    // Show/hide category labels
    document.querySelectorAll('.cat-label').forEach(label => {
        const cat = label.dataset.cat;
        const hasVisible = [...document.querySelectorAll(`.faq-item[data-cat="${cat}"]`)]
            .some(i => i.style.display !== 'none');
        label.style.display = (currentCat === 'all' || currentCat === cat) && hasVisible ? '' : 'none';
    });

    document.getElementById('noResults').style.display = visible === 0 ? 'block' : 'none';
}

// Navbar JS
function toggleNotifPanel(){document.getElementById('notifPanel')?.classList.toggle('open');document.getElementById('notifOverlay')?.classList.toggle('open');}
function closeNotifPanel(){document.getElementById('notifPanel')?.classList.remove('open');document.getElementById('notifOverlay')?.classList.remove('open');}
function toggleUserMenu(){document.getElementById('userDropdown')?.classList.toggle('open');}
document.addEventListener('click',e=>{const m=document.getElementById('userMenu');if(m&&!m.contains(e.target))document.getElementById('userDropdown')?.classList.remove('open');});
function markAllRead(){fetch('mark_read.php');return true;}
</script>
</body>
</html>
