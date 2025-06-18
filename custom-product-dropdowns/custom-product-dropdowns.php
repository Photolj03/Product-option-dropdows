<?php
/**
 * Plugin Name: Custom Product Dropdowns
 * Description: A plugin to add customizable dropdown options for products with associated prices.
 * Version: 1.0
 * Author: Your Name
 * License: GPL2
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'CPD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CPD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include necessary files
require_once CPD_PLUGIN_DIR . 'includes/admin-settings.php';
require_once CPD_PLUGIN_DIR . 'includes/dropdown-fields.php';
require_once CPD_PLUGIN_DIR . 'includes/price-calculation.php';

// Initialize the plugin
function cpd_init() {
    // Enqueue scripts and styles
    wp_enqueue_script( 'cpd-dropdowns-js', CPD_PLUGIN_URL . 'assets/js/dropdowns.js', array( 'jquery' ), null, true );
    wp_enqueue_style( 'cpd-dropdowns-css', CPD_PLUGIN_URL . 'assets/css/dropdowns.css', array(), null );
}
add_action( 'wp_enqueue_scripts', 'cpd_init' );

?>