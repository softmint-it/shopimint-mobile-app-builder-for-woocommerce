<?php
require_once(__DIR__ . '/shopimint-base.php');
require_once(__DIR__ . '/helpers/blog-helper.php');
/*
 * Base REST Controller for shopimint
 *
 * @since 1.4.0
 *
 * @package home
 */

class ShopimintBlog extends ShopimintBaseController
{
     /**
     * Endpoint namespace
     *
     * @var string
     */
    protected $namespace = 'api/flutter_blog';


    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_shopimint_blog_routes'));
    }

    public function register_shopimint_blog_routes()
    {
        register_rest_route($this->namespace, '/blog/dynamic', array(
            array(
                'methods' => "GET",
                'callback' => array($this, 'get_blog_from_dynamic_link'),
                'permission_callback' => function () {
                    return parent::checkApiPermission();
                }
            ),
        ));

        register_rest_route( $this->namespace,  '/blog/create', array(
			array(
				'methods' => "POST",
				'callback' => array( $this, 'create_blog' ),
				'permission_callback' => function () {
                    return parent::checkApiPermission();
                }
			),
		));

        register_rest_route( $this->namespace,  '/blog/comment', array(
			array(
				'methods' => "POST",
				'callback' => array( $this, 'create_comment' ),
				'permission_callback' => function () {
                    return parent::checkApiPermission();
                }
			),
		));
    }

    function get_blog_from_dynamic_link($request)
    {
        $helper = new ShopimintBlogHelper();
        return $helper->get_blog_from_dynamic_link($request);
    }
    
    function create_blog($request){
		$helper = new ShopimintBlogHelper();
        return $helper->create_blog($request);
	}

    function create_comment($request){
		$helper = new ShopimintBlogHelper();
        return $helper->create_comment($request);
	}
}

new ShopimintBlog;