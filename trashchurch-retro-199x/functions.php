<?php
/**
 * TrashChurch Retro 199X Functions
 * Version 0.2.0 (Adds Hit Counter system)
 *
 * Integrated:
 * - Optional Customizer include for selecting restricted pages (inc/customizer-restricted-pages.php)
 * - Helpers to read restricted page IDs from Customizer/theme_mod (supports legacy key 'tc_restricted_pages')
 * - template_redirect hook to send non-logged-in visitors to wp-login.php for selected pages
 *
 * Notes:
 * - This file is defensive: it checks for function existence and only requires the customizer file if present.
 * - Version constant set to 0.3.5 as requested.
 */

if ( ! defined('TR199X_VERSION') ) {
    define('TR199X_VERSION', '0.3.5');
}

if ( file_exists( get_template_directory() . '/inc/login-branding.php' ) ) {
    require_once get_template_directory() . '/inc/login-branding.php';
}

function tr199x_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('custom-logo', array(
        'height' => 180,
        'width'  => 180,
        'flex-height' => true,
        'flex-width'  => true
    ));
    add_theme_support('custom-background', array(
        'default-color' => '000010'
    ));
    add_theme_support('html5', array(
        'search-form','comment-form','comment-list','gallery','caption','style','script'
    ));

    register_nav_menus(array(
        'primary' => __('Primary Menu', 'trashchurch-retro-199x')
    ));
}
add_action('after_setup_theme','tr199x_setup');

