<?php
// Use attachment IDs (replace 123/124 with real IDs). Outputs responsive <img>.
$left_id  = 147; // left-racoon attachment ID
$right_id = 146; // right-racoon attachment ID
$left_img = $left_id ? wp_get_attachment_image( $left_id, 'medium', false, [ 'class' => 'tr-title-img tr-title-left', 'alt' => 'Left raccoon' ] ) : '';
$right_img = $right_id ? wp_get_attachment_image( $right_id, 'medium', false, [ 'class' => 'tr-title-img tr-title-right', 'alt' => 'Right raccoon' ] ) : '';
?>
<div class="tr-title-wrap">
  <?php echo $left_img; ?>
  <h1 class="tr-title">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
  </h1>
  <?php echo $right_img; ?>
</div
