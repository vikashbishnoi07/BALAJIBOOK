<?php
get_header();
$customer_login = (int) get_option('f100_page_customer_login');
$staff_login = (int) get_option('f100_page_staff_login');
?>
<section class="finance-hero">
    <div class="site-shell hero-grid">
        <div>
            <p class="eyebrow">SECURE 100-DAY FINANCE MANAGEMENT</p>
            <h1>Every payment. Every day. One trusted record.</h1>
            <p class="lead">A simple, protected finance portal for administrators, company members, salespersons and customers.</p>
            <div class="hero-actions">
                <a class="theme-btn primary" href="<?php echo esc_url($customer_login ? get_permalink($customer_login) : '#'); ?>">Customer login</a>
                <a class="theme-btn" href="<?php echo esc_url($staff_login ? get_permalink($staff_login) : '#'); ?>">Staff login</a>
            </div>
        </div>
        <div class="hero-ledger" aria-label="Example 100-day account summary">
            <div class="ledger-top"><strong>Payment overview</strong><span class="secure-dot">● SECURE</span></div>
            <div class="ledger-progress"><div class="ledger-progress-label"><span>64 of 100 days complete</span><strong>64%</strong></div><div class="ledger-track"><span></span></div></div>
            <div class="ledger-numbers"><div><span>Daily payment</span><strong>₹500.00</strong></div><div><span>Balance</span><strong>₹18,000.00</strong></div><div><span>Paid so far</span><strong>₹32,000.00</strong></div><div><span>Status</span><strong>On schedule</strong></div></div>
            <div class="mini-days" aria-hidden="true"><?php for ($i = 0; $i < 10; $i++) { echo '<i></i>'; } ?></div>
        </div>
    </div>
</section>
<section class="trust-strip"><div class="site-shell trust-grid"><div class="trust-item"><span class="trust-icon">01</span><div><strong>Administrator controlled</strong><span>Only the admin can change finance records</span></div></div><div class="trust-item"><span class="trust-icon">02</span><div><strong>Separate role logins</strong><span>Customer and staff access remain distinct</span></div></div><div class="trust-item"><span class="trust-icon">03</span><div><strong>Private documents</strong><span>Protected files for the matching customer</span></div></div></div></section>
<section class="home-section"><div class="site-shell"><div class="section-heading"><p class="eyebrow">BUILT FOR CLEAR RESPONSIBILITY</p><h2>The right information for each person.</h2><p>Every role sees only what it needs. Editing stays with the administrator.</p></div><div class="role-grid"><article class="role-card"><span class="number">01</span><h3>Administrator</h3><p>Full control of all customers, staff, accounts, payments and documents.</p><ul><li>Create every login</li><li>Manage 100-day schedules</li><li>Upload private files</li></ul></article><article class="role-card"><span class="number">02</span><h3>Company & sales staff</h3><p>A read-only view of customer accounts assigned by the administrator.</p><ul><li>Separate staff login</li><li>Collection progress</li><li>No customer document access</li></ul></article><article class="role-card"><span class="number">03</span><h3>Customer</h3><p>A private, read-only dashboard for their own payment record and documents.</p><ul><li>100 daily entries</li><li>Balance and progress</li><li>Protected document viewer</li></ul></article></div></div></section>
<div class="site-shell"><section class="portal-callout"><div><h2>Already have your Finance100 login?</h2><p>Open your secure portal to view the latest information added by the administrator.</p></div><a class="theme-btn" href="<?php echo esc_url($customer_login ? get_permalink($customer_login) : '#'); ?>">Open customer portal</a></section></div>
<?php get_footer(); ?>

