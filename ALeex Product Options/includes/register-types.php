<?php
// Register "Product Extra" post type
function aleexpo_register_product_extra_cpt() {
    $labels = array(
        'name' => 'Product Extras',
        'singular_name' => 'Product Extra',
        'add_new' => 'Add New Extra',
        'add_new_item' => 'Add New Product Extra',
        'edit_item' => 'Edit Product Extra',
        'new_item' => 'New Product Extra',
        'view_item' => 'View Product Extra',
        'search_items' => 'Search Product Extras',
        'not_found' => 'No extras found',
        'not_found_in_trash' => 'No extras found in Trash',
        'menu_name' => 'Product Extras',
    );
    $args = array(
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'aleex-product-options', // <-- Make it a submenu
        'menu_icon' => 'dashicons-list-view',
        'supports' => array('title'),
    );
    register_post_type('aleex_product_extra', $args);
}
add_action('init', 'aleexpo_register_product_extra_cpt');

// Register taxonomy for assignment
function aleexpo_register_assignment_taxonomy() {
    $labels = array(
        'name' => 'Extra Assignments',
        'singular_name' => 'Extra Assignment',
        'search_items' => 'Search Assignments',
        'all_items' => 'All Assignments',
        'edit_item' => 'Edit Assignment',
        'update_item' => 'Update Assignment',
        'add_new_item' => 'Add New Assignment',
        'new_item_name' => 'New Assignment Name',
        'menu_name' => 'Assignments',
    );
    $args = array(
        'hierarchical' => false,
        'labels' => $labels,
        'show_ui' => true,
        'show_in_menu' => false,
        'show_admin_column' => true,
        'show_in_quick_edit' => false,
    );
    register_taxonomy('aleex_extra_assignment', 'aleex_product_extra', $args);
}
add_action('init', 'aleexpo_register_assignment_taxonomy');