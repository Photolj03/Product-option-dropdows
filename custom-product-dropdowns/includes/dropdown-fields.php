<?php
// This file defines the functions to create and display the customizable dropdown fields on the product pages.

// Register hooks for adding dropdowns to products
add_action('woocommerce_before_add_to_cart_button', 'cpd_add_dropdowns_to_products', 5);

function cpd_add_dropdowns_to_products() {
    global $product;

    // 1. Check for per-product dropdowns
    $dropdowns = get_post_meta($product->get_id(), '_cpd_dropdowns', true);
    $dropdowns = $dropdowns ? json_decode($dropdowns, true) : [];

    if (empty($dropdowns)) {
        // 2. If not set, check for category dropdowns
        $categories = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'ids']);
        foreach ($categories as $cat_id) {
            $cat_dropdowns = get_option('cpd_dropdowns_cat_' . $cat_id, '');
            if ($cat_dropdowns) {
                $dropdowns = json_decode($cat_dropdowns, true);
                break;
            }
        }
    }

    // 3. Output all dropdowns if exist
    if (!empty($dropdowns)) {
        echo '<div class="cpd-dropdowns-wrapper">';
        foreach ($dropdowns as $idx => $dropdown) {
            $label = isset($dropdown['label']) ? $dropdown['label'] : 'Choose an option';
            $options = isset($dropdown['options']) ? $dropdown['options'] : '';
        echo '<p class="form-row form-row-wide cpd-dropdown-group">';
        echo '<label for="cpd_custom_dropdown_' . $idx . '">' . esc_html($label) . '</label>';
        echo '<select name="cpd_custom_dropdown[' . $idx . ']" id="cpd_custom_dropdown_' . $idx . '" class="cpd-dropdown select">';echo '<option value="" selected disabled>Select an option</option>';
            foreach (explode("\n", $options) as $option) {
                $option = trim($option);
                if (!$option) continue;
                $parts = explode('|', $option);
                if (count($parts) !== 2) continue; // skip malformed lines
                list($name, $price) = array_map('trim', $parts);
                echo '<option value="' . esc_attr($name) . '" data-price="' . esc_attr($price) . '">' . esc_html($name) . ' (+£' . esc_html($price) . ')</option>';
            }
            echo '</select>';
            echo '</p>';
        }
        echo '</div>';
    }
}

// Register settings for the plugin
function cpd_register_settings() {
    // Logic to register settings
}
add_action( 'admin_init', 'cpd_register_settings' );

// Store dropdown selections in cart item data
add_filter('woocommerce_add_cart_item_data', function($cart_item_data, $product_id) {
    if (isset($_POST['cpd_custom_dropdown']) && is_array($_POST['cpd_custom_dropdown'])) {
        $cart_item_data['cpd_custom_dropdown'] = array_map('sanitize_text_field', $_POST['cpd_custom_dropdown']);
    }
    return $cart_item_data;
}, 10, 2);

// Show dropdown selections in cart/checkout
add_filter('woocommerce_get_item_data', function($item_data, $cart_item) {
    if (!empty($cart_item['cpd_custom_dropdown'])) {
        foreach ($cart_item['cpd_custom_dropdown'] as $idx => $value) {
            $item_data[] = [
                'name'  => 'Option ' . ($idx + 1),
                'value' => esc_html($value),
            ];
        }
    }
    return $item_data;
}, 10, 2);

// Adjust cart item price based on dropdown selections
add_action('woocommerce_before_calculate_totals', function($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    foreach ($cart->get_cart() as $cart_item) {
        if (!empty($cart_item['cpd_custom_dropdown'])) {
            $product_id = $cart_item['product_id'];
            // Get dropdowns (per product or per category)
            $dropdowns = get_post_meta($product_id, '_cpd_dropdowns', true);
            $dropdowns = $dropdowns ? json_decode($dropdowns, true) : [];
            if (empty($dropdowns)) {
                $categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
                foreach ($categories as $cat_id) {
                    $cat_dropdowns = get_option('cpd_dropdowns_cat_' . $cat_id, '');
                    if ($cat_dropdowns) {
                        $dropdowns = json_decode($cat_dropdowns, true);
                        break;
                    }
                }
            }
            // Calculate extra price
            $extra = 0;
            foreach ($cart_item['cpd_custom_dropdown'] as $idx => $selected) {
                if (isset($dropdowns[$idx])) {
                    $options = explode("\n", $dropdowns[$idx]['options']);
                    foreach ($options as $option) {
                        $option = trim($option);
                        if (!$option) continue;
                        list($name, $price) = array_map('trim', explode('|', $option));
                        if ($name === $selected) {
                            $extra += floatval($price);
                            break;
                        }
                    }
                }
            }
            // Set new price
            $cart_item['data']->set_price( $cart_item['data']->get_price() + $extra );
        }
    }
});

// Save dropdown selections to order items
add_action('woocommerce_add_order_item_meta', function($item_id, $values) {
    if (!empty($values['cpd_custom_dropdown'])) {
        foreach ($values['cpd_custom_dropdown'] as $idx => $value) {
            wc_add_order_item_meta($item_id, 'Dropdown Option ' . ($idx + 1), $value);
        }
    }
}, 10, 2);

// Validate that all dropdowns have a selection before adding to cart
add_filter('woocommerce_add_to_cart_validation', function($passed, $product_id, $quantity) {
    // Get dropdowns (per product or per category)
    $dropdowns = get_post_meta($product_id, '_cpd_dropdowns', true);
    $dropdowns = $dropdowns ? json_decode($dropdowns, true) : [];
    if (empty($dropdowns)) {
        $categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
        foreach ($categories as $cat_id) {
            $cat_dropdowns = get_option('cpd_dropdowns_cat_' . $cat_id, '');
            if ($cat_dropdowns) {
                $dropdowns = json_decode($cat_dropdowns, true);
                break;
            }
        }
    }

    if (!empty($dropdowns)) {
        foreach ($dropdowns as $idx => $dropdown) {
            if (empty($_POST['cpd_custom_dropdown'][$idx])) {
                wc_add_notice(sprintf(__('Please select an option for "%s".', 'custom-product-dropdowns'), esc_html($dropdown['label'])), 'error');
                return false;
            }
        }
    }
    return $passed;
}, 10, 3);

?>