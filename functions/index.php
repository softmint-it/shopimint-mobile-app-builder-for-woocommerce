<?php

function activateApp()
{
    $website = get_home_url();
    $adminemail = get_bloginfo('admin_email');
    $response = wp_remote_post("https://app.shopimint.com/backend/api/app/plugin/active", array(
        'method' => 'POST',
        'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
		'timeout'     => 45,
        'sslverify'   => false,
        'body' => json_encode(array(
            'email' => $adminemail,
            'website' => $website,
            'platform' => 'wordpress',
            'plugin' => true
        )),
    ));
	update_option("shopimint_plugin_enabled_time", $response);
    return true;
}

function deactiveShopimintApi() {

    $website = get_home_url();
    $adminemail = get_bloginfo('admin_email');
    $response = wp_remote_post("https://app.shopimint.com/backend/api/app/plugin/deactive", array(
        'method' => 'POST',
        'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
		'timeout'     => 45,
        'sslverify'   => false,
        'body' => json_encode(array(
            'email' => $adminemail,
            'website' => $website,
            'platform' => 'wordpress',
            'plugin' => false
        )),
    ));
	update_option("shopimint_plugin_removed_time", $response);
    return true;
    
}

function shopimint_trackNewOrder($id) 
{
    $order = wc_get_order($id);
    $userId = $order->get_customer_id();

    $user = get_userdata($userId);
    $deviceToken = get_user_meta($userId, 'fcm_token', true);

    $automationtype = 'order_success';
    $props = json_encode(array(
        "name"=> $user->display_name,
        "orderId"=> $id, 
        "prevStatus"=> wc_get_order_status_name( $previous_status ),
        "nextStatus"=> wc_get_order_status_name( $next_status ),
        "product"=> '',
        "cartcount"=> ''
    ));
    sendNotification($deviceToken , $props, $automationtype);
}

function shopimint_trackOrderStatusChanged($id, $previous_status, $next_status)
{
    $order = wc_get_order($id);
    $userId = $order->get_customer_id();

    $user = get_userdata($userId);
    $deviceToken = get_user_meta($userId, 'fcm_token', true);
    
    $automationtype = 'order_delivery';
    $props = json_encode(array(
        "name"=> $user->display_name,
        "orderId"=> $id, 
        "prevStatus"=> wc_get_order_status_name( $previous_status ),
        "nextStatus"=> wc_get_order_status_name( $next_status ),
        "product"=> '',
        "cartcount"=> ''
    ));
    //sendNotification($deviceToken , $props, $automationtype);
    abandonedCartNotification();
}

function abandonedCartNotification($step = 0) 
{
    global $wpdb;
    $table_name = $wpdb->prefix . "shopimint_abandoned_carts";
	$results = $wpdb->get_results('SELECT * FROM '.$table_name.' WHERE cart_ignored = "0" AND recovered_cart = "0" AND remind_count = "'.$step.'" ');
    $deviceToken = '';
	foreach( $results as $result ) {
		update_option("shopimint_plugin_removed_time", "UPDATE `$table_name` SET remind_count = 1 , last_remind_time = NOW() WHERE id = $result->id ");
        
        $deviceToken = $result->fcm_token;
        
        $updatereminder_sql = "UPDATE ".$table_name." SET remind_count = 1, last_remind_time = NOW() WHERE id = $result->id ";
		$wpdb->get_results($updatereminder_sql);
        
        $automationtype = 'abandoned_cart_first';
        $user_id = $result->user_id;
        $username = '';
        if($user_id != 0) {
            $user = get_userdata($userId);
            $username = $user->display_name;
        }
        $props = json_encode(array(
            "name"=> $username,
            "orderId"=> '', 
            "prevStatus"=> '',
            "nextStatus"=> '',
            "product"=> '',
            "cartcount"=> '', //$result->cart_total,
        ));
        sendNotification($deviceToken , $props, $automationtype);
    }
}

