<?php

if (!defined('ABSPATH')) {
    exit;
}

function finance100_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('search-form', 'gallery', 'caption', 'style', 'script'));
    register_nav_menus(array('primary' => __('Primary Menu', 'finance100-theme')));
}
add_action('after_setup_theme', 'finance100_theme_setup');

function finance100_theme_assets() {
    wp_enqueue_style('finance100-theme', get_stylesheet_uri(), array(), wp_get_theme()->get('Version'));
}
add_action('wp_enqueue_scripts', 'finance100_theme_assets');

function finance100_theme_menu_fallback() {
    $customer_login = (int) get_option('f100_page_customer_login');
    $staff_login = (int) get_option('f100_page_staff_login');
    echo '<ul>';
    echo '<li><a href="' . esc_url(home_url('/')) . '">Home</a></li>';
    if ($staff_login) {
        echo '<li><a href="' . esc_url(get_permalink($staff_login)) . '">Staff</a></li>';
    }
    if ($customer_login) {
        echo '<li><a class="header-login" href="' . esc_url(get_permalink($customer_login)) . '">Customer login</a></li>';
    }
    echo '</ul>';
}

