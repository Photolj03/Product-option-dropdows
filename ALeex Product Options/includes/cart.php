<?php
error_log('ALEEX CART.PHP INCLUDED');
// Capture and store selected extras in cart item data
add_filter('woocommerce_add_cart_item_data', function($cart_item_data, $product_id, $variation_id = null) {
    // Debug: log POST data
    error_log('ALEEX POST: ' . print_r($_REQUEST, true)); // Use $_REQUEST for AJAX

    $extras = [];
    foreach ($_REQUEST as $key => $val) {
        if (strpos($key, 'aleex_extra_') === 0) {
            $extra_id = str_replace('aleex_extra_', '', $key);
            $options = get_post_meta($extra_id, '_aleex_extra_options', true);
            error_log("ALEEX OPTIONS for $extra_id: " . print_r($options, true));
            if ($val !== '' && isset($options[(string)$val])) {
                $option = $options[(string)$val];
                $extras[] = [
                    'id'     => $extra_id,
                    'label'  => get_the_title($extra_id),
                    'option' => $option['label'],
                    'price'  => floatval($option['price']),
                ];
            }
        }
    }
    if (!empty($extras)) {
        $cart_item_data['aleex_extras'] = $extras;
    }
    return $cart_item_data;
}, 10, 3);

// Add extra price to item price in cart
add_action('woocommerce_before_calculate_totals', function($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    foreach ($cart->get_cart() as $cart_item) {
        // Always use the base product price
        $base_price = floatval(wc_get_product($cart_item['product_id'])->get_price());
        $extra_total = 0;
        if (!empty($cart_item['aleex_extras'])) {
            foreach ($cart_item['aleex_extras'] as $extra) {
                $extra_total += floatval($extra['price']);
            }
        }
        $cart_item['data']->set_price($base_price + $extra_total);
        // Debug log
        error_log("Product ID: {$cart_item['product_id']} | Base: $base_price | Extras: $extra_total | Final: " . ($base_price + $extra_total));
    }
});

// Display selected extras in cart and checkout
add_filter('woocommerce_get_item_data', function($item_data, $cart_item) {
    if (!empty($cart_item['aleex_extras'])) {
        foreach ($cart_item['aleex_extras'] as $extra) {
            error_log('ALEEX CART ITEM: ' . print_r($cart_item, true));
            $item_data[] = [
                'key' => $extra['label'],
                'value' => $extra['option'] . ( $extra['price'] > 0 ? ' (+'.wc_price($extra['price']).')' : '' )
            ];
        }
    }
    // Debug: show actual price
    $item_data[] = [
        'key' => 'Debug Price',
        'value' => wc_price($cart_item['data']->get_price())
    ];
    return $item_data;
}, 10, 2);

// Save extras in order item meta
add_action('woocommerce_add_order_item_meta', function($item_id, $values) {
    if (!empty($values['aleex_extras'])) {
        wc_add_order_item_meta($item_id, 'aleex_extras', $values['aleex_extras']);
    }
}, 10, 2);

// Remove forced totals recalculation - WooCommerce handles this natively
// Removed: add_action('woocommerce_before_cart', ...)

add_action('woocommerce_after_calculate_totals', function() {
    if (function_exists('wc_enqueue_js')) {
        wc_enqueue_js('jQuery(document.body).trigger("wc_fragment_refresh");');
    }
});