function sendNotification($deviceToken , $props, $automationtype) {

    $appid = get_option("shopimint_appid");

    // $deviceToken = 'eNwyOfRuoEthjDagSQ0-o6:APA91bH0mGtRae0nXfaARizPpaULopTLgjzXAZHV2OrOoJaQSiZIY_mgxvPqdR_jH9HECzHG-L3dNUHDqMmU1BiV4kAyOzKuupBvi4ssaMb76uEZwn4unvRgpIHAO3-mZFXQC5-SFlRn';
    // $appid = '311aeb3e-12bf-c2a4-7f00-5bc491af3683';

    if(isset($deviceToken) && $deviceToken != false && $deviceToken != '') {

        $response = wp_remote_post('https://app.shopimint.com/backend/api/app/MarketingAutomation/send', array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
            'timeout'     => 45,
            'sslverify'   => false,
            'body' => json_encode(array(
                'to' => $deviceToken,
                'automation_type' => $automationtype,
                'app_id' => $appid,
                'props' => $props
            )),
        ));

        update_option("shopimint_plugin_removed_time", $response);

        $statusCode = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        return $statusCode == 200;

    }else {
        return false;
    }
}

function getProductAddOns($categories)
{
    $addOns = [];
    if (is_plugin_active('woocommerce-product-addons/woocommerce-product-addons.php')) {
        $addOnGroup = WC_Product_Addons_Groups::get_all_global_groups();
        foreach ($addOnGroup as $addOn) {
            $cateIds = array_keys($addOn["restrict_to_categories"]);
            if (count($cateIds) == 0) {
                $addOns = array_merge($addOns, $addOn["fields"]);
                break;
            }
            $isSupported = false;
            foreach ($categories as $cate) {
                if (in_array($cate["id"], $cateIds)) {
                    $isSupported = true;
                    break;
                }
            }
            if ($isSupported) {
                $addOns = array_merge($addOns, $addOn["fields"]);
            }
        }
    }

    return $addOns;
}


function shopimint_parseMetaDataForBookingProduct($product)
{
    if (is_plugin_active('woocommerce-appointments/woocommerce-appointments.php')) {
        //add meta_data to $_POST to use for booking product
        $meta_data = [];
        foreach ($product["meta_data"] as $key => $value) {
            if ($value["key"] == "staff_ids" && isset($value["value"])) {
                $staffs = is_array($value["value"]) ? $value["value"] : json_decode($value["value"], true);
                if (count($staffs) > 0) {
                    $meta_data["wc_appointments_field_staff"] = sanitize_text_field($staffs[0]);
                }
            } elseif ($value["key"] == "product_id") {
                $meta_data["add-to-cart"] = sanitize_text_field($value["value"]);
            } else {
                $meta_data[$value["key"]] = sanitize_text_field($value["value"]);
            }
        }
        $_POST = $meta_data;
    }
}

function checkPHP8()
{
    return version_compare(phpversion(), '8.0.0') >= 0;
}

