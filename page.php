<?php get_header(); ?>
<section class="site-main-page"><div class="site-shell"><article class="page-card"><?php while (have_posts()) : the_post(); ?><h1><?php the_title(); ?></h1><?php the_content(); ?><?php endwhile; ?></article></div></section>
<?php get_footer(); ?>

