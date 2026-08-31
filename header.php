<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
    <div class="site-shell site-header-inner">
        <a class="site-brand" href="<?php echo esc_url(home_url('/')); ?>">
            <span class="site-brand-mark">F100</span>
            <span><strong><?php bloginfo('name'); ?></strong><small>100-Day Finance Portal</small></span>
        </a>
        <nav class="site-nav" aria-label="Primary navigation">
            <?php wp_nav_menu(array('theme_location' => 'primary', 'container' => false, 'fallback_cb' => 'finance100_theme_menu_fallback')); ?>
        </nav>
    </div>
</header>
<main>

