<?php get_header(); ?>
<section class="site-main-page"><div class="site-shell"><article class="page-card"><h1><?php bloginfo('name'); ?></h1><?php if (have_posts()) : while (have_posts()) : the_post(); ?><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><?php the_excerpt(); ?><?php endwhile; else : ?><p>No content found.</p><?php endif; ?></article></div></section>
<?php get_footer(); ?>

