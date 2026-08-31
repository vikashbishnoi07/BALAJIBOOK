<?php
// Finance100 intentionally preserves finance records if the plugin is removed.
// This prevents accidental deletion of payment history and customer documents.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

