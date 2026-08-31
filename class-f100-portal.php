<?php

if (!defined('ABSPATH')) {
    exit;
}

class F100_Portal {
    public static function init() {
        add_shortcode('f100_customer_login', array(__CLASS__, 'customer_login'));
        add_shortcode('f100_staff_login', array(__CLASS__, 'staff_login'));
        add_shortcode('f100_customer_portal', array(__CLASS__, 'customer_portal'));
        add_shortcode('f100_staff_portal', array(__CLASS__, 'staff_portal'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        add_action('template_redirect', array(__CLASS__, 'process_login'));
        add_action('admin_post_f100_download_document', array(__CLASS__, 'download_document'));
        add_action('admin_init', array(__CLASS__, 'restrict_dashboard'));
        add_filter('show_admin_bar', array(__CLASS__, 'hide_admin_bar'));
    }

    public static function enqueue_assets() {
        wp_enqueue_style('f100-portal', F100_URL . 'assets/css/portal.css', array(), F100_VERSION);
    }

    public static function hide_admin_bar($show) {
        if (current_user_can('manage_finance100')) {
            return $show;
        }
        return false;
    }

    public static function restrict_dashboard() {
        if (!is_user_logged_in() || current_user_can('manage_finance100') || wp_doing_ajax()) {
            return;
        }
        global $pagenow;
        if ($pagenow === 'admin-post.php') {
            return;
        }

        $user = wp_get_current_user();
        if (in_array('finance_customer', $user->roles, true)) {
            $target = get_permalink((int) get_option('f100_page_customer_dashboard'));
        } else {
            $target = get_permalink((int) get_option('f100_page_staff_dashboard'));
        }
        wp_safe_redirect($target ?: home_url('/'));
        exit;
    }

    public static function process_login() {
        if (empty($_POST['f100_login_action'])) {
            return;
        }

        $type = sanitize_key(wp_unslash($_POST['f100_login_action']));
        if (!in_array($type, array('customer', 'staff'), true)) {
            return;
        }
        if (!isset($_POST['f100_login_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['f100_login_nonce'])), 'f100_login_' . $type)) {
            self::login_redirect($type, 'security');
        }

        $credentials = array(
            'user_login' => sanitize_user(wp_unslash($_POST['log'] ?? '')),
            'user_password' => (string) wp_unslash($_POST['pwd'] ?? ''),
            'remember' => !empty($_POST['rememberme']),
        );
        $user = wp_signon($credentials, is_ssl());
        if (is_wp_error($user)) {
            self::login_redirect($type, 'credentials');
        }

        $is_admin = user_can($user, 'manage_finance100');
        $valid = $type === 'customer'
            ? in_array('finance_customer', $user->roles, true)
            : ($is_admin || (bool) array_intersect($user->roles, array('finance_salesperson', 'finance_company_member')));

        if (!$valid) {
            wp_logout();
            self::login_redirect($type, 'role');
        }

        if ($is_admin) {
            $target = admin_url('admin.php?page=finance100');
        } elseif ($type === 'customer') {
            $target = get_permalink((int) get_option('f100_page_customer_dashboard'));
        } else {
            $target = get_permalink((int) get_option('f100_page_staff_dashboard'));
        }
        wp_safe_redirect($target ?: home_url('/'));
        exit;
    }

    private static function login_redirect($type, $error) {
        $page_key = $type === 'customer' ? 'customer_login' : 'staff_login';
        $target = get_permalink((int) get_option('f100_page_' . $page_key));
        wp_safe_redirect(add_query_arg('f100_login_error', sanitize_key($error), $target ?: home_url('/')));
        exit;
    }

    public static function customer_login() {
        return self::login_form('customer');
    }

    public static function staff_login() {
        return self::login_form('staff');
    }

    private static function login_form($type) {
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $customer = in_array('finance_customer', $user->roles, true);
            $page_id = $customer ? get_option('f100_page_customer_dashboard') : get_option('f100_page_staff_dashboard');
            return '<div class="f100-login-shell"><div class="f100-login-card"><p class="f100-kicker">FINANCE100 SECURE ACCESS</p><h2>You are already signed in</h2><p>Continue to your protected dashboard.</p><a class="f100-btn" href="' . esc_url(get_permalink((int) $page_id)) . '">Open dashboard</a></div></div>';
        }

        $is_customer = $type === 'customer';
        $title = $is_customer ? 'Customer login' : 'Staff login';
        $subtitle = $is_customer ? 'View your account, 100-day payment record and private documents.' : 'View assigned customers and account collection progress.';
        $error = isset($_GET['f100_login_error']) ? sanitize_key(wp_unslash($_GET['f100_login_error'])) : '';
        $message = '';
        if ($error === 'role') {
            $message = 'Please use the correct login page for this account.';
        } elseif ($error) {
            $message = 'The username or password was not accepted. Please try again.';
        }

        ob_start();
        ?>
        <div class="f100-login-shell">
            <div class="f100-login-aside">
                <div class="f100-mark">F100</div>
                <p class="f100-kicker">PRIVATE FINANCE PORTAL</p>
                <h2>100 days.<br>One clear record.</h2>
                <p>Simple payment visibility with secure, role-based access.</p>
                <div class="f100-trust-line"><span aria-hidden="true">●</span> Protected customer information</div>
            </div>
            <div class="f100-login-card">
                <p class="f100-kicker"><?php echo esc_html($is_customer ? 'CUSTOMER ACCESS' : 'COMPANY ACCESS'); ?></p>
                <h1><?php echo esc_html($title); ?></h1>
                <p><?php echo esc_html($subtitle); ?></p>
                <?php if ($message) : ?><div class="f100-alert" role="alert"><?php echo esc_html($message); ?></div><?php endif; ?>
                <form method="post" class="f100-login-form">
                    <input type="hidden" name="f100_login_action" value="<?php echo esc_attr($type); ?>">
                    <?php wp_nonce_field('f100_login_' . $type, 'f100_login_nonce'); ?>
                    <label><span>Username</span><input type="text" name="log" autocomplete="username" required></label>
                    <label><span>Password</span><input type="password" name="pwd" autocomplete="current-password" required></label>
                    <label class="f100-check"><input type="checkbox" name="rememberme" value="1"> <span>Keep me signed in</span></label>
                    <button type="submit" class="f100-btn">Sign in securely</button>
                </form>
                <p class="f100-help">Contact the administrator if you need a login or password reset.</p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function customer_portal() {
        if (!is_user_logged_in()) {
            return self::access_prompt('customer');
        }
        $user = wp_get_current_user();
        if (!in_array('finance_customer', $user->roles, true)) {
            return '<div class="f100-access-denied">This dashboard is available only to customer accounts.</div>';
        }

        global $wpdb;
        $accounts_table = $wpdb->prefix . 'f100_accounts';
        $payments_table = $wpdb->prefix . 'f100_payments';
        $documents_table = $wpdb->prefix . 'f100_documents';
        $accounts = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$accounts_table} WHERE customer_id = %d ORDER BY created_at DESC", $user->ID));
        $documents = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$documents_table} WHERE customer_id = %d ORDER BY created_at DESC", $user->ID));

        ob_start();
        ?>
        <div class="f100-portal">
            <?php echo self::portal_header($user, 'Customer dashboard'); ?>
            <section class="f100-welcome">
                <div><p class="f100-kicker">YOUR FINANCE RECORD</p><h1>Namaste, <?php echo esc_html($user->display_name); ?></h1><p>Every payment and document shown here is read-only.</p></div>
                <div class="f100-identity"><span>Customer ID</span><strong>#<?php echo esc_html(str_pad((string) $user->ID, 6, '0', STR_PAD_LEFT)); ?></strong></div>
            </section>

            <?php if (!$accounts) : ?>
                <div class="f100-empty"><h2>No finance account yet</h2><p>Your administrator has not assigned a 100-day payment schedule to this login.</p></div>
            <?php endif; ?>

            <?php foreach ($accounts as $account) :
                $payments = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$payments_table} WHERE account_id = %d ORDER BY installment_no ASC", $account->id));
                $paid_total = array_sum(array_map(function ($row) { return (float) $row->amount_paid; }, $payments));
                $paid_days = count(array_filter($payments, function ($row) { return in_array($row->status, array('paid', 'waived'), true); }));
                $progress = min(100, max(0, $paid_days));
                ?>
                <section class="f100-account-block">
                    <div class="f100-account-title"><div><p class="f100-kicker">ACCOUNT <?php echo esc_html($account->account_number); ?></p><h2>100-day payment plan</h2></div><span class="f100-pill f100-pill-<?php echo esc_attr($account->status); ?>"><?php echo esc_html(ucwords(str_replace('_', ' ', $account->status))); ?></span></div>
                    <div class="f100-summary-grid">
                        <div><span>Principal</span><strong>₹<?php echo esc_html(number_format_i18n($account->principal_amount, 2)); ?></strong></div>
                        <div><span>Daily payment</span><strong>₹<?php echo esc_html(number_format_i18n($account->daily_amount, 2)); ?></strong></div>
                        <div><span>Paid so far</span><strong>₹<?php echo esc_html(number_format_i18n($paid_total, 2)); ?></strong></div>
                        <div><span>Balance</span><strong>₹<?php echo esc_html(number_format_i18n(max(0, $account->total_due - $paid_total), 2)); ?></strong></div>
                    </div>
                    <div class="f100-progress-head"><span><?php echo esc_html($paid_days); ?> of 100 days complete</span><strong><?php echo esc_html($progress); ?>%</strong></div>
                    <div class="f100-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr($progress); ?>"><span style="width:<?php echo esc_attr($progress); ?>%"></span></div>
                    <div class="f100-table-scroll"><table class="f100-table"><thead><tr><th>Day</th><th>Due date</th><th>Amount</th><th>Paid</th><th>Payment date</th><th>Status</th></tr></thead><tbody>
                    <?php foreach ($payments as $payment) : ?>
                        <tr><td><strong><?php echo esc_html($payment->installment_no); ?></strong></td><td><?php echo esc_html(mysql2date(get_option('date_format'), $payment->due_date)); ?></td><td>₹<?php echo esc_html(number_format_i18n($payment->amount_due, 2)); ?></td><td>₹<?php echo esc_html(number_format_i18n($payment->amount_paid, 2)); ?></td><td><?php echo $payment->payment_date ? esc_html(mysql2date(get_option('date_format'), $payment->payment_date)) : '—'; ?></td><td><span class="f100-pill f100-pill-<?php echo esc_attr($payment->status); ?>"><?php echo esc_html(ucfirst($payment->status)); ?></span></td></tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                </section>
            <?php endforeach; ?>

            <section class="f100-documents"><div class="f100-section-title"><div><p class="f100-kicker">PRIVATE FILES</p><h2>My documents</h2></div><span><?php echo esc_html(count($documents)); ?> file(s)</span></div>
                <?php if (!$documents) : ?><div class="f100-empty compact"><p>No document has been added to your portal.</p></div><?php endif; ?>
                <div class="f100-document-grid">
                <?php foreach ($documents as $document) : ?>
                    <a class="f100-document" href="<?php echo esc_url(self::document_url($document->id)); ?>">
                        <span class="f100-file-icon"><?php echo esc_html(strtoupper(pathinfo($document->original_name, PATHINFO_EXTENSION))); ?></span>
                        <span><strong><?php echo esc_html($document->document_title); ?></strong><small><?php echo esc_html(size_format($document->file_size)); ?> · <?php echo esc_html(mysql2date(get_option('date_format'), $document->created_at)); ?></small></span>
                        <b aria-hidden="true">View →</b>
                    </a>
                <?php endforeach; ?>
                </div>
            </section>
            <section class="f100-profile"><div><p class="f100-kicker">READ-ONLY PROFILE</p><h2>Customer details</h2></div><dl><div><dt>Email</dt><dd><?php echo esc_html($user->user_email); ?></dd></div><div><dt>Mobile</dt><dd><?php echo esc_html(get_user_meta($user->ID, 'f100_phone', true) ?: '—'); ?></dd></div><div><dt>ID / reference</dt><dd><?php echo esc_html(get_user_meta($user->ID, 'f100_id_number', true) ?: '—'); ?></dd></div><div><dt>Address</dt><dd><?php echo esc_html(get_user_meta($user->ID, 'f100_address', true) ?: '—'); ?></dd></div></dl></section>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function staff_portal() {
        if (!is_user_logged_in()) {
            return self::access_prompt('staff');
        }
        $user = wp_get_current_user();
        $is_salesperson = in_array('finance_salesperson', $user->roles, true);
        $is_member = in_array('finance_company_member', $user->roles, true);
        if (!$is_salesperson && !$is_member && !current_user_can('manage_finance100')) {
            return '<div class="f100-access-denied">This dashboard is available only to company staff.</div>';
        }
        if (current_user_can('manage_finance100')) {
            return '<div class="f100-access-denied">Administrators manage Finance100 from <a href="' . esc_url(admin_url('admin.php?page=finance100')) . '">WordPress Admin</a>.</div>';
        }

        global $wpdb;
        $accounts_table = $wpdb->prefix . 'f100_accounts';
        $payments_table = $wpdb->prefix . 'f100_payments';
        $column = $is_salesperson ? 'salesperson_id' : 'member_id';
        $accounts = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$accounts_table} WHERE {$column} = %d ORDER BY created_at DESC", $user->ID));

        ob_start();
        ?>
        <div class="f100-portal">
            <?php echo self::portal_header($user, 'Staff dashboard'); ?>
            <section class="f100-welcome"><div><p class="f100-kicker">READ-ONLY COMPANY VIEW</p><h1>Welcome, <?php echo esc_html($user->display_name); ?></h1><p>Assigned customer accounts and collection progress are shown below.</p></div><div class="f100-identity"><span>Role</span><strong><?php echo esc_html($is_salesperson ? 'Salesperson' : 'Company member'); ?></strong></div></section>
            <section class="f100-account-block"><div class="f100-section-title"><div><p class="f100-kicker">ASSIGNED ACCOUNTS</p><h2><?php echo esc_html(count($accounts)); ?> customer account(s)</h2></div><span>Read only</span></div>
            <?php if (!$accounts) : ?><div class="f100-empty"><h2>No assigned customer</h2><p>The administrator has not assigned an account to this login.</p></div><?php endif; ?>
            <div class="f100-staff-grid">
            <?php foreach ($accounts as $account) :
                $customer = get_userdata($account->customer_id);
                $paid = (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(amount_paid),0) FROM {$payments_table} WHERE account_id = %d", $account->id));
                $completed = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$payments_table} WHERE account_id = %d AND status IN ('paid','waived')", $account->id));
                ?>
                <article class="f100-staff-card"><div class="f100-staff-card-top"><span class="f100-avatar"><?php echo esc_html(strtoupper(substr($customer ? $customer->display_name : '?', 0, 1))); ?></span><div><h3><?php echo esc_html($customer ? $customer->display_name : 'Deleted customer'); ?></h3><p><?php echo esc_html($account->account_number); ?></p></div><span class="f100-pill f100-pill-<?php echo esc_attr($account->status); ?>"><?php echo esc_html(ucwords(str_replace('_', ' ', $account->status))); ?></span></div><div class="f100-staff-numbers"><span>Daily <strong>₹<?php echo esc_html(number_format_i18n($account->daily_amount, 2)); ?></strong></span><span>Collected <strong>₹<?php echo esc_html(number_format_i18n($paid, 2)); ?></strong></span><span>Balance <strong>₹<?php echo esc_html(number_format_i18n(max(0, $account->total_due - $paid), 2)); ?></strong></span></div><div class="f100-progress-head"><span><?php echo esc_html($completed); ?>/100 days</span><strong><?php echo esc_html($completed); ?>%</strong></div><div class="f100-progress"><span style="width:<?php echo esc_attr(min(100, $completed)); ?>%"></span></div></article>
            <?php endforeach; ?>
            </div></section>
            <div class="f100-readonly-note">Staff can view assigned summaries only. Payments, customer details and documents can be changed only by the administrator.</div>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function portal_header($user, $label) {
        return '<header class="f100-portal-header"><a class="f100-brand" href="' . esc_url(home_url('/')) . '"><span>F100</span><strong>Finance100</strong></a><div><span class="f100-header-label">' . esc_html($label) . '</span><strong>' . esc_html($user->display_name) . '</strong><a href="' . esc_url(wp_logout_url(home_url('/'))) . '">Sign out</a></div></header>';
    }

    private static function access_prompt($type) {
        $page_id = get_option('f100_page_' . ($type === 'customer' ? 'customer_login' : 'staff_login'));
        return '<div class="f100-empty"><h2>Sign in required</h2><p>Please use your secure ' . esc_html($type) . ' login to open this page.</p><a class="f100-btn" href="' . esc_url(get_permalink((int) $page_id)) . '">Go to login</a></div>';
    }

    public static function document_url($document_id) {
        $url = admin_url('admin-post.php?action=f100_download_document&document_id=' . absint($document_id));
        return wp_nonce_url($url, 'f100_document_' . absint($document_id));
    }

    public static function download_document() {
        if (!is_user_logged_in()) {
            auth_redirect();
        }
        $document_id = absint($_GET['document_id'] ?? 0);
        check_admin_referer('f100_document_' . $document_id);
        global $wpdb;
        $table = $wpdb->prefix . 'f100_documents';
        $document = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $document_id));
        if (!$document) {
            wp_die(esc_html__('Document not found.', 'finance100'), '', array('response' => 404));
        }
        $allowed = current_user_can('manage_finance100') || (int) $document->customer_id === get_current_user_id();
        if (!$allowed) {
            wp_die(esc_html__('You cannot open this customer document.', 'finance100'), '', array('response' => 403));
        }
        $path = trailingslashit(F100_Activator::private_directory()) . basename($document->stored_name);
        if (!is_readable($path)) {
            wp_die(esc_html__('The document file is unavailable.', 'finance100'), '', array('response' => 404));
        }

        nocache_headers();
        header('X-Content-Type-Options: nosniff');
        header('X-Robots-Tag: noindex, nofollow', true);
        header('Content-Type: ' . $document->mime_type);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: inline; filename="' . rawurlencode($document->original_name) . '"');
        readfile($path);
        exit;
    }
}

