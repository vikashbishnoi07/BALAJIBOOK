<?php

if (!defined('ABSPATH')) {
    exit;
}

class F100_Activator {
    public static function activate() {
        self::create_roles();
        self::create_tables();
        self::create_private_directory();
        self::create_pages();
        update_option('f100_version', F100_VERSION);
        flush_rewrite_rules();
    }

    private static function create_roles() {
        add_role('finance_company_member', 'Finance Company Member', array(
            'read' => true,
            'f100_view_assigned' => true,
        ));
        add_role('finance_salesperson', 'Finance Salesperson', array(
            'read' => true,
            'f100_view_assigned' => true,
        ));
        add_role('finance_customer', 'Finance Customer', array(
            'read' => true,
            'f100_view_own_account' => true,
        ));

        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('manage_finance100');
            $admin->add_cap('f100_view_assigned');
            $admin->add_cap('f100_view_own_account');
        }
    }

    private static function create_tables() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $accounts = $wpdb->prefix . 'f100_accounts';
        $payments = $wpdb->prefix . 'f100_payments';
        $documents = $wpdb->prefix . 'f100_documents';

        $account_sql = "CREATE TABLE {$accounts} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            account_number varchar(60) NOT NULL,
            customer_id bigint(20) unsigned NOT NULL,
            salesperson_id bigint(20) unsigned NOT NULL DEFAULT 0,
            member_id bigint(20) unsigned NOT NULL DEFAULT 0,
            principal_amount decimal(14,2) NOT NULL DEFAULT 0.00,
            daily_amount decimal(14,2) NOT NULL DEFAULT 0.00,
            total_due decimal(14,2) NOT NULL DEFAULT 0.00,
            start_date date NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            notes text NULL,
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY account_number (account_number),
            KEY customer_id (customer_id),
            KEY salesperson_id (salesperson_id),
            KEY member_id (member_id)
        ) {$charset};";

        $payment_sql = "CREATE TABLE {$payments} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            account_id bigint(20) unsigned NOT NULL,
            installment_no smallint(3) unsigned NOT NULL,
            due_date date NOT NULL,
            amount_due decimal(14,2) NOT NULL DEFAULT 0.00,
            amount_paid decimal(14,2) NOT NULL DEFAULT 0.00,
            payment_date date NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            remarks varchar(255) NULL,
            updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY account_installment (account_id,installment_no),
            KEY account_id (account_id),
            KEY due_date (due_date),
            KEY status (status)
        ) {$charset};";

        $document_sql = "CREATE TABLE {$documents} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) unsigned NOT NULL,
            account_id bigint(20) unsigned NOT NULL DEFAULT 0,
            document_title varchar(190) NOT NULL,
            original_name varchar(255) NOT NULL,
            stored_name varchar(255) NOT NULL,
            mime_type varchar(100) NOT NULL,
            file_size bigint(20) unsigned NOT NULL DEFAULT 0,
            uploaded_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY customer_id (customer_id),
            KEY account_id (account_id)
        ) {$charset};";

        dbDelta($account_sql);
        dbDelta($payment_sql);
        dbDelta($document_sql);
    }

    public static function private_directory() {
        $uploads = wp_upload_dir();
        return trailingslashit($uploads['basedir']) . 'finance100-private';
    }

    private static function create_private_directory() {
        $directory = self::private_directory();
        wp_mkdir_p($directory);

        $htaccess = "Options -Indexes\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n";
        if (!file_exists($directory . '/.htaccess')) {
            file_put_contents($directory . '/.htaccess', $htaccess);
        }
        if (!file_exists($directory . '/index.php')) {
            file_put_contents($directory . '/index.php', "<?php\n// Silence is golden.\n");
        }
    }

    private static function create_pages() {
        $pages = array(
            'customer_login' => array('Finance100 Customer Login', 'customer-login', '[f100_customer_login]'),
            'staff_login' => array('Finance100 Staff Login', 'staff-login', '[f100_staff_login]'),
            'customer_dashboard' => array('My Finance100 Dashboard', 'customer-dashboard', '[f100_customer_portal]'),
            'staff_dashboard' => array('Finance100 Staff Dashboard', 'staff-dashboard', '[f100_staff_portal]'),
        );

        foreach ($pages as $key => $page) {
            $existing = get_page_by_path($page[1]);
            if ($existing) {
                update_option('f100_page_' . $key, (int) $existing->ID);
                continue;
            }

            $page_id = wp_insert_post(array(
                'post_title' => $page[0],
                'post_name' => $page[1],
                'post_content' => $page[2],
                'post_status' => 'publish',
                'post_type' => 'page',
                'comment_status' => 'closed',
            ));

            if (!is_wp_error($page_id)) {
                update_option('f100_page_' . $key, (int) $page_id);
            }
        }
    }
}

