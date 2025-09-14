<?php
/**
 * Member/Sidebar navigation logic + Member Services helpers + News overrides + Member Comet override
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Register member menu locations.
 */
function tr199x_register_member_menus() {
    register_nav_menus( array(
        'member_default'   => __( 'Member (Default)', 'trashchurch-retro-199x' ),
        'member_logged_in' => __( 'Member (Logged-in on Restricted Page)', 'trashchurch-retro-199x' ),
    ) );
}
add_action( 'after_setup_theme', 'tr199x_register_member_menus', 6 );

/**
 * True if current view is for a restricted page (based on theme’s restricted-pages system).
 */
function tr199x_is_restricted_context() {
    if ( ! function_exists( 'tr199x_get_restricted_page_ids' ) ) {
        return false;
    }
    $ids = tr199x_get_restricted_page_ids();
    if ( empty( $ids ) ) return false;
    return is_page( $ids );
}

/**
 * Custom Walker for Member sidebar menu to add toggles and ARIA attributes.
 */
class TR199X_Member_Walker extends Walker_Nav_Menu {
    public $last_parent_id = 0;

    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        $classes = empty( $item->classes ) ? array() : (array) $item->classes;
        $has_children = in_array( 'menu-item-has-children', $classes, true );

        $classes[] = 'depth-' . $depth;
        if ( $has_children && $depth === 0 ) {
            $classes[] = 'is-collapsible';
        }

        $class_names = implode( ' ', array_map( 'esc_attr', array_filter( $classes ) ) );

        // Build anchor attributes
        $atts  = '';
        if ( ! empty( $item->attr_title ) ) $atts .= ' title="' . esc_attr( $item->attr_title ) . '"';
        if ( ! empty( $item->target ) )     $atts .= ' target="' . esc_attr( $item->target ) . '"';
        if ( ! empty( $item->xfn ) )        $atts .= ' rel="' . esc_attr( $item->xfn ) . '"';

        // If top-level Custom Link with children, clicking the link should toggle only (no nav)
        $url = ! empty( $item->url ) ? $item->url : '';
        if ( $depth === 0 && $has_children && $item->type === 'custom' ) {
            $url = '#';
        }
        if ( $url ) $atts .= ' href="' . esc_url( $url ) . '"';

        $atts .= ' class="tr-member-link"';

        // Begin item
        $output .= '<li id="menu-item-' . $item->ID . '" class="' . $class_names . '" data-item-type="' . esc_attr( $item->type ) . '">';

        // Row wrapper so the toggle button can sit to the right of the link
        $output .= '<div class="tr-item-row">';

        // Link
        $title = apply_filters( 'the_title', $item->title, $item->ID );
        $output .= '<a' . $atts . '>' . $title . '</a>';

        // Toggle button for top-level items with children
        if ( $has_children && $depth === 0 ) {
            $output .= '<button class="tr-submenu-toggle" type="button" aria-expanded="false" aria-controls="member-sub-' . $item->ID . '" aria-label="' . esc_attr__( 'Expand submenu', 'trashchurch-retro-199x' ) . '">▸</button>';
            $this->last_parent_id = $item->ID;
        }

        $output .= '</div>'; // .tr-item-row
    }

    public function end_el( &$output, $item, $depth = 0, $args = null ) {
        $output .= '</li>';
    }

    public function start_lvl( &$output, $depth = 0, $args = null ) {
        $indent = str_repeat( "\t", $depth );
        // Attach id to the first-level submenu so the toggle controls it.
        $id_attr = $depth === 0 && $this->last_parent_id ? ' id="member-sub-' . $this->last_parent_id . '"' : '';
        $output .= "\n$indent<ul$id_attr class=\"sub-menu\" hidden>\n";
    }

    public function end_lvl( &$output, $depth = 0, $args = null ) {
        $indent = str_repeat( "\t", $depth );
        $output .= "$indent</ul>\n";
    }
}

/**
 * Render the member sidebar nav with the correct menu for this context.
 * - Only renders for logged-in users.
 * - Uses "member_logged_in" when on a restricted page and logged in.
 * - Otherwise uses "member_default" if assigned.
 */
