<?php

namespace Roots\Sage\Extras;

use Roots\Sage\Setup;

/**
 * Add <body> classes
 */
function body_class($classes) {
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
function excerpt_more() {
  return ' &hellip; <a href="' . get_permalink() . '">' . __('Continued', 'sage') . '</a>';
}
add_filter('excerpt_more', __NAMESPACE__ . '\\excerpt_more');

add_filter('wp_nav_menu_menu-mobile_items', __NAMESPACE__ . '\\conditional_mobile_menu', 199, 2);
function conditional_mobile_menu($items, $args){
$items.=account_link_menu_item()  ;
 return $items;
}
function account_link_menu_item(){
  $UM_plinks=(new \UM_Permalinks)->core; 
      if (is_user_logged_in()) {
      return '<li class="menu-item menu-account" ><a href="'.get_permalink($UM_plinks['account']). '">'.__('Profilo', 'sage' ).' </a></li><li class="menu-item menu-logout" > <a href="'. esc_url( get_permalink($UM_plinks['logout'])) .'">'.__('Scollegati', 'sage' ).'</a></li>';
    }
    elseif (!is_user_logged_in() ) {
      return '<li class="menu-item menu-login" ><a href="'.get_permalink($UM_plinks['login']). '">'.__('Accedi', 'sage' ).' </a></li><li class="menu-item menu-register" ><a href="'.get_permalink($UM_plinks['register']). '?action=register">'.__('Registrati', 'sage' ).' </a></li>';
    }
}
add_filter('single_product_large_thumbnail_size', __NAMESPACE__ . '\\pregit_single_product_thumb_size' );
function pregit_single_product_thumb_size(){
  return 'full';
}
add_filter( 'gform_field_value_product_id', __NAMESPACE__ . '\\my_custom_population_function' );
function my_custom_population_function( $value ) {
 global $post;
 return $post->ID;
}

function remove_producer_metaboxe_from_product_edit() {
    remove_meta_box( 'tagsdiv-producer', 'product', 'side' );
}
add_action( 'add_meta_boxes_product' , __NAMESPACE__ . '\\remove_producer_metaboxe_from_product_edit' );



function save_producer_tax_terms( $post_id ) {
  if ( 'product' != get_post_type($post_id) || wp_is_post_revision( $post_id ) ) return;
  $taxonomy = 'producer';
  $product_producers = get_post_meta( $post_id, 'produttore' )[0];
  $terms=array();
  foreach ($product_producers as $key => $user_id) {
    $user = get_userdata($user_id);
    $term = $user->user_login ;

    if( ! term_exists( $term, $taxonomy )) wp_insert_term( $term, $taxonomy, array('slug'=>'produttore_'.$user_id) );
    $terms[] =  'produttore_'.$user_id;
  }
  if($terms) wp_set_object_terms( $post_id , $terms, $taxonomy);


}
add_action( 'save_post', __NAMESPACE__ . '\\save_producer_tax_terms' );
add_action( 'user_register', __NAMESPACE__ . '\\on_producer_register_add_to_tax', 10, 1 );

function on_producer_register_add_to_tax( $user_id ) {
    $user = get_userdata($user_id);
    if ( in_array( 'producer', (array) $user->roles )  && ! term_exists($user->user_login , 'producer' ) )
        wp_insert_term( $user->user_login , 'producer', array('slug'=>'produttore_'.$user_id) );

}

add_action( 'init', __NAMESPACE__ . '\\cptui_register_my_taxes_producer' );
function cptui_register_my_taxes_producer() {
  $labels = array(
    "name" => __( 'Produttori', 'sage' ),
    "singular_name" => __( 'Produttore', 'sage' ),
    );

  $args = array(
    "label" => __( 'Produttori', 'sage' ),
    "labels" => $labels,
    "public" => true,
    "hierarchical" => false,
    "label" => "Produttori",
    "show_ui" => true,
    "show_in_menu" => true,
    "show_in_nav_menus" => false,
    "query_var" => true,
    "rewrite" => array( 'slug' => 'producer', 'with_front' => true, ),
    "show_admin_column" => false,
    "show_in_rest" => false,
    "rest_base" => "",
    "show_in_quick_edit" => false,
  );
  register_taxonomy( "producer", array( "product" ), $args );

// End cptui_register_my_taxes_producer()
}
// Disable Producer ADD UI
function remove_edit_producer_ui(){
  if(get_current_screen()->id !=='edit-producer') return;
  echo "<script type='text/javascript'>\n";
  echo '(function($) { $(function(){$("#col-left").remove();$("#col-right").css("width","100%");})
})(jQuery);';
  echo "\n</script>";
}
add_action( 'admin_print_scripts',  __NAMESPACE__ . '\\remove_edit_producer_ui',50 );