function tr199x_widgets_init() {
    register_sidebar(array(
        'name'          => __('Retro Sidebar','trashchurch-retro-199x'),
        'id'            => 'tr199x-sidebar',
        'before_widget' => '<div class="widget %2$s" id="%1$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
}
add_action('widgets_init','tr199x_widgets_init');

function tr199x_scripts() {
    wp_enqueue_style('tr199x-style', get_stylesheet_uri(), array(), TR199X_VERSION);

    wp_enqueue_script('tr199x-main', get_template_directory_uri().'/assets/js/main.js', array(), TR199X_VERSION, true);

    if ( get_theme_mod('tr199x_enable_comet', false) ) {
        if ( ! ( wp_is_mobile() && get_theme_mod('tr199x_disable_mobile_comet', true) ) ) {
            wp_enqueue_script('tr199x-comet', get_template_directory_uri().'/assets/js/comet.js', array(), TR199X_VERSION, true);
            $count = (int) get_theme_mod('tr199x_comet_count', 10);
            $count = max(1, min(40,$count));
            $smooth = (float) get_theme_mod('tr199x_comet_smooth', 0.25);
            $smooth = max(0.05, min(0.9,$smooth));
            $img = esc_url( get_theme_mod('tr199x_comet_image', get_template_directory_uri().'/assets/img/comet.png') );
            $fade = (bool) get_theme_mod('tr199x_comet_fade', true);
            wp_localize_script('tr199x-comet','TR199X_COMET', array(
                'count'=>$count,
                'smooth'=>$smooth,
                'image'=>$img,
                'fade'=>$fade
            ));
        }
    }

    // Async Hit Counter (optional)
    if ( get_theme_mod('tr199x_enable_hit_counter', true ) && get_theme_mod('tr199x_hits_async', false ) ) {
        wp_enqueue_script(
            'tr199x-hit-counter',
            get_template_directory_uri().'/assets/js/hit-counter.js',
            array(),
            TR199X_VERSION,
            true
        );
        wp_localize_script('tr199x-hit-counter','TR199X_HITCFG', array(
            'endpoint' => esc_url_raw( rest_url('tr199x/v1/hit') ),
            'type'     => is_singular() ? 'post' : 'global',
            'postId'   => is_singular() ? get_queried_object_id() : 0,
            'target'   => get_theme_mod('tr199x_hits_async_target', '#global-hit-count'),
            'delay'    => 800
        ));
    }

    if ( get_theme_mod('tr199x_enable_midi', false) ) {
        // MIDI embed handled in footer hook
    }
}
add_action('wp_enqueue_scripts','tr199x_scripts');

function tr199x_body_classes( $classes ) {
    if ( get_theme_mod('tr199x_disable_tile', false) ) $classes[] = 'tr-no-tile';
    if ( get_theme_mod('tr199x_pixel_bg', false) ) $classes[] = 'pixelated-bg';
    if ( ! get_theme_mod('tr199x_enable_marquee', false) ) $classes[] = 'no-marquee';
    $scheme = get_theme_mod('tr199x_color_scheme','default');
    if ($scheme !== 'default') $classes[] = 'scheme-'.$scheme;
    return $classes;
}
add_filter('body_class','tr199x_body_classes');

function tr199x_excerpt_more( $more ) {
    if ( is_admin() ) return $more;
    return ' …';
}
add_filter('excerpt_more','tr199x_excerpt_more');

function tr199x_allow_marquee( $tags ) {
    $tags['marquee'] = array(
        'behavior'=>true,'direction'=>true,'scrollamount'=>true,'scrolldelay'=>true,
        'width'=>true,'height'=>true,'loop'=>true
    );
    $tags['blink'] = array();
    return $tags;
}
add_filter('wp_kses_allowed_html','tr199x_allow_marquee',10,1);

/* Customizer */
function tr199x_customize($wp) {

    $wp->add_section('tr199x_retro', array(
        'title'=>__('Retro 199X Options','trashchurch-retro-199x'),
        'priority'=>30,
        'description'=>__('Toggle maximal 1990s effects (marquee, comet cursor, MIDI, hit counter, color scheme).','trashchurch-retro-199x')
    ));

    // Color scheme
    $wp->add_setting('tr199x_color_scheme', array(
        'default'=>'default',
        'sanitize_callback'=>function($v){
            return in_array($v,array('default','green','amber','magenta')) ? $v : 'default';
        }
    ));
    $wp->add_control('tr199x_color_scheme', array(
        'label'=>__('Color Scheme','trashchurch-retro-199x'),
        'type'=>'radio',
        'section'=>'tr199x_retro',
        'choices'=>array(
            'default'=>'Neon Aqua/Magenta',
            'green'=>'Terminal Green',
            'amber'=>'Amber/Gold',
            'magenta'=>'Purple/Magenta'
        )
    ));

    // Marquee
    $wp->add_setting('tr199x_enable_marquee', array(
        'default'=>false,
        'sanitize_callback'=>'rest_sanitize_boolean'
    ));
    $wp->add_control('tr199x_enable_marquee', array(
        'label'=>__('Enable Marquee Banner','trashchurch-retro-199x'),
        'type'=>'checkbox',
        'section'=>'tr199x_retro'
    ));

    $wp->add_setting('tr199x_marquee_text', array(
        'default'=>__('WELCOME TO THE TRASHCHURCH RETRO 199X PORTAL! ADJUST EFFECTS IN CUSTOMIZER!','trashchurch-retro-199x'),
        'sanitize_callback'=>'sanitize_text_field'
    ));
    $wp->add_control('tr199x_marquee_text', array(
        'label'=>__('Marquee Text','trashchurch-retro-199x'),
        'type'=>'text',
        'section'=>'tr199x_retro'
    ));

    $wp->add_setting('tr199x_marquee_speed', array(
        'default'=>10,
        'sanitize_callback'=>'absint'
    ));
    $wp->add_control('tr199x_marquee_speed', array(
        'label'=>__('Marquee Scroll Amount (1–40)','trashchurch-retro-199x'),
        'type'=>'number',
        'section'=>'tr199x_retro',
        'input_attrs'=>array('min'=>1,'max'=>40)
    ));

    // Comet cursor
    $wp->add_setting('tr199x_enable_comet', array(
        'default'=>false,
        'sanitize_callback'=>'rest_sanitize_boolean'
    ));
    $wp->add_control('tr199x_enable_comet', array(
        'label'=>__('Enable Comet Cursor Image Trail','trashchurch-retro-199x'),
        'type'=>'checkbox',
        'section'=>'tr199x_retro'
    ));

    $wp->add_setting('tr199x_disable_mobile_comet', array(
        'default'=>true,
        'sanitize_callback'=>'rest_sanitize_boolean'
    ));
    $wp->add_control('tr199x_disable_mobile_comet', array(
        'label'=>__('Disable Comet on Mobile','trashchurch-retro-199x'),
        'type'=>'checkbox',
        'section'=>'tr199x_retro'
    ));

    $wp->add_setting('tr199x_comet_image', array(
        'default'=> get_template_directory_uri().'/assets/img/comet.png',
        'sanitize_callback'=>'esc_url_raw'
    ));
    $wp->add_control(new WP_Customize_Image_Control(
        $wp,
        'tr199x_comet_image',
        array(
            'label'=>__('Comet Image (32x32 transparent PNG)','trashchurch-retro-199x'),
            'section'=>'tr199x_retro',
            'settings'=>'tr199x_comet_image'
        )
    ));

    $wp->add_setting('tr199x_comet_count', array(
        'default'=>10,
        'sanitize_callback'=>'absint'
    ));
    $wp->add_control('tr199x_comet_count', array(
        'label'=>__('Comet Elements Count (1–40)','trashchurch-retro-199x'),
        'type'=>'number',
        'section'=>'tr199x_retro',
        'input_attrs'=>array('min'=>1,'max'=>40)
    ));

    $wp->add_setting('tr199x_comet_smooth', array(
        'default'=>0.25,
        'sanitize_callback'=>function($v){ return (float)$v; }
    ));
    $wp->add_control('tr199x_comet_smooth', array(
        'label'=>__('Comet Smooth Factor (0.05–0.9)','trashchurch-retro-199x'),
        'type'=>'number',
        'section'=>'tr199x_retro',
        'input_attrs'=>array('min'=>0.05,'max'=>0.9,'step'=>0.01)
    ));

    $wp->add_setting('tr199x_comet_fade', array(
        'default'=>true,
        'sanitize_callback'=>'rest_sanitize_boolean'
    ));
    $wp->add_control('tr199x_comet_fade', array(
        'label'=>__('Fade Opacity Down Tail','trashchurch-retro-199x'),
        'type'=>'checkbox',
        'section'=>'tr199x_retro'
    ));

    // MIDI
    $wp->add_setting('tr199x_enable_midi', array(
        'default'=>false,
        'sanitize_callback'=>'rest_sanitize_boolean'
    ));
    $wp->add_control('tr199x_enable_midi', array(
        'label'=>__('Enable Background MIDI (Autoplay)**','trashchurch-retro-199x'),
        'type'=>'checkbox',
        'section'=>'tr199x_retro',
        'description'=>__('**Browsers may block autoplay; ensure user consent.')
    ));

    $wp->add_setting('tr199x_midi_file', array(
        'default'=> get_template_directory_uri().'/assets/midi/theme.mid',
        'sanitize_callback'=>'esc_url_raw'
    ));
    $wp->add_control(new WP_Customize_Upload_Control(
        $wp,
        'tr199x_midi_file',
        array(
            'label'=>__('MIDI File (.mid)','trashchurch-retro-199x'),
            'section'=>'tr199x_retro'
        )
    ));

    // Hit Counter
    $wp->add_setting('tr199x_enable_hit_counter', array(
        'default'=>true,
        'sanitize_callback'=>'rest_sanitize_boolean'
    ));
    $wp->add_control('tr199x_enable_hit_counter', array(
        'label'=>__('Enable Hit Counter','trashchurch-retro-199x'),
        'type'=>'checkbox',
        'section'=>'tr199x_retro'
    ));

    $wp->add_setting('tr199x_hits_increment_on_load', array(
        'default'=>true,
        'sanitize_callback'=>'rest_sanitize_boolean'
    ));
    $wp->add_control('tr199x_hits_increment_on_load', array(
        'label'=>__('Increment on Page Load (server-side)','trashchurch-retro-199x'),
        'type'=>'checkbox',
        'section'=>'tr199x_retro'
    ));

    $wp->add_setting('tr199x_hits_async', array(
        'default'=>false,
        'sanitize_callback'=>'rest_sanitize_boolean'
    ));
    $wp->add_control('tr199x_hits_async', array(
        'label'=>__('Use Async (REST) Increment (for caching)','trashchurch-retro-199x'),
        'type'=>'checkbox',
        'section'=>'tr199x_retro'
    ));

    $wp->add_setting('tr199x_hits_async_target', array(
        'default'=>'#global-hit-count',
        'sanitize_callback'=>'sanitize_text_field'
    ));
    $wp->add_control('tr199x_hits_async_target', array(
        'label'=>__('Async Target Selector (number only)','trashchurch-retro-199x'),
        'type'=>'text',
        'section'=>'tr199x_retro'
    ));

    $wp->add_setting('tr199x_hits_ignore_logged_in', array(
        'default'=>true,
        'sanitize_callback'=>'rest_sanitize_boolean'
    ));
    $wp->add_control('tr199x_hits_ignore_logged_in', array(
        'label'=>__('Ignore Logged-in Users','trashchurch-retro-199x'),
        'type'=>'checkbox',
        'section'=>'tr199x_retro'
    ));

    $wp->add_setting('tr199x_hits_ignore_author', array(
        'default'=>true,
        'sanitize_callback'=>'rest_sanitize_boolean'
    ));
    $wp->add_control('tr199x_hits_ignore_author', array(
        'label'=>__('Ignore Post Author Views','trashchurch-retro-199x'),
        'type'=>'checkbox',
        'section'=>'tr199x_retro'
    ));

    $wp->add_setting('tr199x_hits_ignore_bots', array(
        'default'=>true,
        'sanitize_callback'=>'rest_sanitize_boolean'
    ));
    $wp->add_control('tr199x_hits_ignore_bots', array(
        'label'=>__('Ignore Basic Bots (UA regex)','trashchurch-retro-199x'),
        'type'=>'checkbox',
        'section'=>'tr199x_retro'
    ));

    $wp->add_setting('tr199x_hits_unique_session', array(
        'default'=>false,
        'sanitize_callback'=>'rest_sanitize_boolean'
    ));
    $wp->add_control('tr199x_hits_unique_session', array(
        'label'=>__('Count Global Hit Once per Session','trashchurch-retro-199x'),
        'type'=>'checkbox',
        'section'=>'tr199x_retro'
    ));

    $wp->add_setting('tr199x_hits_retro_digits', array(
        'default'=>true,
        'sanitize_callback'=>'rest_sanitize_boolean'
    ));
    $wp->add_control('tr199x_hits_retro_digits', array(
        'label'=>__('Use Retro GIF Digits','trashchurch-retro-199x'),
        'type'=>'checkbox',
        'section'=>'tr199x_retro'
    ));

    $wp->add_setting('tr199x_hits_label', array(
        'default'=>'Site Hits:',
        'sanitize_callback'=>'sanitize_text_field'
    ));
    $wp->add_control('tr199x_hits_label', array(
        'label'=>__('Global Counter Label','trashchurch-retro-199x'),
        'type'=>'text',
        'section'=>'tr199x_retro'
    ));

    $wp->add_setting('tr199x_post_hits_label', array(
        'default'=>'Views:',
        'sanitize_callback'=>'sanitize_text_field'
    ));
    $wp->add_control('tr199x_post_hits_label', array(
        'label'=>__('Per-Post Counter Label','trashchurch-retro-199x'),
        'type'=>'text',
        'section'=>'tr199x_retro'
    ));

    // Under construction ribbon
    $wp->add_setting('tr199x_under_construction', array(
        'default'=>true,
        'sanitize_callback'=>'rest_sanitize_boolean'
    ));
    $wp->add_control('tr199x_under_construction', array(
        'label'=>__('Show "Under Construction" Ribbon (tag: under-construction)','trashchurch-retro-199x'),
        'type'=>'checkbox',
        'section'=>'tr199x_retro'
    ));

    // Footer badges
    $wp->add_setting('tr199x_show_badges', array(
        'default'=>true,
        'sanitize_callback'=>'rest_sanitize_boolean'
    ));
    $wp->add_control('tr199x_show_badges', array(
        'label'=>__('Show Footer 88x31 Badges','trashchurch-retro-199x'),
        'type'=>'checkbox',
        'section'=>'tr199x_retro'
    ));

    // Disable tile
    $wp->add_setting('tr199x_disable_tile', array(
        'default'=>false,
        'sanitize_callback'=>'rest_sanitize_boolean'
    ));
    $wp->add_control('tr199x_disable_tile', array(
        'label'=>__('Disable Background Tile','trashchurch-retro-199x'),
        'type'=>'checkbox',
        'section'=>'tr199x_retro'
    ));

    // Pixelate tile
    $wp->add_setting('tr199x_pixel_bg', array(
        'default'=>false,
        'sanitize_callback'=>'rest_sanitize_boolean'
    ));
    $wp->add_control('tr199x_pixel_bg', array(
        'label'=>__('Pixelate Tiled Background','trashchurch-retro-199x'),
        'type'=>'checkbox',
        'section'=>'tr199x_retro'
    ));
}
add_action('customize_register','tr199x_customize');

/* If an optional Customizer helper exists, include it. This file (inc/customizer-restricted-pages.php)
 * provides a multi-select pages control and stores a theme_mod (key: 'tr199x_restricted_pages' by default).
 * The file is optional — include only when present to avoid fatal errors.
 */
$tr199x_customizer_file = get_template_directory() . '/inc/customizer-restricted-pages.php';
if ( file_exists( $tr199x_customizer_file ) ) {
    require_once $tr199x_customizer_file;
}

/* MIDI Embed */
function tr199x_midi_embed() {
    if ( get_theme_mod('tr199x_enable_midi', false) ) {
        $midi = esc_url( get_theme_mod('tr199x_midi_file', get_template_directory_uri().'/assets/midi/theme.mid') );
        echo '<div class="tr-sr">MIDI playing</div><embed src="'. $midi .'" hidden="true" autostart="true" loop="true"></embed>';
    }
}
add_action('wp_footer','tr199x_midi_embed');

/* Include Hit Counter System */
if ( file_exists( get_template_directory() . '/inc/hit-counter.php' ) ) {
    require_once get_template_directory() . '/inc/hit-counter.php';
}

/* Teamup Calendar integration */
if ( file_exists( get_template_directory() . '/inc/teamup-calendar.php' ) ) {
    require_once get_template_directory() . '/inc/teamup-calendar.php';
}

function tr_enqueue_teamup_styles() {
  wp_enqueue_style( 'tr-teamup', get_template_directory_uri() . '/assets/css/teamup.css', array(), '1.0' );
}
add_action( 'wp_enqueue_scripts', 'tr_enqueue_teamup_styles' );

/* ------------------------------------------------------------------
 * Restricted pages support + redirect logic
 * - Reads selection from theme_mod 'tr199x_restricted_pages'
 * - Also accepts legacy 'tc_restricted_pages' theme_mod or site option 'tc_restricted_pages'
 * - Safe: wrapped in function_exists guards and skips admin/REST/AJAX contexts
 * ------------------------------------------------------------------ */

if ( ! function_exists( 'tr199x_get_restricted_page_ids' ) ) {
    /**
     * Return array of restricted page IDs (ints).
     * Accepts:
     *  - theme_mod 'tr199x_restricted_pages' (preferred)
     *  - theme_mod 'tc_restricted_pages' (legacy)
     *  - option 'tc_restricted_pages' (mu-plugin migration)
     *
     * Stored value can be CSV string or array; this normalizes to array of ints.
     *
     * @return int[]
     */
    function tr199x_get_restricted_page_ids() {
        // Primary (theme Mod set via customizer include): tr199x_restricted_pages
        $raw = get_theme_mod( 'tr199x_restricted_pages', '' );

        // Fallback: legacy theme_mod used by earlier snippets (tc_restricted_pages)
        if ( empty( $raw ) ) {
            $raw = get_theme_mod( 'tc_restricted_pages', '' );
        }

        // Fallback: option (used by mu-plugin migration or site-wide storage)
        if ( empty( $raw ) ) {
            $raw = get_option( 'tc_restricted_pages', '' );
        }

        if ( empty( $raw ) ) {
            return array();
        }

        if ( is_array( $raw ) ) {
            $ids = $raw;
        } else {
            $ids = array_filter( array_map( 'trim', explode( ',', (string) $raw ) ) );
        }

        $ids = array_map( 'absint', $ids );
        $ids = array_filter( $ids );

        return $ids;
    }
}

if ( ! function_exists( 'tr199x_restrict_pages_to_wp_login' ) ) {
    add_action( 'template_redirect', 'tr199x_restrict_pages_to_wp_login', 1 );
    /**
     * Redirect non-logged-in visitors to wp-login.php when they request a restricted page.
     */
    function tr199x_restrict_pages_to_wp_login() {
        // Do not run in admin, REST, cron, or AJAX contexts
        if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) ) {
            return;
        }

        // Allow logged-in users
        if ( is_user_logged_in() ) {
            return;
        }

        $restricted_ids = tr199x_get_restricted_page_ids();
        if ( empty( $restricted_ids ) ) {
            return;
        }

        // 1) Prefer WP-aware check by ID (is_page accepts array of IDs)
        if ( is_page( $restricted_ids ) ) {
            wp_safe_redirect( wp_login_url( get_permalink() ) );
            exit;
        }

        // 2) Fallback: match the request URI path to selected pages' slugs
        $request_path = trim( parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
        $home_path    = trim( parse_url( home_url(), PHP_URL_PATH ), '/' );
        if ( $home_path && strpos( $request_path, $home_path ) === 0 ) {
            $request_path = ltrim( substr( $request_path, strlen( $home_path ) ), '/' );
        }
        $first_segment = strtok( $request_path, '/' );

        $restricted_slugs = array();
        foreach ( $restricted_ids as $id ) {
            $post = get_post( $id );
            if ( $post && ! empty( $post->post_name ) ) {
                $restricted_slugs[] = $post->post_name;
            }
        }

        if ( in_array( $request_path, $restricted_slugs, true ) || ( $first_segment && in_array( $first_segment, $restricted_slugs, true ) ) ) {
            // Preserve query string in redirect_to
            $current_url = ( (! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off') || ( isset( $_SERVER['SERVER_PORT'] ) && intval( $_SERVER['SERVER_PORT'] ) === 443 ) ) ? 'https://' : 'http://';
            $current_url .= ( $_SERVER['HTTP_HOST'] ?? '' ) . ( $_SERVER['REQUEST_URI'] ?? '' );
            wp_safe_redirect( wp_login_url( $current_url ) );
            exit;
        }

        // Optional debug trace for developers (only when WP_DEBUG)
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $queried = get_queried_object();
            error_log( sprintf(
                '[tr199x_restrict_pages] request="%s", request_path="%s", first_segment="%s", is_page=%s, queried_type=%s, queried_id=%s',
                $_SERVER['REQUEST_URI'] ?? '',
                $request_path,
                $first_segment,
                is_page() ? 'true' : 'false',
                isset( $queried->post_type ) ? $queried->post_type : 'N/A',
                isset( $queried->ID ) ? $queried->ID : 'N/A'
            ) );
        }
    }
}

/* End of functions.php */