function tr199x_render_member_nav() {
    // Only show to logged-in users anywhere on the site
    if ( ! is_user_logged_in() ) {
        return;
    }

    $location = 'member_default';
    if ( tr199x_is_restricted_context() ) {
        $location = 'member_logged_in';
    }

    // Only render if a menu is assigned to this location
    $locations = get_nav_menu_locations();
    if ( empty( $locations[ $location ] ) ) {
        return;
    }

    echo '<nav class="tr-member-nav widget tr-widget-member-nav" aria-label="' . esc_attr__( 'Member navigation', 'trashchurch-retro-199x' ) . '">';
    echo '<h3 class="widget-title">' . esc_html__( 'Member Services', 'trashchurch-retro-199x' ) . '</h3>';
    wp_nav_menu( array(
        'theme_location' => $location,
        'menu_class'     => 'tr-member-menu',
        'container'      => false,
        'depth'          => 0, // allow submenus (unlimited depth)
        'fallback_cb'    => false,
        'walker'         => new TR199X_Member_Walker(),
        'item_spacing'   => 'discard',
    ) );
    echo '</nav>';
}

/**
 * Enqueue a tiny inline script to enable expand/collapse behavior.
 */
function tr199x_enqueue_member_nav_toggle_script() {
    if ( ! is_user_logged_in() ) return;

    // Only if one of the member menus is assigned
    $locations = get_nav_menu_locations();
    if ( empty( $locations['member_default'] ) && empty( $locations['member_logged_in'] ) ) return;

    $js = <<<JS
document.addEventListener('DOMContentLoaded', function() {
  var nav = document.querySelector('.tr-widget-member-nav');
  if (!nav) return;

  // Initialize open state for current items
  nav.querySelectorAll('li.menu-item-has-children').forEach(function(li) {
    var btn = li.querySelector('.tr-submenu-toggle');
    var submenu = li.querySelector(':scope > ul.sub-menu');
    if (!btn || !submenu) return;

    if (li.classList.contains('current-menu-ancestor') || li.classList.contains('current-menu-item')) {
      submenu.hidden = false;
      btn.setAttribute('aria-expanded', 'true');
      btn.setAttribute('aria-label', btn.getAttribute('aria-label') === 'Expand submenu' ? 'Collapse submenu' : btn.getAttribute('aria-label'));
    }

    // Toggle button click
    btn.addEventListener('click', function(e) {
      var expanded = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', String(!expanded));
      submenu.hidden = expanded;
      btn.setAttribute('aria-label', !expanded ? 'Collapse submenu' : 'Expand submenu');
    });

    // For top-level Custom Links with children: clicking the link should only toggle
    var link = li.querySelector(':scope > .tr-item-row > a.tr-member-link');
    var isTopLevel = li.classList.contains('depth-0');
    var isCustom = li.dataset.itemType === 'custom';
    if (link && isTopLevel && isCustom) {
      var toggleOnly = function(ev) {
        ev.preventDefault();
        btn.click();
      };
      link.addEventListener('click', toggleOnly);
      link.addEventListener('keydown', function(ev) {
        if (ev.key === 'Enter' || ev.key === ' ') { toggleOnly(ev); }
      });
    }
  });
});
JS;

    // Attach after main theme script so DOM is ready and no ordering issues
    wp_add_inline_script( 'tr199x-main', $js, 'after' );
}
add_action( 'wp_enqueue_scripts', 'tr199x_enqueue_member_nav_toggle_script', 20 );

/* ------------------------------------------------------------------
 * Member Services template helpers: Post visibility badges
 * ------------------------------------------------------------------ */

if ( ! function_exists( 'tr199x_is_member_services_context' ) ) {
    function tr199x_is_member_services_context() : bool {
        return ! is_admin()
            && function_exists( 'is_page_template' )
            && is_page_template( 'page-templates/member-page.php' );
    }
}

if ( ! function_exists( 'tr199x_get_visibility_slug' ) ) {
    function tr199x_get_visibility_slug( $post = null ) : string {
        $post = get_post( $post );
        if ( ! $post || $post->post_type !== 'post' ) return '';
        if ( has_term( 'public-posts', 'category', $post ) ) return 'public';
        if ( has_term( 'member-posts', 'category', $post ) ) return 'members';
        return '';
    }
}