add_filter( 'gform_column_input_6_5_3', __NAMESPACE__ . '\\set_column', 10, 5 );
function set_column( $input_info, $field, $column, $value, $form_id ) {
    return array( 'type' => 'date');
}
add_filter( 'gform_column_input_6_5_1',__NAMESPACE__ . '\\set_column2', 10, 5 );
function set_column2( $input_info, $field, $column, $value, $form_id ) {
    return array( 'type' => 'post-select','args'=>array(
      'post_type' => 'product',
      'tax_query' => array(
        array(
          'taxonomy' => 'producer',
          'field' => 'slug',
          'terms' => 'produttore_'.get_current_user_id()
        )
      )
    ),'value'=>'ID',"text"=>'post_title' );
}


/**
 *  This will hide the Divi "Project" post type.
 *  Thanks to georgiee (https://gist.github.com/EngageWP/062edef103469b1177bc#gistcomment-1801080) for his improved solution.
 */
add_filter( 'et_project_posttype_args', __NAMESPACE__ . '\\mytheme_et_project_posttype_args', 10, 1 );
function mytheme_et_project_posttype_args( $args ) {
  return array_merge( $args, array(
    'public'              => false,
    'exclude_from_search' => false,
    'publicly_queryable'  => false,
    'show_in_nav_menus'   => false,
    'show_ui'             => false
  ));
}



add_filter('loop_shop_columns', __NAMESPACE__ . '\\wc_product_columns_frontend');
            function wc_product_columns_frontend() {
                global $woocommerce;

                // Default Value also used for categories and sub_categories
                $columns = 4;

                // Product List
                if ( is_tax(['product_cat','producer']) ) :
                    $columns = 3;
                endif;

 

    return $columns;


}


/**
 * Restrict specific user roles from accessing profile page
 */
add_action("template_redirect", __NAMESPACE__ . '\\um_custom_page_restriction');
function um_custom_page_restriction(){
   

    if( !is_user_logged_in()  ) return;
    if ( um_is_core_page('login')  ) {
    {
             exit( wp_redirect(       um_get_core_page('account') ) ); // redirect 
             // You can also return a template file to display message
    }
  }
}

add_filter('um_account_page_default_tabs_hook', __NAMESPACE__ . '\\form_tab', 100  );
function form_tab( $tabs ) {
  
global $ultimatemember;
  if (  $ultimatemember->user->get_role() === 'produttore'  ) $tabs[235]['order_form'] = [
    'icon' => 'um-faicon-pencil',
  'title'  => 'Order Form',
'custom' => true
  ];
    return $tabs;  
    
}
  

/* Finally we add some content in the tab */

add_filter('um_account_content_hook_orders', __NAMESPACE__ . '\\um_account_content_hook_orders_tab');
function um_account_content_hook_orders_tab( $output ){
  global $ultimatemember;
 if($ultimatemember->user->get_role() === 'produttore'){
  return $output;
 }
$customer_orders = get_posts( apply_filters( 'woocommerce_my_account_my_orders_query', array(
  
  'meta_key'    => '_customer_user',
  'meta_value'  => get_current_user_id(),
  'post_type'   => wc_get_order_types( 'view-orders' ),
  'post_status' => array_keys( wc_get_order_statuses() )
) ) );
if ( !$customer_orders ){
  ob_start();
 
   
echo '<div class="woocommerce-Message woocommerce-Message--info woocommerce-info">
    <a class="woocommerce-Button button" href="'.esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ).'">'.
       __( 'Go Shop', 'woocommerce' ).'
    </a>'.
     __( 'No order has been made yet.', 'woocommerce' ).'
  </div>';
  $output .= ob_get_contents();
  ob_end_clean();
return $output;
}
  
  return $output;
}
add_action('um_account_tab__order_form', __NAMESPACE__ . '\\um_account_tab__mytab');
function um_account_tab__mytab( $info ) {
  global $ultimatemember;
  extract( $info );

  echo  do_shortcode('[gravityform id="6" ajax="true"]' );

}
add_action( 'wp_loaded', __NAMESPACE__ . '\\remove_account_photo' );
function remove_account_photo(){
remove_action('um_account_user_photo_hook__mobile', 'um_account_user_photo_hook__mobile');
remove_action('um_account_user_photo_hook', 'um_account_user_photo_hook');
}

remove_all_actions( 'woocommerce_after_single_product_summary');
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );
remove_action( 'woocommerce_before_single_product', 'wc_print_notices', 10 );
add_action( 'woocommerce_before_main_content', 'wc_print_notices', 15 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );


add_filter( 'woocommerce_add_to_cart_fragments', __NAMESPACE__ . '\\woocommerce_header_add_to_cart_fragment' );
function woocommerce_header_add_to_cart_fragment( $fragments ) {
  ob_start();
  ?>
  <a class="cart-contents" href="<?php echo wc_get_cart_url(); ?>" title="<?php __('Carrello', 'sage'); ?>"><i class="fa fa-shopping-cart"></i><span class="wcmenucart-text">(<span class="cart-length">
  <?php echo WC()->cart->get_cart_contents_count(); ?> </span>) <?php _e('Carrello', 'sage')?></span></a>
   
  <?php
  
  $fragments['a.cart-contents'] = ob_get_clean();
  
  return $fragments;
}