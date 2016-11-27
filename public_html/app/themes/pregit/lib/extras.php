<?php

namespace Roots\Sage\Extras;

use Roots\Sage\Setup;
/**
 * Add <body> classes
 */
function body_class($classes)
{
    // Add page slug if it doesn't exist
    if (is_single() || is_page() && !is_front_page()) {
        if (!in_array(basename(get_permalink()), $classes)) {
            $classes[] = basename(get_permalink());
        }
    }

    // Add class if sidebar is active
    if (Setup\display_sidebar()) {
        $classes[] = 'sidebar-primary';
    }

    return $classes;
}
add_filter('body_class', __NAMESPACE__ . '\\body_class');

/**
 * Clean up the_excerpt()
 */
function excerpt_more()
{
    return ' &hellip; <a href="' . get_permalink() . '">' . __('Continued', 'sage') . '</a>';
}
add_filter('excerpt_more', __NAMESPACE__ . '\\excerpt_more');

add_filter('searchwp_tax_term_or_logic', '__return_true');

add_filter('wp_nav_menu_menu_items', function ($items, $args) {

    $items = explode("</li>", $items);
    array_pop($items);
    array_splice($items, 2, 0, '<li class="logo"><a href="' . get_home_url() . '"><img src="' . get_stylesheet_directory_uri() . '/dist/images/pregit_logo.svg" alt="" class="logo-img"></a>');
    $items = implode("</li>", $items);
    return $items;
}, 10, 2);

/**
 *  Account link for Mobile menu
 */
function conditional_mobile_menu($items, $args)
{
    $items .= account_link_menu_item();
    return $items;
}
//add_filter('wp_nav_menu_menu-mobile_items', __NAMESPACE__ . '\\conditional_mobile_menu', 199, 2);

/**
 *  Account link funtion
 */
function account_link_menu_item()
{
    $UM_plinks = (new \UM_Permalinks)->core;
    if (is_user_logged_in()) {
        return '<li class="responsive-menu-pro-item menu-item menu-account" ><a class="responsive-menu-pro-item-link" href="' . get_permalink($UM_plinks['account']) . '">' . __('Profilo', 'sage') . ' </a></li><li class="responsive-menu-pro-item menu-item menu-logout" > <a class="responsive-menu-pro-item-link" href="' . esc_url(get_permalink($UM_plinks['logout'])) . '">' . __('Scollegati', 'sage') . '</a></li>';
    } elseif (!is_user_logged_in()) {
        return '<li class="responsive-menu-pro-item menu-item menu-login" ><a class="responsive-menu-pro-item-link" href="' . get_permalink($UM_plinks['login']) . '">' . __('Area Riservata', 'sage') . ' </a></li>';
    }
}

/**
 *  This will hide the Divi "Project" post type.
 *  Thanks to georgiee (https://gist.github.com/EngageWP/062edef103469b1177bc#gistcomment-1801080) for his improved solution.
 */
function mytheme_et_project_posttype_args($args)
{
    return array_merge($args, array(
        'public'              => false,
        'exclude_from_search' => false,
        'publicly_queryable'  => false,
        'show_in_nav_menus'   => false,
        'show_ui'             => false,
    ));
}
add_filter('et_project_posttype_args', __NAMESPACE__ . '\\mytheme_et_project_posttype_args', 10, 1);

/**
 * Restrict specific user roles from accessing profile page
 */
add_action("template_redirect", __NAMESPACE__ . '\\um_custom_page_restriction');
function um_custom_page_restriction()
{

    if (!is_user_logged_in()) {
        return;
    }

    if (um_is_core_page('login')) {
        {
            exit(wp_redirect(um_get_core_page('account'))); // redirect
            // You can also return a template file to display message
        }
    }
}

add_action('wp_loaded', __NAMESPACE__ . '\\remove_account_photo');
function remove_account_photo()
{
    remove_action('um_account_user_photo_hook__mobile', 'um_account_user_photo_hook__mobile');
    remove_action('um_account_user_photo_hook', 'um_account_user_photo_hook');
}

add_action('init', __NAMESPACE__ . '\\cptui_register_my_taxes_producer');
function cptui_register_my_taxes_producer()
{
    $labels = array(
        "name"          => __('Produttori', 'sage'),
        "singular_name" => __('Produttore', 'sage'),
    );

    $args = array(
        "label"              => __('Produttori', 'sage'),
        "labels"             => $labels,
        "public"             => true,
        "hierarchical"       => false,
        "label"              => "Produttori",
        "show_ui"            => true,
        "show_in_menu"       => true,
        "show_in_nav_menus"  => false,
        "query_var"          => true,
        "rewrite"            => array('slug' => 'producer', 'with_front' => true),
        "show_admin_column"  => false,
        "show_in_rest"       => false,
        "rest_base"          => "",
        "show_in_quick_edit" => false,
    );
    register_taxonomy("producer", array("product"), $args);

// End cptui_register_my_taxes_producer()
}

