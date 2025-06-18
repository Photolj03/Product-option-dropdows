<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Add custom data to the cart item.
 *
 * This function captures the selected extra options and the product's base price.
 *
 * @param array $cart_item_data The original cart item data.
 * @param int   $product_id     The ID of the product being added.
 * @param int   $variation_id   The ID of the variation being added, if any.
 * @return array The modified cart item data.
 */
function aleex_add_custom_data_to_cart_item($cart_item_data, $product_id, $variation_id) {
    // Get the actual product object to handle simple and variable products correctly.
    $product = wc_get_product($variation_id ? $variation_id : $product_id);

    // Store the base price securely in our own meta key to avoid conflicts.
    if ($product) {
        $cart_item_data['_aleex_base_price'] = (float) $product->get_price();
    }

    $extras = [];
    foreach ($_REQUEST as $key => $value) {
        // --- THIS IS THE FIX ---
        // The check is now `$value !== ''` instead of `!empty($value)`.
        // This correctly handles the first option, which has a value of "0".
        if (strpos($key, 'aleex_extra_') === 0 && $value !== '') {
            $extra_id = (int) str_replace('aleex_extra_', '', $key);
            $options = get_post_meta($extra_id, '_aleex_extra_options', true);

            // Check if the option is valid and add it to our extras array.
            if (is_array($options) && isset($options[$value])) {
                $option = $options[$value];
                $extras[] = [
                    'id'     => $extra_id,
                    'label'  => get_the_title($extra_id),
                    'option' => $option['label'],
                    'price'  => (float) $option['price'],
                ];
            }
        }
    }

    if (!empty($extras)) {
        $cart_item_data['_aleex_extras'] = $extras;
    }

    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'aleex_add_custom_data_to_cart_item', 10, 3);


/**
 * Recalculate the cart item price based on selected extras.
 *
 * This runs with maximum priority to ensure it's the last price modification applied.
 *
 * @param WC_Cart $cart The WooCommerce cart object.
 */
function aleex_recalculate_price_before_totals($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    foreach ($cart->get_cart() as $cart_item) {
        // Use our securely stored base price for calculation.
        if (isset($cart_item['_aleex_base_price'])) {
            $base_price = (float) $cart_item['_aleex_base_price'];
            $extra_total = 0;

            if (!empty($cart_item['_aleex_extras'])) {
                foreach ($cart_item['_aleex_extras'] as $extra) {
                    $extra_total += isset($extra['price']) ? (float) $extra['price'] : 0;
                }
            }
            // Set the new, final price on the product data object.
            $cart_item['data']->set_price($base_price + $extra_total);
        }
    }
}
add_action('woocommerce_before_calculate_totals', 'aleex_recalculate_price_before_totals', PHP_INT_MAX);


/**
 * Display the selected extra options in the cart and checkout pages.
 *
 * @param array $item_data The array of item data to display.
 * @param array $cart_item The cart item data.
 * @return array The modified item data.
 */
function aleex_display_extras_in_cart($item_data, $cart_item) {
    if (!empty($cart_item['_aleex_extras'])) {
        foreach ($cart_item['_aleex_extras'] as $extra) {
            $price_display = $extra['price'] > 0 ? ' (+' . wc_price($extra['price']) . ')' : '';
            $item_data[] = [
                'key'   => esc_html($extra['label']),
                'value' => esc_html($extra['option']) . $price_display,
            ];
        }
    }
    return $item_data;
}
add_filter('woocommerce_get_item_data', 'aleex_display_extras_in_cart', 10, 2);


/**
 * Save the extra options to the order when it is created.
 *
 * @param WC_Order_Item_Product $item          The order item object.
 * @param string                $cart_item_key The key of the item in the cart.
 * @param array                 $values        The cart item data.
 * @param WC_Order              $order         The order object.
 */
function aleex_save_extras_to_order_meta($item, $cart_item_key, $values, $order) {
    if (!empty($values['_aleex_extras'])) {
        foreach ($values['_aleex_extras'] as $extra) {
            $meta_label = esc_html($extra['label']);
            $meta_value = esc_html($extra['option']) . ($extra['price'] > 0 ? ' (+' . wc_price($extra['price']) . ')' : '');
            $item->add_meta_data($meta_label, $meta_value, true);
        }
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'aleex_save_extras_to_order_meta', 10, 4);