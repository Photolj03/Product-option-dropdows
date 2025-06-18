<?php
// Hook into WooCommerce single product page before add to cart button
add_action('woocommerce_before_add_to_cart_button', 'aleexpo_display_product_extras', 0);
function aleexpo_display_product_extras() {
    if (!is_product()) return;
    global $post;
    $product_id = $post->ID;

    // Get product categories
    $categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);

    // Query all published extras
    $extras = get_posts([
        'post_type' => 'aleex_product_extra',
        'post_status' => 'publish',
        'numberposts' => -1,
    ]);

    $relevant_extras = [];
    foreach ($extras as $extra) {
        $assigned_products = get_post_meta($extra->ID, '_aleex_assigned_products', true);
        if (!is_array($assigned_products)) $assigned_products = [];
        $assigned_categories = get_post_meta($extra->ID, '_aleex_assigned_categories', true);
        if (!is_array($assigned_categories)) $assigned_categories = [];

        $assigned = false;
        // Match product
        if (in_array($product_id, $assigned_products)) {
            $assigned = true;
        }
        // Match category
        if (!$assigned && !empty($categories)) {
            foreach ($categories as $cat_id) {
                if (in_array($cat_id, $assigned_categories)) {
                    $assigned = true;
                    break;
                }
            }
        }
        if ($assigned) {
            $relevant_extras[] = $extra;
        }
    }
    echo '<!-- ALEEX EXTRAS START -->';

    if (empty($relevant_extras)) return;

    echo '<div class="aleex-product-extras" style="margin-bottom:20px"><h4>Product Options</h4>';
    foreach ($relevant_extras as $extra) {
        $options = get_post_meta($extra->ID, '_aleex_extra_options', true);
        if (!is_array($options) || empty($options)) continue;
        printf('<div class="aleex-extra-field" style="margin-bottom:10px;">
            <label><strong>%s</strong></label><br>',
            esc_html(get_the_title($extra->ID))
        );
        // Dropdown
        echo '<select class="aleex-extra-select" name="aleex_extra_' . esc_attr($extra->ID) . '">';
        echo '<option value="" data-extra="0">-- None --</option>';
        foreach ($options as $opt_key => $opt) {
            $price = floatval($opt['price']);
            $label = esc_html($opt['label']);
            $show = $label . ($price > 0 ? ' (+' . wc_price($price) . ')' : '');
            printf(
                '<option value="%s" data-extra="%s">%s</option>',
                esc_attr($opt_key), // use the real key!
                esc_attr($price),
                $show
            );
        }
        echo '</select></div>';
    }
    echo '</div>';

    // Output a price update JS hook (the full JS will be in the next step)
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const selects = document.querySelectorAll('.aleex-extra-select');
        const priceElem = document.querySelector('.summary .price, .elementor-widget-woocommerce-product-price .woocommerce-Price-amount');
        if (!priceElem) return;
        const basePrice = parseFloat(priceElem.textContent.replace(/[^0-9.]+/g,""));
        function updatePrice() {
            let extra = 0;
            selects.forEach(sel => {
                const price = parseFloat(sel.options[sel.selectedIndex].getAttribute('data-extra')) || 0;
                extra += price;
            });
            const newPrice = (basePrice + extra).toFixed(2);
            // Simple replace, will work for most themes
            priceElem.textContent = priceElem.textContent.replace(/[0-9,.]+/, newPrice);
        }
        selects.forEach(sel => {
            sel.addEventListener('change', updatePrice);
        });
    });
    </script>
    <?php
}
add_action('wp_footer', function() {
    if (!is_product()) return;
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        function getCurrencySymbol() {
            let price = document.querySelector('.summary .price, .elementor-widget-woocommerce-product-price .woocommerce-Price-amount');
            if (!price) return "£";
            let match = price.textContent.match(/[^\d.,\s]+/);
            return match ? match[0] : "£";
        }
        function parsePrice(str) {
            let val = str.replace(/[^\d.,]/g, '').replace(',', '.');
            return parseFloat(val) || 0;
        }

        const selects = document.querySelectorAll('.aleex-extra-select');
        const priceElem = document.querySelector('.summary .price, .elementor-widget-woocommerce-product-price .woocommerce-Price-amount');
        if (!priceElem) return;

        // Store the original price as a float
        let originalPrice = parseFloat(priceElem.textContent.replace(/[^0-9.]/g, ''));
        if (isNaN(originalPrice)) originalPrice = 0;

        function updatePrice() {
            let extra = 0;
            selects.forEach(sel => {
                let opt = sel.options[sel.selectedIndex];
                let price = parseFloat(opt.getAttribute('data-extra')) || 0;
                extra += price;
            });
            let newPrice = (originalPrice + extra).toFixed(2);

            // Replace only the numeric part of the price
            priceElem.innerHTML = priceElem.innerHTML.replace(/([0-9]+[.,]?[0-9]*)/g, newPrice);
        }

        selects.forEach(sel => {
            sel.addEventListener('change', updatePrice);
        });
    });
    </script>
    <?php
});