<?php
function calculate_total_price($base_price, $selected_options) {
    $total_price = $base_price;

    foreach ($selected_options as $option) {
        if (isset($option['price'])) {
            $total_price += floatval($option['price']);
        }
    }

    return $total_price;
}

function enqueue_price_calculation_script() {
    wp_enqueue_script('dropdowns-js', plugins_url('../assets/js/dropdowns.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('dropdowns-js', 'woocommerce_params', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_price_calculation_script');

add_action('wp_ajax_cpd_get_price_incl_vat', 'cpd_get_price_incl_vat');
add_action('wp_ajax_nopriv_cpd_get_price_incl_vat', 'cpd_get_price_incl_vat');
function cpd_get_price_incl_vat() {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;

    if (!$product_id) {
        echo 'No product ID received.';
        wp_die();
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        echo 'Invalid product.';
        wp_die();
    }

    if (!$price) {
        echo 'No price received.';
        wp_die();
    }

    $incl_vat = wc_get_price_including_tax($product, ['price' => $price]);
    echo '<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">£</span>' . wc_format_decimal($incl_vat, 2) . '</bdi></span>';
    wp_die();
}
?>
