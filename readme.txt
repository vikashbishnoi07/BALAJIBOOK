=== Finance100 Customer Portal ===
Contributors: finance100
Tags: finance, customer portal, payment schedule, private documents, role based access
Requires at least: 6.2
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Secure, role-based finance portal with 100-day payment schedules and private customer documents.

== Description ==

Finance100 creates separate customer and staff login pages, an administrator control centre, 100 daily payment rows for every finance account, and a private document area.

Roles:

* Administrator: create and edit users, accounts, payments and documents.
* Salesperson: read-only summaries for specifically assigned accounts.
* Company member: read-only summaries for specifically assigned accounts.
* Customer: read-only access to their own account, 100-day ledger, profile and private documents.

== Installation ==

1. Upload finance100-portal.zip from WordPress > Plugins > Add New > Upload Plugin.
2. Activate the plugin.
3. Open Finance100 in the WordPress admin menu.
4. Create customer and staff logins.
5. Create a finance account. The 100-day schedule is generated automatically.
6. Add the Customer Login and Staff Login pages to your website menu.

== Security ==

Documents are stored under a protected uploads directory with randomized stored names. Every view request passes through a login, role, customer ownership and security-token check. Only PDF, JPG and PNG files up to 10 MB are accepted.

Keep WordPress, PHP, this plugin and all other plugins updated. Use HTTPS and strong passwords. Take regular off-site backups.

== Changelog ==

= 1.0.0 =
* Initial release.

