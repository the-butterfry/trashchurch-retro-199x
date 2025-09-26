<?php
/**
 * Retro 199X Hit Counter System
 * Provides global and per-post counters, widget, REST endpoint, shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class TR199X_HitCounter {

    const OPTION_TOTAL = 'tr199x_total_hits';
    const OPTION_LOCK  = 'tr199x_total_hits_lock';
    const POST_META    = '_tr199x_post_hits';
    const SESSION_KEY  = 'tr199x_counted';

    public static function init() {
        add_action( 'init', [__CLASS__, 'maybe_start_session'], 1 );
        add_action( 'wp', [__CLASS__, 'auto_increment' ], 20 );
        add_shortcode( 'hit_counter', [__CLASS__, 'shortcode_global'] );
        add_shortcode( 'post_hits', [__CLASS__, 'shortcode_post'] );
        add_action( 'rest_api_init', [__CLASS__, 'register_rest'] );
        add_action( 'widgets_init', [__CLASS__, 'register_widget'] );
    }

    public static function maybe_start_session() {
        if ( ! session_id() && ! headers_sent() ) {
            if ( get_theme_mod( 'tr199x_hits_unique_session', false ) ) {
                session_start();
            }
        }
    }

    public static function settings_enabled() : bool {
        return (bool) get_theme_mod( 'tr199x_enable_hit_counter', true );
    }

    public static function should_count_request( $post_id = 0 ) : bool {
        if ( ! self::settings_enabled() ) return false;
        if ( is_admin() || is_feed() ) return false;

        if ( get_theme_mod( 'tr199x_hits_ignore_logged_in', true ) && is_user_logged_in() ) {
            return false;
        }

        if ( $post_id && get_theme_mod( 'tr199x_hits_ignore_author', true ) && is_user_logged_in() ) {
            $post = get_post( $post_id );
            if ( $post && (int) $post->post_author === get_current_user_id() ) {
                return false;
            }
        }

        if ( get_theme_mod( 'tr199x_hits_ignore_bots', true ) ) {
            $ua = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
            if ( preg_match('/bot|crawl|spider|slurp|archive|wget|curl|python|facebookexternal/i', $ua) ) {
                return false;
            }
        }

        if ( get_theme_mod( 'tr199x_hits_unique_session', false ) ) {
            if ( isset($_SESSION[ self::SESSION_KEY ]) && $post_id === 0 ) {
                return false;
            }
        }

        return true;
    }

    public static function auto_increment() {
        if ( self::should_count_request(0) && get_theme_mod( 'tr199x_hits_increment_on_load', true ) ) {
            self::increment_global();
            if ( get_theme_mod( 'tr199x_hits_unique_session', false ) ) {
                $_SESSION[ self::SESSION_KEY ] = 1;
            }
        }

        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            if ( $post_id && self::should_count_request( $post_id ) ) {
                self::increment_post( $post_id );
            }
        }
    }

    public static function get_global_hits() : int {
        return (int) get_option( self::OPTION_TOTAL, 0 );
    }

    public static function increment_global() : int {
        $attempts = 0;
        while ( get_transient( self::OPTION_LOCK ) && $attempts < 10 ) {
            usleep( 50000 );
            $attempts++;
        }
        set_transient( self::OPTION_LOCK, 1, 2 );
        $current = self::get_global_hits();
        $current++;
        update_option( self::OPTION_TOTAL, $current, false );
        delete_transient( self::OPTION_LOCK );
        return $current;
    }

    public static function get_post_hits( $post_id ) : int {
        return (int) get_post_meta( $post_id, self::POST_META, true );
    }

    public static function increment_post( $post_id ) : int {
        $hits = self::get_post_hits( $post_id );
        $hits++;
        update_post_meta( $post_id, self::POST_META, $hits );
        return $hits;
    }

    public static function format_number( $num ) : string {
        if ( ! get_theme_mod( 'tr199x_hits_retro_digits', true ) ) {
            return number_format_i18n( $num );
        }
        $digits_dir = trailingslashit( get_template_directory_uri() ) . 'assets/digits/';
        $chars = str_split( (string) $num );
        $html = '<span class="tr-hit-digits" aria-label="'.esc_attr( $num ).'">';
        foreach ( $chars as $c ) {
            if ( ctype_digit( $c ) ) {
                $html .= '<img src="'.esc_url($digits_dir.$c.'.gif').'" alt="'.$c.'" class="tr-digit">';
            } else {
                $html .= '<span class="tr-digit-text">'.esc_html($c).'</span>';
            }
        }
        $html .= '</span>';
        return $html;
    }

    public static function render_global( $wrap = true ) : string {
        $hits = self::get_global_hits();
        $label = get_theme_mod( 'tr199x_hits_label', 'Site Hits:' );
        $out = self::format_number( $hits );
        return $wrap
            ? '<div class="tr-hit-counter"><span class="tr-hit-label">'.$label.'</span> '.$out.'</div>'
            : $out;
    }

    public static function render_post( $post_id = 0, $wrap = true ) : string {
        if ( ! $post_id ) $post_id = get_the_ID();
        $hits = self::get_post_hits( $post_id );
        $label = get_theme_mod( 'tr199x_post_hits_label', 'Views:' );
        $out = self::format_number( $hits );
        return $wrap
            ? '<div class="tr-post-hit-counter"><span class="tr-hit-label">'.$label.'</span> '.$out.'</div>'
            : $out;
    }

    public static function shortcode_global( $atts ) {
        return self::render_global();
    }

    public static function shortcode_post( $atts ) {
        $atts = shortcode_atts( ['id'=>0], $atts );
        return self::render_post( (int) $atts['id'] );
    }

    public static function register_rest() {
        register_rest_route( 'tr199x/v1', '/hit', array(
            'methods'  => 'POST',
            'callback' => [__CLASS__,'rest_hit'],
            'permission_callback' => '__return_true'
        ) );
    }

    public static function rest_hit( WP_REST_Request $req ) {
        $type = $req->get_param('type') ?: 'global';
        $post_id = (int) $req->get_param('post_id');
        if ( $type === 'post' && $post_id ) {
            if ( self::should_count_request( $post_id ) ) {
                $hits = self::increment_post( $post_id );
                return ['type'=>'post','post_id'=>$post_id,'hits'=>$hits,'counted'=>true];
            }
            return ['type'=>'post','post_id'=>$post_id,'hits'=>self::get_post_hits($post_id),'counted'=>false];
        } else {
            if ( self::should_count_request(0) ) {
                $hits = self::increment_global();
                return ['type'=>'global','hits'=>$hits,'counted'=>true];
            }
            return ['type'=>'global','hits'=>self::get_global_hits(),'counted'=>false];
        }
    }

    public static function register_widget() {
        register_widget( 'TR199X_HitCounter_Widget' );
    }
}

class TR199X_HitCounter_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'tr199x_hit_counter',
            __('Retro Hit Counter','trashchurch-retro-199x'),
            ['description'=>__('Displays the global site hit counter.','trashchurch-retro-199x')]
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'] ?? '';
        if ( ! empty( $instance['title'] ) ) {
            echo ($args['before_title'] ?? '<h3 class="widget-title">') .
                 esc_html( $instance['title'] ) .
                 ($args['after_title'] ?? '</h3>');
        }
        echo TR199X_HitCounter::render_global();
        echo $args['after_widget'] ?? '';
    }

    public function form( $instance ) {
        $title = $instance['title'] ?? __('Hit Counter','trashchurch-retro-199x'); ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:','trashchurch-retro-199x'); ?></label>
            <input class="widefat"
                   id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>"
                   type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    public function update( $new, $old ) {
        $old['title'] = sanitize_text_field( $new['title'] );
        return $old;
    }
}

/* Template tags */
function tr199x_hit_counter() {
    echo TR199X_HitCounter::render_global();
}
function tr199x_post_hits( $post_id = 0 ) {
    echo TR199X_HitCounter::render_post( $post_id );
}

TR199X_HitCounter::init();