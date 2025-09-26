<?php
// Use attachment IDs (replace if needed). Outputs decorative <img> tags (alt="" + aria-hidden).
$left_id  = 147; // left-racoon attachment ID
$right_id = 146; // right-racoon attachment ID

$left_src  = $left_id  ? wp_get_attachment_image_url( $left_id, 'medium' )  : '';
$right_src = $right_id ? wp_get_attachment_image_url( $right_id, 'medium' ) : '';

$tagline = get_bloginfo( 'description', 'display' );
?>
<div class="tr-title-wrap">
  <?php if ( $left_src ) : ?>
    <img
      class="tr-title-img tr-title-left"
      src="<?php echo esc_url( $left_src ); ?>"
      alt=""
      aria-hidden="true"
      loading="lazy"
    />
  <?php endif; ?>

  <div class="tr-title-stack">
    <h1 class="tr-title">
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
    </h1>
    <?php if ( $tagline ) : ?>
      <p class="tr-tagline"><?php echo esc_html( $tagline ); ?></p>
    <?php endif; ?>
  </div>

  <?php if ( $right_src ) : ?>
    <img
      class="tr-title-img tr-title-right"
      src="<?php echo esc_url( $right_src ); ?>"
      alt=""
      aria-hidden="true"
      loading="lazy"
    />
  <?php endif; ?>
</div>