function save_producer_tax_terms($post_id)
{
    if ('product' != get_post_type($post_id) || wp_is_post_revision($post_id)) {
        return;
    }

    $taxonomy          = 'producer';
    $product_producers = get_post_meta($post_id, 'produttore')[0];
    $terms             = array();
    foreach ($product_producers as $key => $user_id) {
        $user = get_userdata($user_id);
        $term = $user->user_login;

        if (!term_exists($term, $taxonomy)) {
            wp_insert_term($term, $taxonomy, array('slug' => 'produttore_' . $user_id));
        }

        $terms[] = 'produttore_' . $user_id;
    }
    if ($terms) {
        wp_set_object_terms($post_id, $terms, $taxonomy);
    }

}
add_action('save_post', __NAMESPACE__ . '\\save_producer_tax_terms');
add_action('user_register', __NAMESPACE__ . '\\on_producer_register_add_to_tax', 10, 1);

function on_producer_register_add_to_tax($user_id)
{
    $user = get_userdata($user_id);
    if (in_array('producer', (array) $user->roles) && !term_exists($user->user_login, 'producer')) {
        wp_insert_term($user->user_login, 'producer', array('slug' => 'produttore_' . $user_id));
    }

}

function remove_producer_metaboxe_from_product_edit()
{
    remove_meta_box('tagsdiv-producer', 'product', 'side');
}
add_action('add_meta_boxes_product', __NAMESPACE__ . '\\remove_producer_metaboxe_from_product_edit');
// Disable Producer ADD UI
function remove_edit_producer_ui()
{
    if (get_current_screen()->id !== 'edit-producer') {
        return;
    }

    echo "<script type='text/javascript'>\n";
    echo '(function($) { $(function(){$("#col-left").remove();$("#col-right").css("width","100%");})})(jQuery);';
    echo "\n</script>";
}
add_action('admin_print_scripts', __NAMESPACE__ . '\\remove_edit_producer_ui', 50);

/* Finally we add some content in the tab */

add_filter('um_account_content_hook_orders', __NAMESPACE__ . '\\um_account_content_hook_orders_tab');
function um_account_content_hook_orders_tab($output)
{

    $current_user = wp_get_current_user();

    if (is_array($current_user->roles) && !in_array('customer', $current_user->roles)) {
        return $output;
    }
    $customer_orders = get_posts(apply_filters('woocommerce_my_account_my_orders_query', array(

        'meta_key'    => '_customer_user',
        'meta_value'  => get_current_user_id(),
        'post_type'   => wc_get_order_types('view-orders'),
        'post_status' => array_keys(wc_get_order_statuses()),
    )));
    if (!$customer_orders) {
        ob_start();

        echo '<div class="woocommerce-Message woocommerce-Message--info woocommerce-info">
    <a class="woocommerce-Button button" href="' . esc_url(apply_filters('woocommerce_return_to_shop_redirect', wc_get_page_permalink('shop'))) . '">' .
        __('Go Shop', 'woocommerce') . '
    </a>' .
        __('No order has been made yet.', 'woocommerce') . '</div>';
        $output .= ob_get_contents();
        ob_end_clean();
        return $output;
    }

    return $output;
}

// Register Custom Post Type
function init_distributor_docs_ct()
{

    $labels = array(
        'name'                  => _x('Distributor docs', 'Post Type General Name', 'sage'),
        'singular_name'         => _x('Distributor doc', 'Post Type Singular Name', 'sage'),
        'menu_name'             => __('Distributor docs', 'sage'),
        'name_admin_bar'        => __('Distributor doc', 'sage'),
        'archives'              => __('Item Archives', 'sage'),
        'parent_item_colon'     => __('Parent Item:', 'sage'),
        'all_items'             => __('All Items', 'sage'),
        'add_new_item'          => __('Add New Item', 'sage'),
        'add_new'               => __('Add New', 'sage'),
        'new_item'              => __('New Item', 'sage'),
        'edit_item'             => __('Edit Item', 'sage'),
        'update_item'           => __('Update Item', 'sage'),
        'view_item'             => __('View Item', 'sage'),
        'search_items'          => __('Search Item', 'sage'),
        'not_found'             => __('Not found', 'sage'),
        'not_found_in_trash'    => __('Not found in Trash', 'sage'),
        'featured_image'        => __('Featured Image', 'sage'),
        'set_featured_image'    => __('Set featured image', 'sage'),
        'remove_featured_image' => __('Remove featured image', 'sage'),
        'use_featured_image'    => __('Use as featured image', 'sage'),
        'insert_into_item'      => __('Insert into item', 'sage'),
        'uploaded_to_this_item' => __('Uploaded to this item', 'sage'),
        'items_list'            => __('Items list', 'sage'),
        'items_list_navigation' => __('Items list navigation', 'sage'),
        'filter_items_list'     => __('Filter items list', 'sage'),
    );
    $args = array(
        'label'               => __('Distributor doc', 'sage'),
        'description'         => __('Distributor doc', 'sage'),
        'labels'              => $labels,
        'supports'            => array('title', 'author', 'revisions', 'custom-fields', 'thumbnail'),
        'hierarchical'        => false,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_position'       => 5,
        'show_in_admin_bar'   => false,
        'show_in_nav_menus'   => true,
        'can_export'          => true,
        'has_archive'         => false,
        'exclude_from_search' => true,
        'publicly_queryable'  => true,
        'capability_type'     => 'post',
    );
    register_post_type('producer_orders', $args);

}
//add_action('init', __NAMESPACE__ . '\\init_distributor_docs_ct', 0);