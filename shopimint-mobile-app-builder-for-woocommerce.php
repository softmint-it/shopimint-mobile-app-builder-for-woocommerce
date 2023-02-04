<?php

/**
 * Plugin Name: Shopimint - Mobile App Builder For Woocommerce
 * Plugin URI: https://github.com/softmint-it/shopimint-mobile-app-builder-for-woocommerce
 * Description: Turn your woocommerce website into a fully functional elegant mobile app without any coding skills using shopimint drag and drop mobile app builder.
 * Author: softmint-it
 * Author URI: https://shopimint.com
 * Text Domain: shopimint-mobile-app-builder-for-woocommerce
 */

defined('ABSPATH') or wp_die('No script kiddies please!');

include plugin_dir_path(__FILE__) . "templates/class-mobile-detect.php";
include plugin_dir_path(__FILE__) . "templates/class-rename-generate.php";
include_once plugin_dir_path(__FILE__) . "controllers/shopimint-user.php";
include_once plugin_dir_path(__FILE__) . "controllers/shopimint-home.php";
include_once plugin_dir_path(__FILE__) . "controllers/shopimint-woo.php";
include_once plugin_dir_path(__FILE__) . "functions/index.php";
include_once plugin_dir_path(__FILE__) . "functions/utils.php";

class ShopimintCheckOut
{
    public $version = '1.0.0';

    public function __construct()
    {
        define('SHOPIMINT_CHECKOUT_VERSION', $this->version);
        define('SHOPIMINT_PLUGIN_FILE', __FILE__);

        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        if (is_plugin_active('woocommerce/woocommerce.php') == false) {
            return 0;
        }

        add_action('woocommerce_init', 'woocommerce_shopimint_init');
        function woocommerce_shopimint_init() {
            include_once plugin_dir_path(__FILE__) . "controllers/shopimint-order.php";
            include_once plugin_dir_path(__FILE__) . "controllers/shopimint-customer.php";
        }

        register_activation_hook(__FILE__, array($this, 'notify_app_builder'));
        register_activation_hook(__FILE__, array($this, 'create_custom_shopimint_table'));
		register_activation_hook(__FILE__, array($this, 'create_custom_abandoned_cart_shopimint_table'));

        // Setup Ajax action hook
        add_action('wp_ajax_shopimint_smartbanner_enabled', array($this, 'shopimint_smartbanner_enabled'));
        add_action('wp_ajax_shopimint_appname', array($this, 'shopimint_appname'));
        add_action('wp_ajax_shopimint_appdescription', array($this, 'shopimint_appdescription'));
        add_action('wp_ajax_shopimint_appicon', array($this, 'shopimint_appicon'));
        add_action('wp_ajax_shopimint_deeplink', array($this, 'shopimint_deeplink'));

        // listen changed order status to notify
        add_action('woocommerce_order_status_changed', array($this, 'track_order_status_changed'), 9, 4);
        add_action('woocommerce_checkout_update_order_meta', array($this, 'track_new_order'));
        add_action('woocommerce_rest_insert_shop_order_object', array($this, 'track_api_new_order'), 10, 4);

        $path = get_template_directory() . "/templates";
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        if (file_exists($path)) {
            $templatePath = plugin_dir_path(__FILE__) . "templates/shopimint-api-template.php";
            if (!copy($templatePath, $path . "/shopimint-api-template.php")) {
                return 0;
            }
        }
    }

    /* Smart Banner Hooks */

    function shopimint_smartbanner_enabled(){
        $updatedvalue = sanitize_text_field($_REQUEST['shopimint_smartbanner_enabled']);
        update_option("shopimint_smartbanner_enabled", $updatedvalue);
    }

    function shopimint_appname(){
        $updatedvalue = sanitize_text_field($_REQUEST['shopimint_appname']);
        update_option("shopimint_appname", $updatedvalue);
    }

    function shopimint_appdescription(){
        $updatedvalue = sanitize_text_field($_REQUEST['shopimint_appdescription']);
        update_option("shopimint_appdescription", $updatedvalue);
    }

    function shopimint_appicon(){
        $updatedvalue = sanitize_text_field($_REQUEST['shopimint_appicon']);
        update_option("shopimint_appicon", $updatedvalue);
    }

    function shopimint_deeplink(){
        $updatedvalue = sanitize_text_field($_REQUEST['shopimint_deeplink']);
        update_option("shopimint_deeplink", $updatedvalue);
    }

    /* End - Smart Banner Hooks */

    function track_order_status_changed($id, $previous_status, $next_status)
    {
        shopimint_trackOrderStatusChanged($id, $previous_status, $next_status);
    }

