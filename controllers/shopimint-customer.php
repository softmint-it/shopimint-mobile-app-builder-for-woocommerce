<?php

class CUSTOM_WC_REST_Customers_Controller extends WC_REST_Customers_Controller
{

    /**
     * Endpoint namespace
     *
     * @var string
     */
    protected $namespace = 'api/flutter_customer';

    /**
     * Register all routes releated with stores
     *
     * @return void
     */
    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_shopimint_woo_routes'));
    }

    public function register_shopimint_woo_routes()
    {
        register_rest_route($this->namespace, '/delete_account', array(
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_account'),
                'permission_callback' => array($this, 'custom_delete_item_permissions_check'),
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
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

    function delete_account($request)
    {
        return $this->delete_item($request);
    }
}

new CUSTOM_WC_REST_Customers_Controller();