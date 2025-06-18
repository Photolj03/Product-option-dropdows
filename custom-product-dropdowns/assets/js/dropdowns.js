function updatePrice() {
    console.log('updatePrice called');
    insertLivePriceBox();
    // rest of function...
}

let cpdAjaxRequest = null;

function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this, args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
    };
}

jQuery(function($){
    // Always insert #cpd-live-price after the WooCommerce price, even if Elementor re-renders
    function insertLivePriceBox() {
        // Try to find the bdi price element and insert after it
        var $bdi = $('.woocommerce-Price-currencySymbol').closest('bdi');
        if ($bdi.length && $('#cpd-live-price').length === 0) {
            $bdi.parent().after('<div id="cpd-live-price" style="font-size:1.5em;font-weight:bold;margin-bottom:0.5em;"></div>');
        }
    }

    function getBasePrice() {
        // Get the price from the bdi element
        var $bdi = $('.woocommerce-Price-currencySymbol').closest('bdi');
        if ($bdi.length && $bdi.text().trim() !== '') {
            return parseFloat($bdi.text().replace(/[^0-9.]/g, '')) || 0;
        }
        return 0;
    }

    function updatePrice() {
        insertLivePriceBox();
        var basePrice = getBasePrice();
        var extra = 0;
        $('select[id^="cpd_custom_dropdown_"]').each(function(){
            var price = parseFloat($(this).find('option:selected').data('price')) || 0;
            extra += price;
        });
        if (basePrice > 0) {
            var productId = $('form.cart').find('input[name="add-to-cart"]').val() || $('input[name="product_id"]').val();
            if (typeof woocommerce_params !== 'undefined' && woocommerce_params.ajax_url) {
                if (cpdAjaxRequest) cpdAjaxRequest.abort();
                cpdAjaxRequest = $.post(woocommerce_params.ajax_url, {
                    action: 'cpd_get_price_incl_vat',
                    product_id: productId,
                    price: basePrice + extra
                }, function(response) {
                    $('#cpd-live-price').html(response);
                    cpdAjaxRequest = null;
                });
            } else {
                // fallback
                var newPrice = (basePrice + extra).toLocaleString('en-GB', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                $('#cpd-live-price').html('<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">£</span>' + newPrice + '</bdi></span>');
            }
        } else {
            $('#cpd-live-price').empty();
        }
    }

    const debouncedUpdatePrice = debounce(updatePrice, 200);

    $(document).on('change', 'select[id^="cpd_custom_dropdown_"]', debouncedUpdatePrice);
    $(document).on('show_variation hide_variation found_variation', debouncedUpdatePrice);
    $(window).on('load', debouncedUpdatePrice);
    document.addEventListener('DOMContentLoaded', debouncedUpdatePrice);

    // Optionally, call once on load
    debouncedUpdatePrice();
});