if ( ! function_exists( 'tr199x_get_visibility_label' ) ) {
    function tr199x_get_visibility_label( $post = null ) : string {
        $slug = tr199x_get_visibility_slug( $post );
        if ( $slug === 'public' )  return __( 'Public',  'trashchurch-retro-199x' );
        if ( $slug === 'members' ) return __( 'Members', 'trashchurch-retro-199x' );
        return '';
    }
}

if ( ! function_exists( 'tr199x_render_visibility_badge' ) ) {
    function tr199x_render_visibility_badge( $post = null ) : string {
        $slug  = is_string( $post ) ? $post : tr199x_get_visibility_slug( $post );
        if ( ! $slug ) return '';
        $label = tr199x_get_visibility_label( $post );
        return sprintf(
            '<span class="tr-cat-badge tr-cat-badge--%1$s" aria-label="%2$s">%2$s</span>',
            esc_attr( $slug ),
            esc_html( $label )
        );
    }
}

if ( ! function_exists( 'tr199x_append_visibility_badge_to_title' ) ) {
    function tr199x_append_visibility_badge_to_title( $title, $post_id = 0 ) {
        if ( is_admin() || is_feed() ) return $title;

        $in_ctx = function_exists( 'tr199x_is_member_services_context' )
            ? tr199x_is_member_services_context()
            : ( function_exists('is_page_template') && is_page_template('page-templates/member-page.php') );

        if ( ! $in_ctx ) return $title;

        $post = get_post( $post_id );
        if ( ! $post || $post->post_type !== 'post' ) return $title;

        if ( strpos( $title, 'tr-cat-badge' ) !== false ) return $title;

        $badge = tr199x_render_visibility_badge( $post );
        if ( ! $badge ) return $title;

        return $title . ' ' . $badge;
    }
    add_filter( 'the_title', 'tr199x_append_visibility_badge_to_title', 10, 2 );
}

/* ------------------------------------------------------------------
 * Unified News route + helpers
 * ------------------------------------------------------------------ */

/**
 * True when rendering the News page context.
 * Works for our rewrite (/news/?tr199x_news=1) OR a static Page with slug 'news'.
 */
function tr199x_is_news_context() : bool {
    if ( is_admin() ) return false;
    if ( intval( get_query_var( 'tr199x_news' ) ) === 1 ) return true;
    if ( function_exists( 'is_page' ) && is_page( 'news' ) ) return true;
    return false;
}

/**
 * Helper: get IDs for 'public-posts' and 'member-posts' categories.
 */
function tr199x_member_news_category_ids() : array {
    static $cache = null;
    if ( $cache !== null ) return $cache;

    $ids = array();
    $public  = get_category_by_slug( 'public-posts' );
    $members = get_category_by_slug( 'member-posts' );
    if ( $public && ! is_wp_error( $public ) )   $ids[] = (int) $public->term_id;
    if ( $members && ! is_wp_error( $members ) ) $ids[] = (int) $members->term_id;

    $cache = array_values( array_unique( array_filter( $ids ) ) );
    return $cache;
}

/**
 * Register /news/ pretty permalink (only applies when no static "news" page exists).
 */
function tr199x_register_news_rewrite() {
    // Only add the rewrite if there is no static Page with slug 'news'
    $has_news_page = function_exists( 'get_page_by_path' ) ? get_page_by_path( 'news' ) : null;
    if ( ! $has_news_page ) {
        add_rewrite_rule( '^news/?$', 'index.php?tr199x_news=1', 'top' );
    }
}
add_action( 'init', 'tr199x_register_news_rewrite' );

add_filter( 'query_vars', function( $vars ) {
    $vars[] = 'tr199x_news';
    return $vars;
} );

add_action( 'after_switch_theme', function() {
    tr199x_register_news_rewrite();
    flush_rewrite_rules();
} );

/**
 * Category archive routing:
 * - public-posts: always redirect to /news/ (unified public news)
 * - member-posts:
 *     - guests → redirect to /news/
 *     - logged-in → allow the category archive (no redirect)
 */
