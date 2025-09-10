<?php get_header(); ?>
<div class="tr-layout" id="primary-content">
  <main>
    <div class="tr-panel tr-post">
      <h1>
        <?php
          if ( is_category() ) single_cat_title();
          elseif ( is_tag() ) single_tag_title();
          elseif ( is_author() ) { the_post(); printf(esc_html__('Author: %s','trashchurch-retro-199x'), get_the_author()); rewind_posts(); }
          elseif ( is_year() ) echo esc_html__('Year: ','trashchurch-retro-199x').get_the_date('Y');
          elseif ( is_month() ) echo esc_html__('Month: ','trashchurch-retro-199x').get_the_date('F Y');
          elseif ( is_day() ) echo esc_html__('Day: ','trashchurch-retro-199x').get_the_date('F j, Y');
          else esc_html_e('Archives','trashchurch-retro-199x');
        ?>
      </h1>
    </div>
    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
      <article <?php post_class('tr-panel tr-post'); ?>>
        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <div class="tr-meta"><?php echo get_the_date(); ?> · <?php the_author(); ?></div>
        <div><?php the_excerpt(); ?></div>
        <a class="tr-read-more" href="<?php the_permalink(); ?>"><?php esc_html_e('Read','trashchurch-retro-199x'); ?> »</a>
      </article>
    <?php endwhile;
      echo '<div class="tr-pagination">';
      the_posts_pagination();
      echo '</div>';
    else: ?>
      <div class="tr-panel"><p><?php esc_html_e('No archive items.','trashchurch-retro-199x'); ?></p></div>
    <?php endif; ?>
  </main>
  <?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>