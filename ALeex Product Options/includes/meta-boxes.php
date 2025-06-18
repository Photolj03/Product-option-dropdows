<?php
add_action('add_meta_boxes', function() {
    add_meta_box(
        'aleex_extra_options',
        'Product Extra Options',
        'aleexpo_render_options_metabox',
        'aleex_product_extra',
        'normal',
        'default'
    );
});

function aleexpo_render_options_metabox($post) {
    $options = get_post_meta($post->ID, '_aleex_extra_options', true);
    if (!is_array($options)) $options = [];
    $assigned_products = get_post_meta($post->ID, '_aleex_assigned_products', true);
    if (!is_array($assigned_products)) $assigned_products = [];
    $assigned_categories = get_post_meta($post->ID, '_aleex_assigned_categories', true);
    if (!is_array($assigned_categories)) $assigned_categories = [];

    // Nonce for security
    wp_nonce_field('aleex_save_extra_options', 'aleex_extra_options_nonce');
    ?>
    <div id="aleex-options-wrapper">
        <h3>Extra Choices</h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Option Label</th>
                    <th>Price (e.g. 4.99)</th>
                    <th>Remove</th>
                </tr>
            </thead>
            <tbody id="aleex-options-list">
            <?php if (!empty($options)): foreach ($options as $index => $option): ?>
                <tr>
                    <td>
                        <input type="text" name="aleex_option_label[]" value="<?php echo esc_attr($option['label']); ?>" required />
                    </td>
                    <td>
                        <input type="number" step="0.01" name="aleex_option_price[]" value="<?php echo esc_attr($option['price']); ?>" required />
                    </td>
                    <td>
                        <button type="button" class="button remove-option">Remove</button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        <button type="button" class="button" id="add-option">Add Option</button>
    </div>
    <hr>
    <div id="aleex-assignment-wrapper">
        <h3>Assign to Products</h3>
        <select name="aleex_assigned_products[]" multiple style="width:100%;" id="aleex-assigned-products">
            <?php
            // List WooCommerce products
            $products = get_posts([
                'post_type' => 'product',
                'posts_per_page' => 100,
                'post_status' => 'publish',
            ]);
            foreach ($products as $prod) {
                $selected = in_array($prod->ID, $assigned_products) ? 'selected' : '';
                echo '<option value="' . esc_attr($prod->ID) . '" ' . $selected . '>' . esc_html($prod->post_title) . '</option>';
            }
            ?>
        </select>
        <p><small>Hold Ctrl (or Cmd on Mac) to select multiple products.</small></p>
        <h3>Assign to Product Categories</h3>
        <select name="aleex_assigned_categories[]" multiple style="width:100%;" id="aleex-assigned-categories">
            <?php
            $terms = get_terms([
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
            ]);
            foreach ($terms as $term) {
                $selected = in_array($term->term_id, $assigned_categories) ? 'selected' : '';
                echo '<option value="' . esc_attr($term->term_id) . '" ' . $selected . '>' . esc_html($term->name) . '</option>';
            }
            ?>
        </select>
        <p><small>Hold Ctrl (or Cmd on Mac) to select multiple categories.</small></p>
    </div>
    <style>
        #aleex-options-wrapper input[type="text"], #aleex-options-wrapper input[type="number"], #aleex-assigned-products, #aleex-assigned-categories { width: 100%; }
        #aleex-options-list tr td { vertical-align: middle; }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var list = document.getElementById('aleex-options-list');
        document.getElementById('add-option').addEventListener('click', function() {
            var row = document.createElement('tr');
            row.innerHTML = `<td><input type="text" name="aleex_option_label[]" required /></td>
                <td><input type="number" step="0.01" name="aleex_option_price[]" required /></td>
                <td><button type="button" class="button remove-option">Remove</button></td>`;
            list.appendChild(row);
        });
        if (list) {
            list.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-option')) {
                    e.target.closest('tr').remove();
                }
            });
        }
    });
    </script>
    <?php
}

function aleexpo_save_options_metabox($post_id) {
    if (!isset($_POST['aleex_extra_options_nonce']) || !wp_verify_nonce($_POST['aleex_extra_options_nonce'], 'aleex_save_extra_options')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Save options
    $labels = isset($_POST['aleex_option_label']) ? array_map('sanitize_text_field', $_POST['aleex_option_label']) : [];
    $prices = isset($_POST['aleex_option_price']) ? array_map('floatval', $_POST['aleex_option_price']) : [];
    $options = [];
    foreach ($labels as $i => $label) {
        if ($label !== '' && isset($prices[$i])) {
            $options[] = [
                'label' => $label,
                'price' => $prices[$i]
            ];
        }
    }
    update_post_meta($post_id, '_aleex_extra_options', $options);

    // Save assignments
    $assigned_products = isset($_POST['aleex_assigned_products']) ? array_map('intval', $_POST['aleex_assigned_products']) : [];
    update_post_meta($post_id, '_aleex_assigned_products', $assigned_products);

    $assigned_categories = isset($_POST['aleex_assigned_categories']) ? array_map('intval', $_POST['aleex_assigned_categories']) : [];
    update_post_meta($post_id, '_aleex_assigned_categories', $assigned_categories);
}
add_action('save_post_aleex_product_extra', 'aleexpo_save_options_metabox');