add_action( 'template_redirect', function() {
    if ( is_category( 'public-posts' ) ) {
        wp_safe_redirect( home_url( '/news/' ), 302 );
        exit;
    }

    if ( is_category( 'member-posts' ) ) {
        if ( ! is_user_logged_in() ) {
            wp_safe_redirect( home_url( '/news/' ), 302 );
            exit;
        }
        // Logged-in: allow member-posts archive
    }
}, 5 );

/**
 * DEFENSIVE: Prevent unwanted canonical redirects away from /category/member-posts/
 * Some plugins or core canonical logic may attempt to "correct" this URL.
 * We disable canonical redirects specifically for the member-posts category.
 */
add_filter( 'redirect_canonical', function( $redirect_url, $requested_url ) {
    // Path-based guard (works even before query is fully parsed)
    $req_path = wp_parse_url( $requested_url, PHP_URL_PATH );
    if ( is_string( $req_path ) && preg_match( '#/category/member-posts(/|$)#', $req_path ) ) {
        return false;
    }

    // Query-based guard (when main query is available)
    if ( is_category() ) {
        $obj = get_queried_object();
        if ( $obj && isset( $obj->taxonomy, $obj->slug ) && $obj->taxonomy === 'category' && $obj->slug === 'member-posts' ) {
            return false;
        }
    }
    return $redirect_url;
}, 10, 2 );

/**
 * If using the /news/ rewrite, shape the main query (post type + sticky behavior).
 * Leave category filtering to the high-priority News-context hook below.
 */
add_action( 'pre_get_posts', function( WP_Query $q ) {
    if ( is_admin() || ! $q->is_main_query() ) return;
    $is_news = (int) $q->get( 'tr199x_news' ) === 1;
    if ( ! $is_news ) return;

    $q->set( 'post_type', 'post' );
    $q->set( 'ignore_sticky_posts', true );
}, 999 );

/**
 * CRITICAL: On the News page (static Page or rewrite), force OR categories
 * for ALL post queries (including Query Loop / Latest Posts inner queries).
 */
add_action( 'pre_get_posts', function( WP_Query $q ) {
    if ( is_admin() ) return;
    if ( ! tr199x_is_news_context() ) return;

    // Only affect queries that fetch posts
    $pt = $q->get( 'post_type' );
    if ( is_array( $pt ) ) {
        if ( ! in_array( 'post', $pt, true ) ) return;
    } elseif ( $pt && $pt !== 'post' && $pt !== 'any' ) {
        return;
    }

    if ( is_user_logged_in() ) {
        $both = tr199x_member_news_category_ids();
        if ( $both ) {
            $q->set( 'tax_query', array(
                array(
                    'taxonomy' => 'category',
                    'field'    => 'term_id',
                    'terms'    => $both,
                    'operator' => 'IN',
                ),
            ) );
        }
    } else {
        $pub = get_category_by_slug( 'public-posts' );
        if ( $pub && ! is_wp_error( $pub ) ) {
            $q->set( 'tax_query', array(
                array(
                    'taxonomy' => 'category',
                    'field'    => 'term_id',
                    'terms'    => array( (int) $pub->term_id ),
                    'operator' => 'IN',
                ),
            ) );
        }
    }

    // Remove other category filters that could force AND behavior
    foreach ( array( 'cat', 'category__in', 'category__and', 'category_name' ) as $k ) {
        if ( isset( $q->query_vars[ $k ] ) ) unset( $q->query_vars[ $k ] );
    }
}, 10000 );

/**
 * On the Member Services template, force post queries to include BOTH categories (OR).
 */
add_action( 'pre_get_posts', function( WP_Query $q ) {
    if ( is_admin() ) return;
    if ( ! function_exists( 'tr199x_is_member_services_context' ) || ! tr199x_is_member_services_context() ) return;

    $pt = $q->get( 'post_type' );
    if ( is_array( $pt ) ) {
        if ( ! in_array( 'post', $pt, true ) ) return;
    } elseif ( $pt && $pt !== 'post' && $pt !== 'any' ) {
        return;
    }

    $both = tr199x_member_news_category_ids();
    if ( empty( $both ) ) return;

    $q->set( 'tax_query', array(
        array(
            'taxonomy' => 'category',
            'field'    => 'term_id',
            'terms'    => $both,
            'operator' => 'IN',
        ),
    ) );

    foreach ( array( 'cat', 'category__in', 'category__and', 'category_name' ) as $k ) {
        if ( isset( $q->query_vars[ $k ] ) ) unset( $q->query_vars[ $k ] );
    }
}, 9999 );

