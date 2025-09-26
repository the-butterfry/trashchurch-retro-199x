<?php
/**
 * Customizer: Restricted Pages selector for TrashChurch Retro 199X
 *
 * Adds a multi-select control to the Customizer (section: tr199x_retro)
 * that lets admins pick pages to protect. The selection is stored in the
 * theme_mod 'tr199x_restricted_pages' as a comma-separated list of page IDs.
 *
 * This file is safe to include on the front-end; it only registers controls
 * when the Customizer API is available (during customize_register).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize the restricted pages setting.
 * Accepts array of IDs or CSV string; returns CSV of positive ints.
 *
 * @param mixed $input
 * @return string Comma-separated list of IDs (e.g. "12,34")
 */
if ( ! function_exists( 'tr199x_sanitize_restricted_pages' ) ) {
	function tr199x_sanitize_restricted_pages( $input ) {
		$ids = array();

		if ( is_array( $input ) ) {
			$ids = $input;
		} elseif ( is_string( $input ) && strlen( $input ) ) {
			$ids = array_filter( array_map( 'trim', explode( ',', $input ) ) );
		}

		$ids = array_map( 'absint', $ids );
		$ids = array_filter( $ids ); // remove zeros

		return implode( ',', $ids );
	}
}

/**
 * Register the setting + control with the Customizer.
 * Defines the control class only when the Customizer API is loaded.
 */
if ( ! function_exists( 'tr199x_register_restricted_pages_customizer' ) ) {
	function tr199x_register_restricted_pages_customizer( $wp_customize ) {
		// Ensure Customizer API is available (defensive)
		if ( ! class_exists( 'WP_Customize_Control' ) ) {
			return;
		}

		// Define the multi-page select control only if it doesn't exist already
		if ( ! class_exists( 'TR199X_Multi_Page_Select_Control' ) ) {
			class TR199X_Multi_Page_Select_Control extends WP_Customize_Control {
				public $type = 'multiselect-pages';

				public function render_content() {
					$pages = get_pages( array( 'sort_column' => 'post_title' ) );

					$value = $this->value();
					$selected = array();

					if ( is_array( $value ) ) {
						$selected = array_map( 'strval', $value );
					} elseif ( is_string( $value ) && strlen( $value ) ) {
						$selected = array_map( 'strval', array_filter( array_map( 'trim', explode( ',', $value ) ) ) );
					}
					?>
					<label>
						<?php if ( ! empty( $this->label ) ) : ?>
							<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
						<?php endif; ?>

						<?php if ( ! empty( $this->description ) ) : ?>
							<span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
						<?php endif; ?>

						<select multiple="multiple" style="width:100%; min-height:160px;" <?php $this->link(); ?> >
							<?php foreach ( $pages as $p ) : ?>
								<option value="<?php echo esc_attr( $p->ID ); ?>" <?php echo in_array( (string) $p->ID, $selected, true ) ? 'selected' : ''; ?>>
									<?php echo esc_html( $p->post_title . ' — /' . $p->post_name . '/' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
					<?php
				}
			}
		}

		// Add the setting (CSV of IDs) and control to the existing section 'tr199x_retro'
		$wp_customize->add_setting( 'tr199x_restricted_pages', array(
			'default'           => '',
			'sanitize_callback' => 'tr199x_sanitize_restricted_pages',
			'transport'         => 'refresh',
		) );

		$wp_customize->add_control( new TR199X_Multi_Page_Select_Control( $wp_customize, 'tr199x_restricted_pages', array(
			'label'       => __( 'Restricted pages (select multiple)', 'trashchurch-retro-199x' ),
			'section'     => 'tr199x_retro',
			'settings'    => 'tr199x_restricted_pages',
			'description' => __( 'Choose pages that require login — non-logged-in visitors will be redirected to the WP login page.', 'trashchurch-retro-199x' ),
		) ) );
	}
	add_action( 'customize_register', 'tr199x_register_restricted_pages_customizer', 5 );
}