    function track_new_order($order_id)
    {
        shopimint_trackNewOrder($order_id);
    }

    function track_api_new_order($object)
    {
        shopimint_trackNewOrder($object->id);
    }

    function create_custom_shopimint_table()
    {
        global $wpdb;
        // include upgrade-functions for maybe_create_table;
        if (!function_exists('maybe_create_table')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'shopimint_checkout';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            `code` tinytext NOT NULL,
            `order` text NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        $success = maybe_create_table($table_name, $sql);
    }
	
	function create_custom_abandoned_cart_shopimint_table(){
        
        global $wpdb;
        // include upgrade-functions for maybe_create_table;
        if (!function_exists('maybe_create_table')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'shopimint_abandoned_carts';
        
        $sql = "CREATE TABLE $table_name (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `session_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
            `user_type` text,
            `user_id` text,
            `fcm_token` text,
            `cart_time` DATETIME,
            `last_remind_time` DATETIME,
            `remind_count` int(11) NOT NULL,
            `abandoned_cart_info` text COLLATE utf8_unicode_ci NOT NULL,
            `cart_ignored` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
            `ignored_reason` text,
            `recovered_cart` int(11) NOT NULL,
            `cart_total` int(11) NOT NULL,
            `checkout_link` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
            `user_email_id` text COLLATE utf8_unicode_ci NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        $success = maybe_create_table($table_name, $sql);

    }

    function notify_app_builder()
    {
        activateApp();
    }

}
$shopimintCheckOut = new ShopimintCheckOut();

// use JO\Module\Templater\Templater;
include plugin_dir_path(__FILE__) . "templates/class-templater.php";

add_action('plugins_loaded', 'load_shopimint_templater');
function load_shopimint_templater()
{

    // add our new custom templates
    $my_templater = new ShopimintTemplater(
        array(
            // YOUR_PLUGIN_DIR or plugin_dir_path(__FILE__)
            'plugin_directory' => plugin_dir_path(__FILE__),
            // should end with _ > prefix_
            'plugin_prefix' => 'plugin_prefix_',
            // templates directory inside your plugin
            'plugin_template_directory' => 'templates',
        )
    );
    $my_templater->add(
        array(
            'page' => array(
                'shopimint-api-template.php' => 'Page Custom Template',
            ),
        )
    )->register();
}

//custom rest api
function shopimint_users_routes()
{
    $controller = new ShopimintUserController();
    $controller->register_routes();
}

add_action('rest_api_init', 'shopimint_users_routes');
add_action('rest_api_init', 'shopimint_check_payment_routes');
function shopimint_check_payment_routes()
{
    register_rest_route('order', '/verify', array(
            'methods' => 'GET',
            'callback' => 'shopimint_check_payment',
            'permission_callback' => function () {
                return true;
            },
        )
    );
}

function shopimint_check_payment()
{
    return true;
}


// Add menu Setting
add_action('admin_menu', 'shopimint_plugin_setup_menu');

function shopimint_plugin_setup_menu()
{
    add_menu_page('Shopimint Api', 'Shopimint Api', 'manage_options', 'shopimint-plugin', 'shopimint_init', 'https://app.shopimint.com/backend/static/logofavsm.png');
}

function shopimint_init()
{
    load_template(dirname(__FILE__) . '/templates/shopimint-api-admin-page.php');
}

add_filter('woocommerce_rest_prepare_product_variation_object', 'shopimint_custom_woocommerce_rest_prepare_product_variation_object', 20, 3);
add_filter('woocommerce_rest_prepare_product_object', 'shopimint_custom_change_product_response', 20, 3);
add_filter('woocommerce_rest_prepare_product_review', 'shopimint_custom_product_review', 20, 3);
add_filter('woocommerce_rest_prepare_product_cat', 'shopimint_custom_product_category', 20, 3);

function shopimint_custom_product_category($response, $object, $request)
{
	 $id = $response->data['id'];
	 $children = get_term_children($id, 'product_cat');

    if(empty( $children ) ) {
    	$response->data['has_children'] = false;
    }else{
		$response->data['has_children'] = true;
	}
    return $response;
}

function shopimint_custom_product_review($response, $object, $request)
{
    if(is_plugin_active('woo-photo-reviews/woo-photo-reviews.php') || is_plugin_active('woocommerce-photo-reviews/woocommerce-photo-reviews.php')){
        $id = $response->data['id'];
        $image_post_ids = get_comment_meta( $id, 'reviews-images', true );
        $image_arr = array();
        if(!is_string($image_post_ids)){
            foreach( $image_post_ids as $image_post_id ) {
                $image_arr[] = wp_get_attachment_thumb_url( $image_post_id );
            }
        }
        $response->data['images'] = $image_arr;
    }
    return $response;
}

function shopimint_custom_change_product_response($response, $object, $request)
{
    return shopimint_customProductResponse($response, $object, $request);
}

function shopimint_custom_woocommerce_rest_prepare_product_variation_object($response, $object, $request)
{

    global $woocommerce_wpml;

    $is_purchased = false;
    if (isset($request['user_id'])) {
        $user_id = $request['user_id'];
        $user_data = get_userdata($user_id);
        $user_email = $user_data->user_email;
        $is_purchased = wc_customer_bought_product($user_email, $user_id, $response->data['id']);
    }
    $response->data['is_purchased'] = $is_purchased;
    if (!empty($woocommerce_wpml->multi_currency) && !empty($woocommerce_wpml->settings['currencies_order'])) {

        $price = $response->data['price'];

        foreach ($woocommerce_wpml->settings['currency_options'] as $key => $currency) {
            $rate = (float)$currency["rate"];
            $response->data['multi-currency-prices'][$key]['price'] = $rate == 0 ? $price : sprintf("%.2f", $price * $rate);
        }
    }

    return $response;
}


add_filter('woocommerce_rest_prepare_shop_order_object', 'shopimint_custom_woocommerce_rest_prepare_shop_order_object', 10, 1);
function shopimint_custom_woocommerce_rest_prepare_shop_order_object($response)
{
    if (empty($response->data) || empty($response->data['line_items'])) {
        return $response;
    }
    $api = new WC_REST_Products_Controller();
    $req = new WP_REST_Request('GET');
    $line_items = [];
    foreach ($response->data['line_items'] as $item) {
        $product_id = $item['product_id'];
        $req->set_query_params(["id" => $product_id]);
        $res = $api->get_item($req);
        if (is_wp_error($res)) {
            $item["product_data"] = null;
        } else {
            $item["product_data"] = $res->get_data();
        }
        $line_items[] = $item;

    }
    $response->data['line_items'] = $line_items;
    return $response;
}


function shopimint_register_order_refund_requested_order_status()
{
    register_post_status('wc-refund-req', array(
        'label' => esc_attr__('Refund Requested'),
        'public' => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list' => true,
        'exclude_from_search' => false,
        'label_count' => _n_noop('Refund requested <span class="count">(%s)</span>', 'Refund requested <span class="count">(%s)</span>')
    ));
}
add_action('init', 'shopimint_register_order_refund_requested_order_status');


function shopimint_add_custom_order_statuses($order_statuses) {
    // Create new status array.
    $new_order_statuses = array();
    // Loop though statuses.
    foreach ($order_statuses as $key => $status) {
        // Add status to our new statuses.
        $new_order_statuses[$key] = $status;
        // Add our custom statuses.
        if ('wc-processing' === $key) {
            $new_order_statuses['wc-refund-req'] = esc_attr__('Refund Requested');
        }
    }
    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'shopimint_add_custom_order_statuses');


function shopimint_custom_status_bulk_edit($actions)
{
    // Add order status changes.
    $actions['mark_refund-req'] = __('Change status to refund requested');

    return $actions;
}

add_filter('bulk_actions-edit-shop_order', 'shopimint_custom_status_bulk_edit', 20, 1);


/* Enable Guest Checkout on woocommerce */

add_action( 'woocommerce_init', 'force_non_logged_user_wc_session' );
function force_non_logged_user_wc_session(){ 
    if( is_user_logged_in() || is_admin() )
       return;

    if ( isset(WC()->session) && ! WC()->session->has_session() ) 
       WC()->session->set_customer_session_cookie( true ); 
}


/* Smart Banner - Add Custom Code to header PHP */

add_action('wp_head', 'custom_app_banner_css');
function custom_app_banner_css(){

    wp_register_style('smartbannerstyle', plugins_url('assets/css/smartbanner.css', SHOPIMINT_PLUGIN_FILE));
    wp_enqueue_style( 'smartbannerstyle');

    wp_register_script('smartbannerjs', plugins_url('assets/js/smartbanner.js', SHOPIMINT_PLUGIN_FILE));
    wp_enqueue_script( 'smartbannerjs');
}

add_action('wp_body_open', 'custom_app_banner');
function custom_app_banner(){
	
	global $wp_query;
	
	$currentpostid = $wp_query->post->ID;
	$page = '';
	$url = get_site_url();
	if ( $wp_query->is_page ) {
        $page = is_front_page() ? 'front' : 'page';
    } elseif ( $wp_query->is_home ) {
        $page = 'home';
    } elseif ( $wp_query->is_single ) {
        
		$page = ( $wp_query->is_attachment ) ? 'attachment' : 'single';
		if($page == 'single') {
			$product_id = $wp_query->post->ID;
			$product = wc_get_product( $product_id );
			if($product){
				$product_id = $product->id;
				$url = $url."?page=ProductViewScreen&productid=".$product_id;
			}
		}
		
    } elseif ( $wp_query->is_tax ) {
		
        $page = 'tax';
		$current_category_object = get_queried_object();
		if($current_category_object) {
			$categoryid = $current_category_object ->term_id;
			$name = $current_category_object ->name;
			$url = $url."?page=ShopScreen&catid=".$categoryid."&title=".$name;
		}
		
    } elseif ( $wp_query->is_category ) {
        $page = 'category';
    } elseif ( $wp_query->is_tag ) {
        $page = 'tag';
    } elseif ( $wp_query->is_search ) {
        $page = 'search';
    } elseif ( $wp_query->is_404 ) {
        $page = 'notfound';
    }
	
    $smartbanner_enabled = get_option("shopimint_smartbanner_enabled");
	$appname = get_option("shopimint_appname"); 
	$appdescription = get_option("shopimint_appdescription"); 
	
	$iosdeeplink = get_option("shopimint_ios_deeplink").$url;
	$androiddeeplink = get_option("shopimint_android_deeplink").$url;
	
	$appicon = get_option("shopimint_appicon");

    $bgcolor = get_option("smartbanner_bgcolor");
    $color = get_option("smartbanner_textcolor");

    if($smartbanner_enabled == '1') {
        ?>
            <div id="smartbannerdiv" class="smartbanner hidden" style="background-color:<?=$bgcolor;?>; ">
                <div class="smartbanner-container">
                    <a href="#" id="smb-close" class="smartbanner-close" onClick="hidebanner()">&times;</a>
                    <span class="smartbanner-icon" style="background-image: url('<?=$appicon;?>');"></span>
                    <div class="smartbanner-info">
                        <div class="smartbanner-title" style="color:<?=$color;?>; "><?=$appname;?></div>
                        <div style="color:<?=$color;?>;" ><?=$appdescription;?></div>
                    </div>
                    <a id="iosbutton" href="<?=$iosdeeplink;?>" target="_blank" class="smartbanner-button">
                        <span class="smartbanner-button-text">Get it IOS</span>
                    </a>
                    <a id="androidbutton" href="<?=$androiddeeplink;?>" target="_blank" class="smartbanner-button">
                        <span class="smartbanner-button-text">Get it Android</span>
                    </a>
                </div>
            </div>
        <?php
    }

};


// Display Fields
add_action( 'woocommerce_product_options_general_product_data', 'woo_add_ar_fields' );

// Save Fields
add_action( 'woocommerce_process_product_meta', 'woo_add_ar_fields_save' );

function woo_add_ar_fields() {

  global $woocommerce, $post;
  echo "<h3 style='padding-left:10px; color:#00008B;'>Shopimint Augmented Reality</h3>";
  echo '<div class="options_group">';
  
  // AR GLB - ANDROID
  woocommerce_wp_text_input( 
		array( 
			'id'          => '_shopimint_ar_glb', 
			'label'       => __( 'GLB URL ( For Android Devices )', 'woocommerce' ), 
			'placeholder' => 'http://',
			'desc_tip'    => 'true',
			'description' => __( 'Enter GLB file URL Here', 'woocommerce' ) 
		)
  );
	
  // AR USDZ - IOS
  woocommerce_wp_text_input( 
		array( 
			'id'          => '_shopimint_ar_usdz', 
			'label'       => __( 'USDZ URL ( For IOS Devices )', 'woocommerce' ), 
			'placeholder' => 'http://',
			'desc_tip'    => 'true',
			'description' => __( 'Enter USDZ file URL Here', 'woocommerce' ) 
		)
  );
  // Custom fields will be created here...
  echo '</div>';
	
}

function woo_add_ar_fields_save( $post_id ){
	
	// AR GLB - ANDROID
	$woocommerce_text_field = $_POST['_shopimint_ar_glb'];
	if( !empty( $woocommerce_text_field ) )
		update_post_meta( $post_id, '_shopimint_ar_glb', esc_attr( $woocommerce_text_field ) );
	
	// AR GLB - IOS
	$woocommerce_text_field = $_POST['_shopimint_ar_usdz'];
	if( !empty( $woocommerce_text_field ) )
		update_post_meta( $post_id, '_shopimint_ar_usdz', esc_attr( $woocommerce_text_field ) );

}