/* ------------------------------------------------------------------
 * Global guest visibility guard
 * - For logged-out visitors, hide "member-posts" from ALL non-singular post queries
 *   (e.g., sidebar widgets, Latest Posts/Query Loop blocks on regular pages).
 * - Skip News and Member Services contexts (they already enforce visibility).
 * ------------------------------------------------------------------ */
add_action( 'pre_get_posts', function( WP_Query $q ) {
    if ( is_admin() ) return;
    if ( is_user_logged_in() ) return;
    if ( $q->is_singular ) return; // lists only

    // Let the dedicated handlers manage these contexts
    if ( function_exists( 'tr199x_is_news_context' ) && tr199x_is_news_context() ) return;
    if ( function_exists( 'tr199x_is_member_services_context' ) && tr199x_is_member_services_context() ) return;

    // Only affect queries that can return posts
    $pt = $q->get( 'post_type' );
    if ( is_array( $pt ) ) {
        if ( ! in_array( 'post', $pt, true ) ) return;
    } elseif ( $pt && $pt !== 'post' && $pt !== 'any' ) {
        return;
    }

    $members = get_category_by_slug( 'member-posts' );
    if ( ! $members || is_wp_error( $members ) ) return;

    $tax = $q->get( 'tax_query' );
    if ( ! is_array( $tax ) ) $tax = array();

    $tax[] = array(
        'taxonomy' => 'category',
        'field'    => 'term_id',
        'terms'    => array( (int) $members->term_id ),
        'operator' => 'NOT IN',
    );

    $q->set( 'tax_query', $tax );
}, 8 );

/* ------------------------------------------------------------------
 * Block-level overrides so /news/ and Member Services blocks show the correct posts
 * without changing layout (list/grid) markup.
 * ------------------------------------------------------------------ */

function tr199x_should_override_block_query() : bool {
    return tr199x_is_news_context() || ( function_exists( 'tr199x_is_member_services_context' ) && tr199x_is_member_services_context() );
}

/**
 * Latest Posts block: enforce category IN logic by context and login.
 */
add_filter( 'block_core_latest_posts_query_args', function( $args, $attributes ) {
    if ( ! tr199x_should_override_block_query() ) return $args;

    $args['post_type'] = 'post';

    if ( is_user_logged_in() ) {
        $both = tr199x_member_news_category_ids();
        if ( $both ) $args['category__in'] = $both;
    } else {
        $pub = get_category_by_slug( 'public-posts' );
        if ( $pub && ! is_wp_error( $pub ) ) $args['category__in'] = array( (int) $pub->term_id );
    }

    unset( $args['category__and'], $args['cat'], $args['category_name'], $args['tax_query'] );
    return $args;
}, 100, 2 );

/**
 * Query Loop block: enforce OR categories by context and login.
 */
add_filter( 'query_loop_block_query_vars', function( $query_args, $block ) {
    if ( ! tr199x_should_override_block_query() ) return $query_args;

    $pt = $query_args['post_type'] ?? 'post';
    if ( is_array( $pt ) ) {
        if ( ! in_array( 'post', $pt, true ) ) return $query_args;
    } elseif ( $pt !== 'post' && $pt !== 'any' ) {
        return $query_args;
    }

    if ( is_user_logged_in() ) {
        $both = tr199x_member_news_category_ids();
        if ( $both ) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'category',
                    'field'    => 'term_id',
                    'terms'    => $both,
                    'operator' => 'IN',
                ),
            );
        }
    } else {
        $pub = get_category_by_slug( 'public-posts' );
        if ( $pub && ! is_wp_error( $pub ) ) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'category',
                    'field'    => 'term_id',
                    'terms'    => array( (int) $pub->term_id ),
                    'operator' => 'IN',
                ),
            );
        }
    }

    unset( $query_args['cat'], $query_args['category__in'], $query_args['category__and'], $query_args['category_name'] );
    return $query_args;
}, 100, 2 );

