<?php
require_once(__DIR__ . '/shopimint-base.php');

class ShopimintUserController extends ShopimintBaseController
{

    public function __construct()
    {
        $this->namespace = 'api/flutter_user';
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/reset-password', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'reset_password'),
                'permission_callback' => function () {
                    return parent::checkApiPermission();
                }
            ),
        ));

        register_rest_route($this->namespace, '/sign_up', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'register'), 
                'permission_callback' => function () {
                    return parent::checkApiPermission();
                }
            ),
        ));

        register_rest_route($this->namespace, '/generate_auth_cookie', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'generate_auth_cookie'),
                'permission_callback' => function () {
                    return parent::checkApiPermission();
                }
            ),
        ));
		
		register_rest_route($this->namespace, '/get_userallinfo_byid', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_userallinfo_byid'),
                'permission_callback' => function () {
                    return parent::checkApiPermission();
                }
            ),
        ));
		
        register_rest_route($this->namespace, '/fb_connect', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'fb_connect'),
                'permission_callback' => function () {
                    return parent::checkApiPermission();
                }
            ),
        ));

        register_rest_route($this->namespace, '/apple_login', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'apple_login'),
                'permission_callback' => function () {
                    return parent::checkApiPermission();
                }
            ),
        ));

        register_rest_route($this->namespace, '/google_login', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'google_login'),
                'permission_callback' => function () {
                    return parent::checkApiPermission();
                }
            ),
        ));

        register_rest_route($this->namespace, '/get_currentuserinfo', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_currentuserinfo'),
                'permission_callback' => function () {
                    return parent::checkApiPermission();
                }
            ),
        ));

        register_rest_route($this->namespace, '/get_points', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_points'),
                'permission_callback' => function () {
                    return parent::checkApiPermission();
                }
            ),
        ));

        register_rest_route($this->namespace, '/update_user_profile', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'update_user_profile'),
                'permission_callback' => function () {
                    return parent::checkApiPermission();
                }
            ),
        ));

        register_rest_route($this->namespace, '/get_currency_rates', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_currency_rates'),
                'permission_callback' => function () {
                    return parent::checkApiPermission();
                }
            ),
        ));

        register_rest_route($this->namespace, '/get_countries', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_countries'),
                'permission_callback' => function () {
                    return parent::checkApiPermission();
                }
            ),
        ));

        register_rest_route($this->namespace, '/get_states', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_states'),
                'permission_callback' => function () {
                    return parent::checkApiPermission();
                }
            ),
        ));

        register_rest_route($this->namespace, '/delete_account', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'delete_account'),
                'permission_callback' => function () {
                    return parent::checkApiPermission();
                }
            ),
        ));
    }

    public function reset_password()
    {
        $json = file_get_contents('php://input');
        $params = json_decode($json, TRUE);
        $usernameReq = $params["user_login"];

        $errors = new WP_Error();
        if (empty($usernameReq) || !is_string($usernameReq)) {
            return parent::sendError("empty_username", "Enter a username or email address.", 400);
        } elseif (strpos($usernameReq, '@')) {
            $user_data = get_user_by('email', trim(wp_unslash($usernameReq)));
            if (empty($user_data)) {
                return parent::sendError("invalid_email", "There is no account with that username or email address.", 404);
            }
        } else {
            $login = trim($usernameReq);
            $user_data = get_user_by('login', $login);
        }
        if (!$user_data) {
            return parent::sendError("invalid_email", "There is no account with that username or email address.", 404);
        }

        $user_login = $user_data->user_login;
        $user_email = $user_data->user_email;
        $key = get_password_reset_key($user_data);

        if (is_wp_error($key)) {
            return $key;
        }

        if (is_multisite()) {
            $site_name = get_network()->site_name;
        } else {
            $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        }
		
		//$otpcode = rand(100001,999999);
        $message = __('Someone has requested a password reset for the following account:') . "\r\n\r\n";
        $message .= sprintf(__('Site Name: %s'), $site_name) . "\r\n\r\n";
        $message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
		//$message .= "OTP CODE : ".$otpcode." \r\n\r\n";
        $message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";
        $message .= __('To reset your password, visit the following address:') . "\r\n\r\n";
        $message .= network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login') . "\r\n";
        $title = sprintf(__('[%s] Password Reset'), $site_name);
        $title = apply_filters('retrieve_password_title', $title, $user_login, $user_data);
        $message = apply_filters('retrieve_password_message', $message, $key, $user_login, $user_data);

        wp_mail($user_email, wp_specialchars_decode($title), $message);

        return new WP_REST_Response(array(
            'status' => 'success',
        ), 200);;
    }
	
	public function validate_reset_password()
    {
        $json = file_get_contents('php://input');
        $params = json_decode($json, TRUE);
        $usernameReq = $params["user_login"];
		$otp = $params["otp"];

        $errors = new WP_Error();
        if (empty($usernameReq) || !is_string($usernameReq)) {
            return parent::sendError("empty_username", "Enter a username or email address.", 400);
        } elseif (strpos($usernameReq, '@')) {
            $user_data = get_user_by('email', trim(wp_unslash($usernameReq)));
            if (empty($user_data)) {
                return parent::sendError("invalid_email", "There is no account with that username or email address.", 404);
            }
        } else {
            $login = trim($usernameReq);
            $user_data = get_user_by('login', $login);
        }
        if (!$user_data) {
            return parent::sendError("invalid_email", "There is no account with that username or email address.", 404);
        }

        $user_login = $user_data->user_login;
        $user_email = $user_data->user_email;
		
		$otpcorrect = false;
		if($otp == '123456') {
			$otpcorrect = true;
		}

        return new WP_REST_Response(array(
			'otpsuccess' => $otpcorrect,
        ), 200);;
    }

    public function register()
    {
        $json = file_get_contents('php://input');
        $params = json_decode($json, TRUE);
        $usernameReq = $params["username"];
        $emailReq = $params["email"];
        $role = $params["role"];
        if (isset($role)) {
            if (!in_array($role, ['subscriber', 'wcfm_vendor', 'seller', 'wcfm_delivery_boy', 'driver','owner'], true)) {
                return parent::sendError("invalid_role", "Role is invalid.", 400);
            }
        }
        $userPassReq = $params["user_pass"];
        $userLoginReq = $params["user_login"];
        $userEmailReq = $params["user_email"];

        $username = sanitize_user($usernameReq);

        $email = sanitize_email($emailReq);
        if (isset($params["seconds"])) {
            $seconds = (int)$params["seconds"];
        } else {
            $seconds = 1209600;
        }

        if (!validate_username($username)) {
            return parent::sendError("invalid_username", "Username is invalid.", 400);
        } elseif (username_exists($username)) {
            return parent::sendError("existed_username", "Username already exists.", 400);
        } else {
            if (!is_email($email)) {
                return parent::sendError("invalid_email", "E-mail address is invalid.", 400);
            } elseif (email_exists($email)) {
                return parent::sendError("existed_email", "E-mail address is already in use.", 400);
            } else {
                if (!$userPassReq) {
                    $params->user_pass = wp_generate_password();
                }

                $allowed_params = array('user_login', 'user_email', 'user_pass', 'display_name', 'user_nicename', 'user_url', 'nickname', 'first_name',
                    'last_name', 'description', 'rich_editing', 'user_registered', 'role', 'jabber', 'aim', 'yim',
                    'comment_shortcuts', 'admin_color', 'use_ssl', 'show_admin_bar_front',
                );

                $dataRequest = $params;

                foreach ($dataRequest as $field => $value) {
                    if (in_array($field, $allowed_params)) {
                        $user[$field] = trim(sanitize_text_field($value));
                    }
                }

                $user['role'] = isset($params["role"]) ? sanitize_text_field($params["role"]) : get_option('default_role');
                $user_id = wp_insert_user($user);
				
                if (is_wp_error($user_id)) {
                    return parent::sendError($user_id->get_error_code(), $user_id->get_error_message(), 400);
                } elseif (isset($params["phone"])) {
                    update_user_meta($user_id, 'billing_phone', $params["phone"]);
                    update_user_meta($user_id, 'registered_phone_number', $params["phone"]);
                }
				
				if (isset($params["fcm_token"])) {
                    update_user_meta($user_id, 'fcm_token', $params["fcm_token"]);
                }
				
            }
        }

        $cookie = shopimint_generateCookieByUserId($user_id,  $seconds);

        return array(
			"fcmtoken" => $params["fcm_token"],
            "cookie" => $cookie,
            "user_id" => $user_id,
        );
    }

	
	public function get_userallinfo_byid($request)
    {
		 
        $userId = $_GET["userid"];
		
        $userinfo = [];
		
		$userinfo["fcm_token"] = get_user_meta($userId, 'fcm_token', true);
        $userinfo["first_name"] = get_user_meta($userId, 'shipping_first_name', true);
        $userinfo["last_name"] = get_user_meta($userId, 'shipping_last_name', true);
        $userinfo["company"] = get_user_meta($userId, 'shipping_company', true);
        $userinfo["address_1"] = get_user_meta($userId, 'shipping_address_1', true);
        $userinfo["address_2"] = get_user_meta($userId, 'shipping_address_2', true);
        $userinfo["city"] = get_user_meta($userId, 'shipping_city', true);
        $userinfo["state"] = get_user_meta($userId, 'shipping_state', true);
        $userinfo["postcode"] = get_user_meta($userId, 'shipping_postcode', true);
        $userinfo["country"] = get_user_meta($userId, 'shipping_country', true);
        $userinfo["email"] = get_user_meta($userId, 'shipping_email', true);
        $userinfo["phone"] = get_user_meta($userId, 'shipping_phone', true);

        return $userinfo;
    }

    private function get_shipping_address($userId)
    {
        $shipping = [];
		
        $shipping["first_name"] = get_user_meta($userId, 'shipping_first_name', true);
        $shipping["last_name"] = get_user_meta($userId, 'shipping_last_name', true);
        $shipping["company"] = get_user_meta($userId, 'shipping_company', true);
        $shipping["address_1"] = get_user_meta($userId, 'shipping_address_1', true);
        $shipping["address_2"] = get_user_meta($userId, 'shipping_address_2', true);
        $shipping["city"] = get_user_meta($userId, 'shipping_city', true);
        $shipping["state"] = get_user_meta($userId, 'shipping_state', true);
        $shipping["postcode"] = get_user_meta($userId, 'shipping_postcode', true);
        $shipping["country"] = get_user_meta($userId, 'shipping_country', true);
        $shipping["email"] = get_user_meta($userId, 'shipping_email', true);
        $shipping["phone"] = get_user_meta($userId, 'shipping_phone', true);

        if (
			empty($shipping["first_name"])
			&& empty($shipping["last_name"])
			&& empty($shipping["company"])
			&& empty($shipping["address_1"])
			&& empty($shipping["address_2"])
			&& empty($shipping["city"]) 
			&& empty($shipping["state"]) 
			&& empty($shipping["postcode"]) 
			&& empty($shipping["country"]) 
			&& empty($shipping["email"])
			&& empty($shipping["phone"])) 
		{
            return null;
        } else {
        	return $shipping;
		}
    }

    private function get_billing_address($userId)
    {
        $billing = [];

        $billing["first_name"] = get_user_meta($userId, 'billing_first_name', true);
        $billing["last_name"] = get_user_meta($userId, 'billing_last_name', true);
        $billing["company"] = get_user_meta($userId, 'billing_company', true);
        $billing["address_1"] = get_user_meta($userId, 'billing_address_1', true);
        $billing["address_2"] = get_user_meta($userId, 'billing_address_2', true);
        $billing["city"] = get_user_meta($userId, 'billing_city', true);
        $billing["state"] = get_user_meta($userId, 'billing_state', true);
        $billing["postcode"] = get_user_meta($userId, 'billing_postcode', true);
        $billing["country"] = get_user_meta($userId, 'billing_country', true);
        $billing["email"] = get_user_meta($userId, 'billing_email', true);
        $billing["phone"] = get_user_meta($userId, 'billing_phone', true);

        if (empty($billing["first_name"]) && empty($billing["last_name"]) && empty($billing["company"]) && empty($billing["address_1"]) && empty($billing["address_2"]) && empty($billing["city"]) && empty($billing["state"]) && empty($billing["postcode"]) && empty($billing["country"]) && empty($billing["email"]) && empty($billing["phone"])) {
            return null;
        }

        return $billing;
    }

    function getResponseUserInfo($user)
    {
        $shipping = $this->get_shipping_address($user->ID);
        $billing = $this->get_billing_address($user->ID);
        $avatar = get_user_meta($user->ID, 'user_avatar', true);
        if (!isset($avatar) || $avatar == "" || is_bool($avatar)) {
            $avatar = get_avatar_url($user->ID);
        } else {
            $avatar = $avatar[0];
        }
        $is_driver_available = false;
        if(is_plugin_active('delivery-drivers-for-woocommerce/delivery-drivers-for-woocommerce.php')){
			$is_driver_available = get_user_meta( $user->ID, 'ddwc_driver_availability', true );
		}
        return array(
            "id" => $user->ID,
            "username" => $user->user_login,
            "nicename" => $user->user_nicename,
            "email" => $user->user_email,
            "url" => $user->user_url,
            "registered" => $user->user_registered,
            "displayname" => $user->display_name,
            "firstname" => $user->user_firstname,
            "lastname" => $user->last_name,
            "nickname" => $user->nickname,
            "description" => $user->user_description,
            "capabilities" => $user->wp_capabilities,
            "role" => $user->roles,
            "shipping" => $shipping,
            "billing" => $billing,
            "avatar" => $avatar,
            "is_driver_available" => $is_driver_available,
            "dokan_enable_selling" => $user->dokan_enable_selling
        );
    }

    public function generate_auth_cookie()
    {
        $json = file_get_contents('php://input');
        $params = json_decode($json, TRUE);
        if (!isset($params["username"]) || !isset($params["password"])) {
            return parent::sendError("invalid_login", "Invalid params", 400);
        }
        $username = $params["username"];
        $password = $params["password"];


        if (isset($params["seconds"])) {
            $seconds = (int)$params["seconds"];
        } else {
            $seconds = 1209600;
        }

        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            return parent::sendError($user->get_error_code(), "Invalid username/email and/or password.", 401);
        }
		
		if (isset($params["fcm_token"])) {
			update_user_meta($user->ID, 'fcm_token', $params["fcm_token"]);
		}

        $cookie = shopimint_generateCookieByUserId($user->ID, $seconds);
		
        return array(
            "cookie" => $cookie,
			"fcmtoken" => $params["fcm_token"],
            "cookie_name" => LOGGED_IN_COOKIE,
            "user" => $this->getResponseUserInfo($user),
        );
    }

    function createSocialAccount($email, $name, $firstName, $lastName, $userName, $avatar, $fcmtoken) {
		
        $email_exists = email_exists($email);
        if ($email_exists) {
            $user = get_user_by('email', $email);
            $user_id = $user->ID;
        } else {
            $i = 0;
            while (username_exists($userName)) {
                $i++;
                $userName = strtolower($userName) . '.' . $i;
            }
            $random_password = wp_generate_password($length = 12, $include_standard_special_chars = false);
            $userdata = array(
                'user_login' => $userName,
                'user_email' => $email,
                'user_pass' => $random_password,
                'display_name' => $name,
                'first_name' => $firstName,
                'last_name' => $lastName,);
            $user_id = wp_insert_user($userdata);
        }
		
        $cookie = shopimint_generateCookieByUserId($user_id);
        $user = get_userdata($user_id);

        if ($fcmtoken != '' && $fcmtoken != false) {
			update_user_meta($user->ID, 'fcm_token', $fcmtoken);
		}

        $response['wp_user_id'] = $user_id;
        $response['cookie'] = $cookie;
        $response['user_login'] = $user->user_login;
		
		$user_array = $this->getResponseUserInfo($user);
		if($avatar != '' || $avatar != null) {
			$user_array["avatar"] = $avatar;
		}
 		$response['user'] = $user_array;
        return $response;
    }

    public function fb_connect($request)
    {
        $fields = 'id,name,first_name,last_name,email';
        $enable_ssl = true;
        $access_token = $request["access_token"];
       
        $fcmtoken = '';
        if (isset($request["fcm_token"])) {
            $fcmtoken = $request["fcm_token"];
		}
		
        if (!isset($access_token)) {
            return parent::sendError("invalid_login", "You must include a 'access_token' variable. Get the valid access_token for this app from Facebook API.", 400);
        }
        $url = 'https://graph.facebook.com/me/?fields=' . $fields . '&access_token=' . $access_token;

        $result = wp_remote_retrieve_body(wp_remote_get($url));

        $result = json_decode($result, true);

        if (isset($result["email"])) {
            $user_name = strtolower($result['first_name'] . '.' . $result['last_name']);
            return $this->createSocialAccount($result["email"], $result['name'], $result['first_name'], $result['last_name'], $user_name , "" , $fcmtoken);
        } else {
            return parent::sendError("invalid_login", "Your 'access_token' did not return email of the user. Without 'email' user can't be logged in or registered. Get user email extended permission while joining the Facebook app.", 400);
        }
    }

    function jwtDecode($token)
    {
        $splitToken = explode(".", $token);
        $payloadBase64 = $splitToken[1]; // Payload is always the index 1
        $decodedPayload = json_decode(urldecode(base64_decode($payloadBase64)), true);
        return $decodedPayload;
    }

    public function apple_login($request)
    {
//         $json = file_get_contents('php://input');
//         $params = json_decode($json, TRUE);
        $token = $request["access_token"];
        $decoded = $this->jwtDecode($token);
        $user_email = $decoded["email"];
        if (!isset($user_email)) {
            return parent::sendError("invalid_login", "Can't get the email to create account.", 400);
        }
        $display_name = explode("@", $user_email)[0];
        $user_name = $display_name;

        $fcmtoken = '';
        if (isset($request["fcm_token"])) {
            $fcmtoken = $request["fcm_token"];
		}

        return $this->createSocialAccount($user_email, $display_name, $display_name, "", $user_name, "", $fcmtoken);
    }

    public function google_login($request)
    {
        $access_token = $request["access_token"];

        $fcmtoken = '';
        if (isset($request["fcm_token"])) {
            $fcmtoken = $request["fcm_token"];
		}

        if (!isset($access_token)) {
            return parent::sendError("invalid_login", "You must include a 'access_token' variable. Get the valid access_token for this app from Google API.", 400);
        }

        $url = 'https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token=' . $access_token;

        $result = wp_remote_retrieve_body(wp_remote_get($url));

        $result = json_decode($result, true);
        if (isset($result["email"])) {
            $firstName = $result["given_name"];
            $lastName = $result["family_name"];
            $email = $result["email"];
            $display_name = $firstName . " " . $lastName;
            $user_name = $firstName . "." . $lastName;
			$avatar = $result["picture"];
            return $this->createSocialAccount($email, $display_name, $firstName, $lastName, $user_name, $avatar, $fcmtoken);
        } else {
            return parent::sendError("invalid_login", "Your 'token' did not return email of the user. Without 'email' user can't be logged in or registered. Get user email extended permission while joining the Google app.", 400);
        }
    }

    public function get_currentuserinfo($request)
    {
        $cookie = $request["cookie"];
        if (isset($request["token"])) {
            $cookie = urldecode(base64_decode($request["token"]));
        }
        if (!isset($cookie)) {
            return parent::sendError("invalid_login", "You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.", 500);
        }

        $user_id = shopimint_validateCookieLogin($cookie);
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        $user = get_userdata($user_id);
        return array(
            "user" => $this->getResponseUserInfo($user)
        );
    }

    /**
     * Get Point Reward by User ID
     *
     * @return void
     */
    function get_points($request)
    {
        global $wc_points_rewards;
        $user_id = (int)$request['user_id'];
        $current_page = (int)$request['page'];

        $points_balance = WC_Points_Rewards_Manager::get_users_points($user_id);
        $points_label = $wc_points_rewards->get_points_label($points_balance);
        $count = apply_filters('wc_points_rewards_my_account_points_events', 5, $user_id);
        $current_page = empty($current_page) ? 1 : absint($current_page);

        $args = array(
            'calc_found_rows' => true,
            'orderby' => array(
                'field' => 'date',
                'order' => 'DESC',
            ),
            'per_page' => $count,
            'paged' => $current_page,
            'user' => $user_id,
        );
        $total_rows = WC_Points_Rewards_Points_Log::$found_rows;
        $events = WC_Points_Rewards_Points_Log::get_points_log_entries($args);

        return array(
            'points_balance' => $points_balance,
            'points_label' => $points_label,
            'total_rows' => $total_rows,
            'page' => $current_page,
            'count' => $count,
            'events' => $events
        );
    }

    function update_user_profile()
    {
        global $json_api;
        $json = file_get_contents('php://input');
        $params = json_decode($json);
        $cookie = $params->cookie;
        if (!isset($cookie)) {
            return parent::sendError("invalid_login", "You must include a 'cookie' var in your request. Use the `generate_auth_cookie` method.", 401);
        }
        $user_id = shopimint_validateCookieLogin($cookie);
        if (is_wp_error($user_id)) {
            return $user_id;
        }

        $user_update = array('ID' => $user_id);
        if (isset($params->user_pass)) {
            $user_update['user_pass'] = $params->user_pass;
        }
        if (isset($params->user_nicename)) {
            $user_update['user_nicename'] = $params->user_nicename;
        }
        if (isset($params->user_email)) {
            $user_update['user_email'] = $params->user_email;
        }
        if (isset($params->user_url)) {
            $user_update['user_url'] = $params->user_url;
        }
        if (isset($params->display_name)) {
            $user_update['display_name'] = $params->display_name;
        }
        if (isset($params->first_name)) {
            $user_update['first_name'] = $params->first_name;
        }
        if (isset($params->last_name)) {
            $user_update['last_name'] = $params->last_name;
        }
		
		/* Biling details */
		if (isset($params->billing_first_name)) {
            update_user_meta($user_id, 'billing_first_name', $params->billing_first_name, '');
        }
		if (isset($params->billing_last_name)) {
            update_user_meta($user_id, 'billing_last_name', $params->billing_last_name, '');
        }
        if (isset($params->billing_company)) {
            update_user_meta($user_id, 'billing_company', $params->billing_company, '');
        }
        if (isset($params->billing_state)) {
            update_user_meta($user_id, 'billing_state', $params->billing_state, '');
        }
        if (isset($params->billing_address_1)) {
            update_user_meta($user_id, 'billing_address_1', $params->billing_address_1, '');
        }
        if (isset($params->billing_address_2)) {
            update_user_meta($user_id, 'billing_address_2', $params->billing_address_2, '');
        }
        if (isset($params->billing_city)) {
            update_user_meta($user_id, 'billing_city', $params->billing_city, '');
        }
        if (isset($params->billing_country)) {
            update_user_meta($user_id, 'billing_country', $params->billing_country, '');
        }
        if (isset($params->billing_postcode)) {
            update_user_meta($user_id, 'billing_postcode', $params->billing_postcode, '');
        }
        if (isset($params->billing_email)) {
            update_user_meta($user_id, 'billing_email', $params->billing_email, '');
        }
        if (isset($params->billing_phone)) {
            update_user_meta($user_id, 'billing_phone', $params->billing_phone, '');
        }
		
		/* Shipping details */
		if (isset($params->shipping_first_name)) {
            update_user_meta($user_id, 'shipping_first_name', $params->shipping_first_name, '');
        }
		if (isset($params->shipping_last_name)) {
            update_user_meta($user_id, 'shipping_last_name', $params->shipping_last_name, '');
        }
        if (isset($params->shipping_company)) {
            update_user_meta($user_id, 'shipping_company', $params->shipping_company, '');
        }
        if (isset($params->shipping_state)) {
            update_user_meta($user_id, 'shipping_state', $params->shipping_state, '');
        }
        if (isset($params->shipping_address_1)) {
            update_user_meta($user_id, 'shipping_address_1', $params->shipping_address_1, '');
        }
        if (isset($params->shipping_address_2)) {
            update_user_meta($user_id, 'shipping_address_2', $params->shipping_address_2, '');
        }
        if (isset($params->shipping_city)) {
            update_user_meta($user_id, 'shipping_city', $params->shipping_city, '');
        }
        if (isset($params->shipping_country)) {
            update_user_meta($user_id, 'shipping_country', $params->shipping_country, '');
        }
        if (isset($params->shipping_postcode)) {
            update_user_meta($user_id, 'shipping_postcode', $params->shipping_postcode, '');
        }
        if (isset($params->shipping_email)) {
            update_user_meta($user_id, 'shipping_email', $params->shipping_email, '');
        }
        if (isset($params->shipping_phone)) {
            update_user_meta($user_id, 'shipping_phone', $params->shipping_phone, '');
        }

        if (isset($params->avatar)) {
            $count = 1;
            require_once(ABSPATH . 'wp-admin' . '/includes/file.php');
            require_once(ABSPATH . 'wp-admin' . '/includes/image.php');
            $imgdata = $params->avatar;
            $imgdata = trim($imgdata);
            $imgdata = str_replace('data:image/png;base64,', '', $imgdata);
            $imgdata = str_replace('data:image/jpg;base64,', '', $imgdata);
            $imgdata = str_replace('data:image/jpeg;base64,', '', $imgdata);
            $imgdata = str_replace('data:image/gif;base64,', '', $imgdata);
            $imgdata = str_replace(' ', '+', $imgdata);
            $imgdata = base64_decode($imgdata);
            $f = finfo_open();
            $mime_type = finfo_buffer($f, $imgdata, FILEINFO_MIME_TYPE);
            $type_file = explode('/', $mime_type);
            $avatar = time() . '_' . $count . '.' . $type_file[1];

            $uploaddir = wp_upload_dir();
            $myDirPath = $uploaddir["path"];
            $myDirUrl = $uploaddir["url"];

            file_put_contents($uploaddir["path"] . '/' . $avatar, $imgdata);

            $filename = $myDirUrl . '/' . basename($avatar);
            $wp_filetype = wp_check_filetype(basename($filename), null);
            $uploadfile = $uploaddir["path"] . '/' . basename($filename);

            $attachment = array(
                "post_mime_type" => $wp_filetype["type"],
                "post_title" => preg_replace("/\.[^.]+$/", "", basename($filename)),
                "post_content" => "",
                "post_author" => $user_id,
                "post_status" => "inherit",
                'guid' => $myDirUrl . '/' . basename($filename),
            );

            $attachment_id = wp_insert_attachment($attachment, $uploadfile);
            $attach_data = apply_filters('wp_generate_attachment_metadata', $attachment, $attachment_id, 'create');
            // $attach_data = wp_generate_attachment_metadata($attachment_id, $uploadfile);
            wp_update_attachment_metadata($attachment_id, $attach_data);
            $url = wp_get_attachment_image_src($attachment_id);
            update_user_meta($user_id, 'user_avatar', $url, '');

        }


        $user_data = wp_update_user($user_update);

        if (is_wp_error($user_data)) {
            // There was an error; possibly this user doesn't exist.
            echo 'Error.';
        }
        $user = get_userdata($user_id);

        if (isset($params->deviceToken)) {
            if (isset($params->is_manager) && $params->is_manager) {
                update_user_meta($user_id, "shopimint_manager_device_token", $params->deviceToken);
            } else if (isset($params->is_delivery) && $params->is_delivery) {
                update_user_meta($user_id, "shopimint_delivery_device_token", $params->deviceToken);
            }
            if (!isset($params->is_delivery) && !isset($params->is_manager)) {
                update_user_meta($user_id, "shopimint_device_token", $params->deviceToken);
            }
            if(in_array('wcfm_delivery_boy', (array)$user->roles) || in_array('driver',(array)$user->roles)){
                update_user_meta($user_id, "shopimint_delivery_device_token", $params->deviceToken);
            }
        }

        return $this->getResponseUserInfo($user);
    }

    public function get_currency_rates()
    {
        global $woocommerce_wpml;

        if (!empty($woocommerce_wpml->multi_currency) && !empty($woocommerce_wpml->settings['currencies_order'])) {
            return $woocommerce_wpml->settings['currency_options'];
        }
        return parent::sendError("not_install_woocommerce_wpml", "WooCommerce WPML hasn't been installed yet.", 404);
    }

    public function get_countries()
    {
        $wc_countries = new WC_Countries();
        $array = $wc_countries->get_countries();
        $keys = array_keys($array);
        $countries = array();
        for ($i = 0; $i < count($keys); $i++) {
            $countries[] = ["code" => $keys[$i], "name" => $array[$keys[$i]]];
        }
        return $countries;
    }

    public function get_states($request)
    {
        $wc_countries = new WC_Countries();
        $array = $wc_countries->get_states($request["country_code"]);
        if ($array) {
            $keys = array_keys($array);
            $states = array();
            for ($i = 0; $i < count($keys); $i++) {
                $states[] = ["code" => $keys[$i], "name" => $array[$keys[$i]]];
            }
            return $states;
        } else {
            return [];
        }
    }

    function custom_delete_item_permissions_check($request)
    {
        $cookie = $request->get_header("User-Cookie");
        if (isset($cookie) && $cookie != null) {
            $user_id = shopimint_validateCookieLogin($cookie);
            if (is_wp_error($user_id)) {
                return false;
            }
            $request['force'] = true;
            $request["id"] = $user_id;
            return true;
        } else {
            return false;
        }
    }

    function delete_account($request) {
		
		require_once(ABSPATH.'wp-admin/includes/user.php');
        $userdelete = wp_delete_user($request["id"]);
		return [
			'message' => ($userdelete) ? "User deleted Successfully" : "No permission to delete user",
			'status' => $userdelete,
		];
        
    }
	
	
}