function shopimint_customProductResponse($response, $object, $request)
{
    global $woocommerce_wpml;

    $is_purchased = false;
    if (isset($request['user_id'])) {
        $user_id = $request['user_id'];
        $user_data = get_userdata($user_id);
        if ($user_data) {
            $user_email = $user_data->user_email;
            $is_purchased = wc_customer_bought_product($user_email, $user_id, $response->data['id']);
        }
    }
    $response->data['is_purchased'] = $is_purchased;

    if (!empty($woocommerce_wpml->multi_currency) && !empty($woocommerce_wpml->settings['currencies_order'])) {

        $type = $response->data['type'];
        $price = $response->data['price'];

        foreach ($woocommerce_wpml->settings['currency_options'] as $key => $currency) {
            $rate = (float)$currency["rate"];
            $response->data['multi-currency-prices'][$key]['price'] = $rate == 0 ? $price : sprintf("%.2f", $price * $rate);
        }
    }

    $product = wc_get_product($response->data['id']);

    /* Update price for product variant */
    if ($product->is_type('variable')) {
        $prices = $product->get_variation_prices();
        if (!empty($prices['price'])) {
            $response->data['price'] = current($prices['price']);
            $response->data['regular_price'] = current($prices['regular_price']);
            $response->data['sale_price'] = current($prices['sale_price']);
            $response->data['min_price'] = $product->get_variation_price();
            $response->data['max_price'] = $product->get_variation_price('max');
            
            if(!$response->data['min_price']){
                $response->data['min_price'] = '0';
            }
            if(!$response->data['max_price']){
                $response->data['max_price'] = '0';
            }
            $variations = $response->data['variations'];
            $variation_arr = array();
            foreach($variations as $variation_id){
                $variation_data = array();
                $variation_p = new WC_Product_Variation($variation_id);
                $variation_data['id'] = $variation_id;
                $variation_data['product_id'] = $product->get_id();
                $variation_data['price'] = $variation_p->get_price();
                $variation_data['regular_price'] = $variation_p->get_regular_price() ;
                $variation_data['sale_price'] =$variation_p->get_sale_price() ;
                $variation_data['date_on_sale_from'] = $variation_p->get_date_on_sale_from();
                $variation_data['date_on_sale_to'] = $variation_p->get_date_on_sale_to();
                $variation_data['on_sale'] = $variation_p->is_on_sale();
                $variation_data['in_stock'] =$variation_p->is_in_stock() ;
                $variation_data['stock_quantity'] = $variation_p->get_stock_quantity();
                $variation_data['stock_status'] = $variation_p->get_stock_status();
                $feature_image = wp_get_attachment_image_src( $variation_p->get_image_id(), 'single-post-thumbnail' );
                $variation_data['feature_image'] = $feature_image ? $feature_image[0] : null;
        
                $attr_arr = array();
                $variation_attributes = $variation_p->get_attributes();
                foreach($variation_attributes as $k=>$v){
                    $attr_data = array();
                    $attr_data['name'] = $k;
                    $attr_data['slug'] = $v;
                    $meta = get_post_meta($variation_id, 'attribute_'.$k, true);
                    $term = get_term_by('slug', $meta, $k);
                    $attr_data['attribute_name'] = $term == false ? null : $term->name;
                    $attr_arr[]=$attr_data;
                }
                $variation_data['attributes_arr'] = $attr_arr;
                $variation_arr[]=$variation_data;
            }
            $response->data['variation_products'] = $variation_arr;
        }
    }

    $attributes = $product->get_attributes();
    $attributesData = [];
    foreach ($attributes as $attr) {
        $check = $attr->is_taxonomy();
        if ($check) {
            $taxonomy = $attr->get_taxonomy_object();
            $label = $taxonomy->attribute_label;
        } else {
            $label = $attr->get_name();
        }
        $attrOptions = wc_get_product_terms($response->data['id'], $attr["name"]);
        $attr["options"] = empty($attrOptions) ? array_map(function ($v){
            return ['name'=>$v, 'slug' => $v];
        },$attr["options"]) : $attrOptions;
        $attributesData[] = array_merge($attr->get_data(), ["label" => $label]);
    }
    $response->data['attributesData'] = $attributesData;

    /* Product Add On */
    $addOns = getProductAddOns($response->data["categories"]);
    $meta_data = $response->data['meta_data'];
    $new_meta_data = [];
    foreach ($meta_data as $meta_data_item) {
        if ($meta_data_item->get_data()["key"] == "_product_addons") {
            if(class_exists('WC_Product_Addons_Helper')){
                $product_addons = WC_Product_Addons_Helper::get_product_addons( $response->data['id'], false );
                $meta_data_item->__set("value", count($addOns) == 0 ? $product_addons : array_merge($product_addons, $addOns));
                $meta_data_item->apply_changes();
            }
        }
        $new_meta_data[] = $meta_data_item;
    }
    $response->data['meta_data'] = $new_meta_data;

    /* Product Booking */
    if (is_plugin_active('woocommerce-appointments/woocommerce-appointments.php')) {
        $terms = wp_get_post_terms($product->id, 'product_type');
        if ($terms != false && count($terms) > 0 && $terms[0]->name == 'appointment') {
            $response->data['type'] = 'appointment';
        }
    }
	
	/* Augmented Reality */
	$response->data['ar_android'] = false;
	$response->data['ar_android_file'] = "";
	$response->data['ar_ios'] = false;
	$response->data['ar_ios_file'] = "";
	$glb = get_post_meta( $product->id, '_shopimint_ar_glb', [] );
	$usdz = get_post_meta( $product->id, '_shopimint_ar_usdz', [] );
	foreach($glb as $g){
		$response->data['ar_android'] = true;
		$response->data['ar_android_file'] = $g;
	}
	foreach($usdz as $u){
		$response->data['ar_ios'] = true;
		$iosurl = $u."#callToAction=Add%20to%20cart&checkoutTitle=".$product->name."&checkoutSubtitle=".$product->name."&price=".$product->price;
		$iosurl = preg_replace('/\s+/', '', $iosurl);
		$response->data['ar_ios_file'] = $iosurl;
		
	}
	
	/* Shopimint attributes */
	$attributes = $product->get_attributes();
    $shopimint_attributes = [];
	
	if($attributes) {
		foreach ($attributes as $attr) {
			$attribute_data = $attr->get_data();
			$check = $attr->is_taxonomy();

			$attrOptions = wc_get_product_terms($response->data['id'], $attr["name"]);
			$attribute_options = [];
			foreach ($attrOptions as $option) {
				array_push($attribute_options, $option->name);
				$attr["options"] = $attribute_options;
			};

			if ($check) {
				$taxonomy = $attr->get_taxonomy_object();
				$label = $taxonomy->attribute_label;
			} else {
				$label = $attr->get_name();
			}

			$shopimint_attributes[] = array_merge([
                //"data" => $attribute_data,
				"id" => $attribute_data['id']."",
				"name" => $label,
				"title" => $label,
				"position" => $attribute_data['position'],
				"visible" => $attribute_data['visible'],
				"variation" => $attribute_data['variation'],
				"options" => $attr["options"]
			]);
		}
	}
    $response->data['shopimint_attributes'] = $shopimint_attributes;
	
	/* Shopimint variations */
	$shopimint_variations = [];
	
	if ($product->is_type('variable')) {
        $variations = $response->data['variations'];
		$variation_arr = array();

		foreach($variations as $variation_id){
			$variation_data = array();
			$variation_p = new WC_Product_Variation($variation_id);
			$attr_arr = array();

			$variation_attributes = $variation_p->get_attributes();
			foreach($variation_attributes as $k=>$v){
				$attr_arr[]= $v;
			}

			$variation_data['id'] = $variation_id;
			$variation_data['product_id'] = $product->get_id();
			$variation_data['price'] = $variation_p->get_price();
			$variation_data['variant'] = $attr_arr; //$variation_attributes; 
			$variation_data['image'] = $feature_image ? $feature_image[0] : null;
			$variation_data['regular_price'] = $variation_p->get_regular_price();
			$variation_data['sale_price'] = ($variation_p->get_sale_price() == "") ? $variation_p->get_regular_price() : $variation_p->get_sale_price() ;
			$variation_data['variantstr'] = 'Black-256GB';
			$variation_data['in_stock'] =$variation_p->is_in_stock() ;

			$variation_arr[]=$variation_data;
		}
    }
	$response->data['shopimint_variations'] = $variation_arr;
	
    return $response;
	
}

function shopimint_getLangCodeFromConfigFile ($file) {
    return str_replace('config_', '', str_replace('.json', '',$file));
}

function shopimint_generateCookieByUserId($user_id, $seconds = 1209600){
    $expiration = time() + 365 * DAY_IN_SECONDS;
    $cookie = wp_generate_auth_cookie($user_id, $expiration, 'logged_in');
    return $cookie;
}

function shopimint_validateCookieLogin($cookie){
    
    if(isset($cookie) && strlen($cookie) > 0){
        $userId = wp_validate_auth_cookie($cookie, 'logged_in');
        if($userId == false){
            return new WP_Error("invalid_login", "Your session has expired. Please logout and login again.", array('status' => 401));
        }else{
            return $userId;
        }
    }else{
        return new WP_Error("invalid_login", "Cookie is required", array('status' => 401));
    }
}
?>