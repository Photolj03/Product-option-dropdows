<?php
/*
Plugin Name: Aleex Product Options
Description: Add customizable extra options with prices to WooCommerce products and categories, with Elementor compatibility.
Version: 0.1
Author: Aleex Developments
*/

if ( ! defined( 'ABSPATH' ) ) exit;

class Aleex_Product_Options_Plugin {
    public function __construct() {
        $this->includes(); // <-- Move this here
        add_action('admin_menu', array($this, 'register_admin_menu'));
        // remove from init
        // add_action('init', array($this, 'includes'));
        if (!is_admin()) {
            // Frontend only
            add_action('wp', array($this, 'frontend_includes'));
        }
    }

    public function includes() {
        require_once plugin_dir_path(__FILE__) . 'includes/register-types.php';
        require_once plugin_dir_path(__FILE__) . 'includes/meta-boxes.php';
    }

    public function frontend_includes() {
        require_once plugin_dir_path(__FILE__) . 'includes/frontend.php';
        require_once plugin_dir_path(__FILE__) . 'includes/cart.php';
    }

    public function register_admin_menu() {
        add_menu_page(
            'Aleex Product Options',
            'Aleex Product Options',
            'manage_options',
            'aleex-product-options',
            array($this, 'admin_page_content'),
            'dashicons-plus',
            56
        );
        // Remove the add_submenu_page for 'edit.php?post_type=aleex_product_extra'
    }

    public function admin_page_content() {
        echo '<div class="wrap"><h1>Aleex Product Options</h1><p>This is where you will manage your product extra options.</p></div>';
    }

 
}
new Aleex_Product_Options_Plugin();