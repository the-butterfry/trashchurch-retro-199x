<?php get_header(); ?>
<div class="tr-layout" id="primary-content">
  <main>
    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
      <article <?php post_class('tr-panel tr-post'); ?>>
        <h1><?php the_title(); ?></h1>
        <div class="tr-content-body">
          <?php the_content(); ?>
          <?php wp_link_pages(); ?>
        </div>
        <?php if ( function_exists('tr199x_post_hits') && get_theme_mod('tr199x_enable_hit_counter', true ) ) tr199x_post_hits(); ?>
        <?php if ( comments_open() || get_comments_number() ) comments_template(); ?>
      </article>
    <?php endwhile; endif; ?>
  </main>
  <?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>