<?php

if (!defined('ABSPATH')) {
    exit;
}

class F100_Admin {
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'register_menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        add_action('admin_post_f100_save_user', array(__CLASS__, 'save_user'));
        add_action('admin_post_f100_save_account', array(__CLASS__, 'save_account'));
        add_action('admin_post_f100_save_payment', array(__CLASS__, 'save_payment'));
        add_action('admin_post_f100_upload_document', array(__CLASS__, 'upload_document'));
        add_action('admin_post_f100_delete_document', array(__CLASS__, 'delete_document'));
    }

    public static function register_menu() {
        add_menu_page('Finance100', 'Finance100', 'manage_finance100', 'finance100', array(__CLASS__, 'render_overview'), 'dashicons-chart-area', 25);
        add_submenu_page('finance100', 'Finance100 Overview', 'Overview', 'manage_finance100', 'finance100', array(__CLASS__, 'render_overview'));
        add_submenu_page('finance100', 'People', 'Customers & Staff', 'manage_finance100', 'finance100-users', array(__CLASS__, 'render_users'));
        add_submenu_page('finance100', 'Finance Accounts', 'Finance Accounts', 'manage_finance100', 'finance100-accounts', array(__CLASS__, 'render_accounts'));
        add_submenu_page('finance100', '100-Day Payments', '100-Day Payments', 'manage_finance100', 'finance100-payments', array(__CLASS__, 'render_payments'));
        add_submenu_page('finance100', 'Private Documents', 'Private Documents', 'manage_finance100', 'finance100-documents', array(__CLASS__, 'render_documents'));
    }

    public static function enqueue_assets($hook) {
        if (strpos($hook, 'finance100') === false) {
            return;
        }
        wp_enqueue_style('f100-admin', F100_URL . 'assets/css/admin.css', array(), F100_VERSION);
    }

    private static function guard($nonce_action) {
        if (!current_user_can('manage_finance100')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'finance100'));
        }
        check_admin_referer($nonce_action);
    }

    private static function admin_url($page, $args = array()) {
        return add_query_arg(array_merge(array('page' => $page), $args), admin_url('admin.php'));
    }

    private static function redirect($page, $notice, $extra = array()) {
        wp_safe_redirect(self::admin_url($page, array_merge($extra, array('f100_notice' => $notice))));
        exit;
    }

    private static function render_header($title, $description = '') {
        echo '<div class="wrap f100-admin-wrap">';
        echo '<div class="f100-admin-heading"><div><p class="f100-eyebrow">FINANCE100 CONTROL CENTRE</p><h1>' . esc_html($title) . '</h1>';
        if ($description) {
            echo '<p>' . esc_html($description) . '</p>';
        }
        echo '</div><span class="f100-admin-badge">Administrator only</span></div>';

        if (!empty($_GET['f100_notice'])) {
            $messages = array(
                'user_saved' => 'User account saved successfully.',
                'account_saved' => 'Finance account and payment schedule saved.',
                'payment_saved' => 'Payment entry updated.',
                'document_saved' => 'Private document uploaded.',
                'document_deleted' => 'Document deleted.',
                'error' => 'The request could not be completed. Please check the form and try again.',
            );
            $key = sanitize_key(wp_unslash($_GET['f100_notice']));
            if (isset($messages[$key])) {
                echo '<div class="notice ' . ($key === 'error' ? 'notice-error' : 'notice-success') . ' is-dismissible"><p>' . esc_html($messages[$key]) . '</p></div>';
            }
        }
    }

    private static function render_footer() {
        echo '</div>';
    }

    public static function render_overview() {
        global $wpdb;
        $accounts_table = $wpdb->prefix . 'f100_accounts';
        $payments_table = $wpdb->prefix . 'f100_payments';
        $documents_table = $wpdb->prefix . 'f100_documents';

        $customer_count = count_users();
        $customer_count = isset($customer_count['avail_roles']['finance_customer']) ? (int) $customer_count['avail_roles']['finance_customer'] : 0;
        $active_accounts = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$accounts_table} WHERE status = 'active'");
        $paid_total = (float) $wpdb->get_var("SELECT COALESCE(SUM(amount_paid),0) FROM {$payments_table}");
        $pending_total = (float) $wpdb->get_var("SELECT COALESCE(SUM(GREATEST(amount_due - amount_paid,0)),0) FROM {$payments_table}");
        $document_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$documents_table}");

        self::render_header('Overview', 'A clear view of customers, collections and private records.');
        echo '<div class="f100-stat-grid">';
        self::stat_card('Customers', number_format_i18n($customer_count), 'Registered customer logins');
        self::stat_card('Active accounts', number_format_i18n($active_accounts), 'Current 100-day schedules');
        self::stat_card('Collected', '₹' . number_format_i18n($paid_total, 2), 'All recorded payments');
        self::stat_card('Balance due', '₹' . number_format_i18n($pending_total, 2), 'Outstanding schedule amount');
        self::stat_card('Documents', number_format_i18n($document_count), 'Protected customer files');
        echo '</div>';

        echo '<div class="f100-panel"><h2>Quick actions</h2><div class="f100-actions">';
        echo '<a class="button button-primary" href="' . esc_url(self::admin_url('finance100-users')) . '">Add customer or staff</a>';
        echo '<a class="button" href="' . esc_url(self::admin_url('finance100-accounts')) . '">Create finance account</a>';
        echo '<a class="button" href="' . esc_url(self::admin_url('finance100-payments')) . '">Update daily payment</a>';
        echo '<a class="button" href="' . esc_url(self::admin_url('finance100-documents')) . '">Upload customer document</a>';
        echo '</div></div>';

        echo '<div class="f100-panel"><h2>Portal pages</h2><p>These pages were created automatically. Add them to your WordPress menu if required.</p><div class="f100-link-list">';
        foreach (array('customer_login' => 'Customer login', 'staff_login' => 'Staff login', 'customer_dashboard' => 'Customer dashboard', 'staff_dashboard' => 'Staff dashboard') as $key => $label) {
            $page_id = (int) get_option('f100_page_' . $key);
            if ($page_id) {
                echo '<a target="_blank" rel="noopener" href="' . esc_url(get_permalink($page_id)) . '">' . esc_html($label) . ' <span aria-hidden="true">↗</span></a>';
            }
        }
        echo '</div></div>';
        self::render_footer();
    }

    private static function stat_card($label, $value, $caption) {
        echo '<div class="f100-stat"><span>' . esc_html($label) . '</span><strong>' . esc_html($value) . '</strong><small>' . esc_html($caption) . '</small></div>';
    }

    public static function render_users() {
        $edit_id = isset($_GET['f100_user_id']) ? absint($_GET['f100_user_id']) : 0;
        $edit_user = $edit_id ? get_userdata($edit_id) : false;
        $allowed_roles = self::allowed_roles();
        if ($edit_user && !array_intersect($edit_user->roles, array_keys($allowed_roles))) {
            $edit_user = false;
        }

        self::render_header('Customers & staff', 'Create and manage the separate logins used by customers, salespersons and company members.');
        echo '<div class="f100-two-col"><section class="f100-panel"><h2>' . ($edit_user ? 'Edit login' : 'Create login') . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="f100-form">';
        wp_nonce_field('f100_save_user');
        echo '<input type="hidden" name="action" value="f100_save_user"><input type="hidden" name="user_id" value="' . esc_attr($edit_user ? $edit_user->ID : 0) . '">';
        self::input('Full name', 'display_name', $edit_user ? $edit_user->display_name : '', 'text', true);
        if (!$edit_user) {
            self::input('Username', 'user_login', '', 'text', true);
        }
        self::input('Email', 'user_email', $edit_user ? $edit_user->user_email : '', 'email', true);
        self::input($edit_user ? 'New password (leave blank to keep)' : 'Password', 'user_pass', '', 'password', !$edit_user);
        echo '<label><span>Login type</span><select name="role" required>';
        $current_role = $edit_user ? reset($edit_user->roles) : 'finance_customer';
        foreach ($allowed_roles as $role => $label) {
            echo '<option value="' . esc_attr($role) . '" ' . selected($current_role, $role, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></label>';
        self::input('Mobile number', 'phone', $edit_user ? get_user_meta($edit_user->ID, 'f100_phone', true) : '');
        self::input('ID / reference number', 'id_number', $edit_user ? get_user_meta($edit_user->ID, 'f100_id_number', true) : '');
        echo '<label><span>Address</span><textarea name="address" rows="3">' . esc_textarea($edit_user ? get_user_meta($edit_user->ID, 'f100_address', true) : '') . '</textarea></label>';
        echo '<button class="button button-primary" type="submit">' . ($edit_user ? 'Update login' : 'Create login') . '</button>';
        echo '</form></section>';

        echo '<section class="f100-panel f100-table-panel"><h2>Existing logins</h2>';
        $users = get_users(array('role__in' => array_keys($allowed_roles), 'orderby' => 'display_name', 'order' => 'ASC'));
        echo '<div class="f100-table-scroll"><table class="widefat striped"><thead><tr><th>Name</th><th>Role</th><th>Contact</th><th></th></tr></thead><tbody>';
        if (!$users) {
            echo '<tr><td colspan="4">No customer or staff login has been created.</td></tr>';
        }
        foreach ($users as $user) {
            $role = reset($user->roles);
            echo '<tr><td><strong>' . esc_html($user->display_name) . '</strong><br><code>' . esc_html($user->user_login) . '</code></td><td>' . esc_html(isset($allowed_roles[$role]) ? $allowed_roles[$role] : $role) . '</td><td>' . esc_html($user->user_email) . '<br>' . esc_html(get_user_meta($user->ID, 'f100_phone', true)) . '</td><td><a href="' . esc_url(self::admin_url('finance100-users', array('f100_user_id' => $user->ID))) . '">Edit</a></td></tr>';
        }
        echo '</tbody></table></div></section></div>';
        self::render_footer();
    }

    public static function save_user() {
        self::guard('f100_save_user');
        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        $role = isset($_POST['role']) ? sanitize_key(wp_unslash($_POST['role'])) : '';
        if (!isset(self::allowed_roles()[$role])) {
            self::redirect('finance100-users', 'error');
        }

        $data = array(
            'display_name' => sanitize_text_field(wp_unslash($_POST['display_name'] ?? '')),
            'user_email' => sanitize_email(wp_unslash($_POST['user_email'] ?? '')),
            'role' => $role,
        );
        $password = isset($_POST['user_pass']) ? (string) wp_unslash($_POST['user_pass']) : '';
        if ($password !== '') {
            $data['user_pass'] = $password;
        }

        if ($user_id) {
            $data['ID'] = $user_id;
            $result = wp_update_user($data);
        } else {
            $data['user_login'] = sanitize_user(wp_unslash($_POST['user_login'] ?? ''), true);
            if ($password === '') {
                self::redirect('finance100-users', 'error');
            }
            $result = wp_insert_user($data);
        }

        if (is_wp_error($result)) {
            self::redirect('finance100-users', 'error');
        }

        update_user_meta($result, 'f100_phone', sanitize_text_field(wp_unslash($_POST['phone'] ?? '')));
        update_user_meta($result, 'f100_id_number', sanitize_text_field(wp_unslash($_POST['id_number'] ?? '')));
        update_user_meta($result, 'f100_address', sanitize_textarea_field(wp_unslash($_POST['address'] ?? '')));
        self::redirect('finance100-users', 'user_saved');
    }

    private static function allowed_roles() {
        return array(
            'finance_customer' => 'Customer',
            'finance_salesperson' => 'Salesperson',
            'finance_company_member' => 'Company member',
        );
    }

    private static function input($label, $name, $value = '', $type = 'text', $required = false) {
        $number_attributes = $type === 'number' ? ' min="0" step="0.01"' : '';
        echo '<label><span>' . esc_html($label) . '</span><input type="' . esc_attr($type) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '"' . $number_attributes . ' ' . ($required ? 'required' : '') . '></label>';
    }

    public static function render_accounts() {
        global $wpdb;
        $table = $wpdb->prefix . 'f100_accounts';
        $edit_id = isset($_GET['account_id']) ? absint($_GET['account_id']) : 0;
        $account = $edit_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $edit_id)) : false;
        $customers = get_users(array('role' => 'finance_customer', 'orderby' => 'display_name'));
        $salespeople = get_users(array('role' => 'finance_salesperson', 'orderby' => 'display_name'));
        $members = get_users(array('role' => 'finance_company_member', 'orderby' => 'display_name'));

        self::render_header('Finance accounts', 'Create one 100-day repayment account for each customer agreement.');
        echo '<div class="f100-two-col"><section class="f100-panel"><h2>' . ($account ? 'Edit account' : 'New 100-day account') . '</h2>';
        if (!$customers) {
            echo '<div class="notice notice-warning inline"><p>Create a customer login before creating a finance account.</p></div>';
        }
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="f100-form">';
        wp_nonce_field('f100_save_account');
        echo '<input type="hidden" name="action" value="f100_save_account"><input type="hidden" name="account_id" value="' . esc_attr($account ? $account->id : 0) . '">';
        self::input('Account number (blank = automatic)', 'account_number', $account ? $account->account_number : '');
        self::user_select('Customer', 'customer_id', $customers, $account ? $account->customer_id : 0, true);
        self::user_select('Salesperson', 'salesperson_id', $salespeople, $account ? $account->salesperson_id : 0, false);
        self::user_select('Company member', 'member_id', $members, $account ? $account->member_id : 0, false);
        self::input('Principal amount (₹)', 'principal_amount', $account ? $account->principal_amount : '', 'number', true);
        self::input('Daily installment (₹)', 'daily_amount', $account ? $account->daily_amount : '', 'number', true);
        self::input('Total due (₹)', 'total_due', $account ? $account->total_due : '', 'number');
        self::input('Schedule start date', 'start_date', $account ? $account->start_date : current_time('Y-m-d'), 'date', true);
        echo '<label><span>Status</span><select name="status"><option value="active" ' . selected($account ? $account->status : 'active', 'active', false) . '>Active</option><option value="completed" ' . selected($account ? $account->status : '', 'completed', false) . '>Completed</option><option value="on_hold" ' . selected($account ? $account->status : '', 'on_hold', false) . '>On hold</option></select></label>';
        echo '<label><span>Admin notes</span><textarea name="notes" rows="3">' . esc_textarea($account ? $account->notes : '') . '</textarea></label>';
        echo '<p class="description">For a new account, the plugin creates all 100 daily rows automatically. Editing an account never overwrites payment history.</p>';
        echo '<button class="button button-primary" type="submit" ' . (!$customers ? 'disabled' : '') . '>' . ($account ? 'Update account' : 'Create account & schedule') . '</button>';
        echo '</form></section>';

        $accounts = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC");
        echo '<section class="f100-panel f100-table-panel"><h2>All finance accounts</h2><div class="f100-table-scroll"><table class="widefat striped"><thead><tr><th>Account</th><th>Customer</th><th>Daily</th><th>Status</th><th></th></tr></thead><tbody>';
        if (!$accounts) {
            echo '<tr><td colspan="5">No finance account has been created.</td></tr>';
        }
        foreach ($accounts as $row) {
            $customer = get_userdata($row->customer_id);
            echo '<tr><td><strong>' . esc_html($row->account_number) . '</strong><br><small>' . esc_html(mysql2date(get_option('date_format'), $row->start_date)) . '</small></td><td>' . esc_html($customer ? $customer->display_name : 'Deleted user') . '</td><td>₹' . esc_html(number_format_i18n($row->daily_amount, 2)) . '</td><td><span class="f100-status status-' . esc_attr($row->status) . '">' . esc_html(ucwords(str_replace('_', ' ', $row->status))) . '</span></td><td><a href="' . esc_url(self::admin_url('finance100-accounts', array('account_id' => $row->id))) . '">Edit</a> · <a href="' . esc_url(self::admin_url('finance100-payments', array('account_id' => $row->id))) . '">Payments</a></td></tr>';
        }
        echo '</tbody></table></div></section></div>';
        self::render_footer();
    }

    private static function user_select($label, $name, $users, $selected_id, $required) {
        echo '<label><span>' . esc_html($label) . '</span><select name="' . esc_attr($name) . '" ' . ($required ? 'required' : '') . '><option value="">— Select —</option>';
        foreach ($users as $user) {
            echo '<option value="' . esc_attr($user->ID) . '" ' . selected((int) $selected_id, (int) $user->ID, false) . '>' . esc_html($user->display_name . ' (' . $user->user_login . ')') . '</option>';
        }
        echo '</select></label>';
    }

    public static function save_account() {
        self::guard('f100_save_account');
        global $wpdb;
        $table = $wpdb->prefix . 'f100_accounts';
        $payments = $wpdb->prefix . 'f100_payments';
        $account_id = absint($_POST['account_id'] ?? 0);
        $customer_id = absint($_POST['customer_id'] ?? 0);
        $customer = get_userdata($customer_id);
        if (!$customer || !in_array('finance_customer', $customer->roles, true)) {
            self::redirect('finance100-accounts', 'error');
        }

        $daily_amount = max(0, (float) ($_POST['daily_amount'] ?? 0));
        $total_due = max(0, (float) ($_POST['total_due'] ?? 0));
        if (!$total_due) {
            $total_due = $daily_amount * 100;
        }
        $start_date = sanitize_text_field(wp_unslash($_POST['start_date'] ?? ''));
        $date_check = DateTime::createFromFormat('Y-m-d', $start_date);
        if (!$date_check || $date_check->format('Y-m-d') !== $start_date || $daily_amount <= 0 || $total_due < ($daily_amount * 99)) {
            self::redirect('finance100-accounts', 'error');
        }

        $account_number = sanitize_text_field(wp_unslash($_POST['account_number'] ?? ''));
        if (!$account_number) {
            $account_number = 'F100-' . current_time('Ym') . '-' . strtoupper(wp_generate_password(5, false, false));
        }
        $now = current_time('mysql');
        $data = array(
            'account_number' => $account_number,
            'customer_id' => $customer_id,
            'salesperson_id' => absint($_POST['salesperson_id'] ?? 0),
            'member_id' => absint($_POST['member_id'] ?? 0),
            'principal_amount' => max(0, (float) ($_POST['principal_amount'] ?? 0)),
            'daily_amount' => $daily_amount,
            'total_due' => $total_due,
            'start_date' => $start_date,
            'status' => in_array($_POST['status'] ?? '', array('active', 'completed', 'on_hold'), true) ? sanitize_key($_POST['status']) : 'active',
            'notes' => sanitize_textarea_field(wp_unslash($_POST['notes'] ?? '')),
            'updated_at' => $now,
        );
        $formats = array('%s', '%d', '%d', '%d', '%f', '%f', '%f', '%s', '%s', '%s', '%s');

        if ($account_id) {
            $result = $wpdb->update($table, $data, array('id' => $account_id), $formats, array('%d'));
        } else {
            $data['created_by'] = get_current_user_id();
            $data['created_at'] = $now;
            $formats[] = '%d';
            $formats[] = '%s';
            $result = $wpdb->insert($table, $data, $formats);
            $account_id = (int) $wpdb->insert_id;

            if ($result !== false && $account_id) {
                $start = new DateTime($start_date);
                $schedule_ok = true;
                for ($day = 1; $day <= 100; $day++) {
                    $due = clone $start;
                    $due->modify('+' . ($day - 1) . ' days');
                    $installment_due = $day === 100 ? ($total_due - ($daily_amount * 99)) : $daily_amount;
                    $payment_created = $wpdb->insert($payments, array(
                        'account_id' => $account_id,
                        'installment_no' => $day,
                        'due_date' => $due->format('Y-m-d'),
                        'amount_due' => $installment_due,
                        'amount_paid' => 0,
                        'status' => 'pending',
                        'updated_by' => get_current_user_id(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ), array('%d', '%d', '%s', '%f', '%f', '%s', '%d', '%s', '%s'));
                    if ($payment_created === false) {
                        $schedule_ok = false;
                        break;
                    }
                }
                if (!$schedule_ok) {
                    $wpdb->delete($payments, array('account_id' => $account_id), array('%d'));
                    $wpdb->delete($table, array('id' => $account_id), array('%d'));
                    $result = false;
                }
            }
        }

        self::redirect('finance100-accounts', $result === false ? 'error' : 'account_saved');
    }

    public static function render_payments() {
        global $wpdb;
        $accounts_table = $wpdb->prefix . 'f100_accounts';
        $payments_table = $wpdb->prefix . 'f100_payments';
        $accounts = $wpdb->get_results("SELECT * FROM {$accounts_table} ORDER BY created_at DESC");
        $account_id = isset($_GET['account_id']) ? absint($_GET['account_id']) : ($accounts ? (int) $accounts[0]->id : 0);
        $account = $account_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$accounts_table} WHERE id = %d", $account_id)) : false;

        self::render_header('100-day payments', 'Only an administrator can create, correct or update payment entries.');
        echo '<div class="f100-panel"><form method="get" class="f100-filter"><input type="hidden" name="page" value="finance100-payments"><label for="account_id">Finance account</label><select id="account_id" name="account_id" onchange="this.form.submit()">';
        foreach ($accounts as $row) {
            $customer = get_userdata($row->customer_id);
            echo '<option value="' . esc_attr($row->id) . '" ' . selected($account_id, (int) $row->id, false) . '>' . esc_html($row->account_number . ' — ' . ($customer ? $customer->display_name : 'Unknown')) . '</option>';
        }
        echo '</select><noscript><button class="button">Open</button></noscript></form></div>';

        if (!$account) {
            echo '<div class="f100-panel"><p>No account is available. Create a finance account first.</p></div>';
            self::render_footer();
            return;
        }

        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$payments_table} WHERE account_id = %d ORDER BY installment_no ASC", $account_id));
        $paid = 0;
        foreach ($rows as $row) {
            $paid += (float) $row->amount_paid;
        }
        echo '<div class="f100-payment-summary"><div><span>Account</span><strong>' . esc_html($account->account_number) . '</strong></div><div><span>Total due</span><strong>₹' . esc_html(number_format_i18n($account->total_due, 2)) . '</strong></div><div><span>Collected</span><strong>₹' . esc_html(number_format_i18n($paid, 2)) . '</strong></div><div><span>Balance</span><strong>₹' . esc_html(number_format_i18n(max(0, $account->total_due - $paid), 2)) . '</strong></div></div>';

        echo '<section class="f100-panel f100-table-panel"><div class="f100-table-scroll"><table class="widefat f100-payments-table"><thead><tr><th>Day</th><th>Due date</th><th>Due</th><th>Paid</th><th>Payment date</th><th>Status</th><th>Remarks</th><th></th></tr></thead><tbody>';
        foreach ($rows as $row) {
            $form_id = 'f100-payment-' . (int) $row->id;
            echo '<tr><td><strong>' . esc_html($row->installment_no) . '</strong></td><td>' . esc_html(mysql2date(get_option('date_format'), $row->due_date)) . '</td><td>₹' . esc_html(number_format_i18n($row->amount_due, 2)) . '</td>';
            echo '<td><input form="' . esc_attr($form_id) . '" aria-label="Amount paid for day ' . esc_attr($row->installment_no) . '" class="small-text" type="number" min="0" step="0.01" name="amount_paid" value="' . esc_attr($row->amount_paid) . '"></td>';
            echo '<td><input form="' . esc_attr($form_id) . '" aria-label="Payment date for day ' . esc_attr($row->installment_no) . '" type="date" name="payment_date" value="' . esc_attr($row->payment_date) . '"></td>';
            echo '<td><select form="' . esc_attr($form_id) . '" aria-label="Status for day ' . esc_attr($row->installment_no) . '" name="status">';
            foreach (array('pending' => 'Pending', 'paid' => 'Paid', 'partial' => 'Partial', 'late' => 'Late', 'waived' => 'Waived') as $status => $label) {
                echo '<option value="' . esc_attr($status) . '" ' . selected($row->status, $status, false) . '>' . esc_html($label) . '</option>';
            }
            echo '</select></td><td><input form="' . esc_attr($form_id) . '" aria-label="Remarks for day ' . esc_attr($row->installment_no) . '" type="text" name="remarks" value="' . esc_attr($row->remarks) . '"></td><td><form id="' . esc_attr($form_id) . '" method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="payment_id" value="' . esc_attr($row->id) . '"><input type="hidden" name="account_id" value="' . esc_attr($account_id) . '"><input type="hidden" name="action" value="f100_save_payment">';
            wp_nonce_field('f100_save_payment_' . $row->id);
            echo '<button class="button button-small">Save</button></form></td></tr>';
        }
        echo '</tbody></table></div></section>';
        self::render_footer();
    }

    public static function save_payment() {
        if (!current_user_can('manage_finance100')) {
            wp_die(esc_html__('Permission denied.', 'finance100'));
        }
        $payment_id = absint($_POST['payment_id'] ?? 0);
        check_admin_referer('f100_save_payment_' . $payment_id);
        global $wpdb;
        $table = $wpdb->prefix . 'f100_payments';
        $status = sanitize_key($_POST['status'] ?? 'pending');
        if (!in_array($status, array('pending', 'paid', 'partial', 'late', 'waived'), true)) {
            $status = 'pending';
        }
        $payment_date = sanitize_text_field(wp_unslash($_POST['payment_date'] ?? ''));
        $valid_payment_date = $payment_date ? DateTime::createFromFormat('Y-m-d', $payment_date) : false;
        if (!$valid_payment_date || $valid_payment_date->format('Y-m-d') !== $payment_date) {
            $payment_date = null;
        }
        $result = $wpdb->update($table, array(
            'amount_paid' => max(0, (float) ($_POST['amount_paid'] ?? 0)),
            'payment_date' => $payment_date,
            'status' => $status,
            'remarks' => sanitize_text_field(wp_unslash($_POST['remarks'] ?? '')),
            'updated_by' => get_current_user_id(),
            'updated_at' => current_time('mysql'),
        ), array('id' => $payment_id), array('%f', '%s', '%s', '%s', '%d', '%s'), array('%d'));
        self::redirect('finance100-payments', $result === false ? 'error' : 'payment_saved', array('account_id' => absint($_POST['account_id'] ?? 0)));
    }

    public static function render_documents() {
        global $wpdb;
        $table = $wpdb->prefix . 'f100_documents';
        $accounts_table = $wpdb->prefix . 'f100_accounts';
        $customers = get_users(array('role' => 'finance_customer', 'orderby' => 'display_name'));
        $accounts = $wpdb->get_results("SELECT * FROM {$accounts_table} ORDER BY created_at DESC");
        $documents = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC");

        self::render_header('Private documents', 'Upload PDF, JPG or PNG files. Only the administrator and the matching customer can open them.');
        echo '<div class="f100-two-col"><section class="f100-panel"><h2>Upload document</h2><form method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '" class="f100-form">';
        wp_nonce_field('f100_upload_document');
        echo '<input type="hidden" name="action" value="f100_upload_document">';
        self::user_select('Customer', 'customer_id', $customers, 0, true);
        echo '<label><span>Finance account (optional)</span><select name="account_id"><option value="0">— General customer document —</option>';
        foreach ($accounts as $account) {
            $customer = get_userdata($account->customer_id);
            echo '<option value="' . esc_attr($account->id) . '" data-customer="' . esc_attr($account->customer_id) . '">' . esc_html($account->account_number . ' — ' . ($customer ? $customer->display_name : 'Unknown')) . '</option>';
        }
        echo '</select></label>';
        self::input('Document title', 'document_title', '', 'text', true);
        echo '<label><span>Select file</span><input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png" required><small>Maximum 10 MB. Files are stored in a protected folder.</small></label>';
        echo '<button class="button button-primary" type="submit" ' . (!$customers ? 'disabled' : '') . '>Upload private document</button></form></section>';

        echo '<section class="f100-panel f100-table-panel"><h2>Uploaded documents</h2><div class="f100-table-scroll"><table class="widefat striped"><thead><tr><th>Document</th><th>Customer</th><th>Size</th><th>Date</th><th></th></tr></thead><tbody>';
        if (!$documents) {
            echo '<tr><td colspan="5">No document has been uploaded.</td></tr>';
        }
        foreach ($documents as $document) {
            $customer = get_userdata($document->customer_id);
            $download = F100_Portal::document_url($document->id);
            $delete = wp_nonce_url(admin_url('admin-post.php?action=f100_delete_document&document_id=' . $document->id), 'f100_delete_document_' . $document->id);
            echo '<tr><td><strong>' . esc_html($document->document_title) . '</strong><br><small>' . esc_html($document->original_name) . '</small></td><td>' . esc_html($customer ? $customer->display_name : 'Deleted user') . '</td><td>' . esc_html(size_format($document->file_size)) . '</td><td>' . esc_html(mysql2date(get_option('date_format'), $document->created_at)) . '</td><td><a href="' . esc_url($download) . '">Open</a> · <a class="f100-delete" onclick="return confirm(\'Delete this private document?\')" href="' . esc_url($delete) . '">Delete</a></td></tr>';
        }
        echo '</tbody></table></div></section></div>';
        self::render_footer();
    }

    public static function upload_document() {
        self::guard('f100_upload_document');
        if (empty($_FILES['document_file']) || !is_uploaded_file($_FILES['document_file']['tmp_name'])) {
            self::redirect('finance100-documents', 'error');
        }

        $file = $_FILES['document_file'];
        if ((int) $file['size'] > 10 * MB_IN_BYTES || (int) $file['size'] <= 0) {
            self::redirect('finance100-documents', 'error');
        }
        $allowed = array('pdf' => 'application/pdf', 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png');
        $checked = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], $allowed);
        if (empty($checked['type']) || !in_array($checked['type'], array_values($allowed), true)) {
            self::redirect('finance100-documents', 'error');
        }

        $customer_id = absint($_POST['customer_id'] ?? 0);
        $customer = get_userdata($customer_id);
        if (!$customer || !in_array('finance_customer', $customer->roles, true)) {
            self::redirect('finance100-documents', 'error');
        }
        $account_id = absint($_POST['account_id'] ?? 0);
        if ($account_id) {
            global $wpdb;
            $account_customer = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT customer_id FROM {$wpdb->prefix}f100_accounts WHERE id = %d",
                $account_id
            ));
            if ($account_customer !== $customer_id) {
                self::redirect('finance100-documents', 'error');
            }
        }

        $directory = F100_Activator::private_directory();
        wp_mkdir_p($directory);
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $stored_name = wp_generate_uuid4() . '.' . $extension;
        $destination = trailingslashit($directory) . $stored_name;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            self::redirect('finance100-documents', 'error');
        }
        @chmod($destination, 0640);

        global $wpdb;
        $result = $wpdb->insert($wpdb->prefix . 'f100_documents', array(
            'customer_id' => $customer_id,
            'account_id' => $account_id,
            'document_title' => sanitize_text_field(wp_unslash($_POST['document_title'] ?? 'Document')),
            'original_name' => sanitize_file_name($file['name']),
            'stored_name' => $stored_name,
            'mime_type' => $checked['type'],
            'file_size' => (int) $file['size'],
            'uploaded_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
        ), array('%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s'));

        if ($result === false) {
            wp_delete_file($destination);
        }
        self::redirect('finance100-documents', $result === false ? 'error' : 'document_saved');
    }

    public static function delete_document() {
        if (!current_user_can('manage_finance100')) {
            wp_die(esc_html__('Permission denied.', 'finance100'));
        }
        $document_id = absint($_GET['document_id'] ?? 0);
        check_admin_referer('f100_delete_document_' . $document_id);
        global $wpdb;
        $table = $wpdb->prefix . 'f100_documents';
        $document = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $document_id));
        if (!$document) {
            self::redirect('finance100-documents', 'error');
        }
        $path = trailingslashit(F100_Activator::private_directory()) . basename($document->stored_name);
        if (file_exists($path)) {
            wp_delete_file($path);
        }
        $wpdb->delete($table, array('id' => $document_id), array('%d'));
        self::redirect('finance100-documents', 'document_deleted');
    }
}