/**
 * Ensure core block styles are present so grid/list layouts render correctly.
 */
add_action( 'wp_enqueue_scripts', function () {
    // Load both core block CSS files (some themes/plugins dequeue these)
    wp_enqueue_style( 'wp-block-library' );
    wp_enqueue_style( 'wp-block-library-theme' );
}, 5 );

/* ------------------------------------------------------------------
 * Member Comet override: logged-in users get member image (GIF OK),
 * both logged-in and logged-out use 40x40 px size.
 * Important: inject "before" comet.js so it applies before the IIFE reads config.
 * ------------------------------------------------------------------ */
function tr199x_member_override_comet_config() {
    // Only run on front end when comet is enabled and script is enqueued/registered
    if ( is_admin() || ! get_theme_mod( 'tr199x_enable_comet', false ) ) return;
    if ( ! wp_script_is( 'tr199x-comet', 'enqueued' ) && ! wp_script_is( 'tr199x-comet', 'registered' ) ) return;

    $default_user_img   = trailingslashit( get_template_directory_uri() ) . 'assets/img/comet.png';
    $default_member_img = trailingslashit( get_template_directory_uri() ) . 'assets/img/comet-member.gif';

    // Logged-out fallback also respects legacy single-image if user image not set
    $img_user   = get_theme_mod( 'tr199x_comet_image_user', get_theme_mod( 'tr199x_comet_image', $default_user_img ) );
    $img_member = get_theme_mod( 'tr199x_comet_image_member', $default_member_img );

    $img = is_user_logged_in() ? $img_member : $img_user;

    // Set or override config BEFORE comet.js executes
    $js = sprintf(
        "window.TR199X_COMET = window.TR199X_COMET || {}; window.TR199X_COMET.image = %s; window.TR199X_COMET.size = 40;",
        wp_json_encode( esc_url_raw( $img ) )
    );

    wp_add_inline_script( 'tr199x-comet', $js, 'before' );
}
add_action( 'wp_enqueue_scripts', 'tr199x_member_override_comet_config', 30 );

/* ------------------------------------------------------------------
 * Dynamic News menu destination:
 * - Logged out: /news/
 * - Logged in: Member Services page (/member-services/)
 *
 * How to use:
 * 1) In Appearance → Menus, enable "CSS Classes" in Screen Options.
 * 2) Edit the "News" menu item and add the CSS class: menu-item-dynamic-news
 * 3) Save the menu. This filter swaps that item's URL by login state.
 * ------------------------------------------------------------------ */
function tr199x_dynamic_news_menu_url( $items, $args ) {
    // Resolve target URLs
    $public_url = home_url( '/news/' );

    // Try to resolve Member Services by slug, fall back to path
    $member_page = function_exists( 'get_page_by_path' ) ? get_page_by_path( 'member-services' ) : null;
    $member_url  = $member_page ? get_permalink( $member_page ) : home_url( '/member-services/' );

    $target_url = is_user_logged_in() ? $member_url : $public_url;

    foreach ( $items as $item ) {
        $classes = is_array( $item->classes ) ? $item->classes : array();
        if ( in_array( 'menu-item-dynamic-news', $classes, true ) ) {
            // Swap the link target
            $item->url = $target_url;

            // Keep "current" highlighting sensible
            if ( is_user_logged_in() ) {
                if ( function_exists( 'is_page' ) && is_page( 'member-services' ) && ! in_array( 'current-menu-item', $classes, true ) ) {
                    $item->classes[] = 'current-menu-item';
                }
            } else {
                if ( function_exists( 'is_page' ) && is_page( 'news' ) && ! in_array( 'current-menu-item', $classes, true ) ) {
                    $item->classes[] = 'current-menu-item';
                }
            }
        }
    }

    return $items;
}
add_filter( 'wp_nav_menu_objects', 'tr199x_dynamic_news_menu_url', 20, 2 );
