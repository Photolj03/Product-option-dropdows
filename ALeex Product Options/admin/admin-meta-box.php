<?php
add_action('add_meta_boxes', function() {
    add_meta_box(
        'aleex_extra_options_box',
        'Product Extra Options',
        function($post) {
            $options = get_post_meta($post->ID, '_aleex_extra_options', true);
            if (!is_array($options)) $options = [];
            wp_nonce_field('aleex_save_extra_options', 'aleex_extra_options_nonce');
            echo '<table id="aleex-options-table"><tr><th>Label</th><th>Price</th><th></th></tr>';
            foreach ($options as $i => $opt) {
                printf(
                    '<tr>
                        <td><input type="text" name="aleex_extra_options[label][]" value="%s" /></td>
                        <td><input type="number" name="aleex_extra_options[price][]" value="%s" step="any" /></td>
                        <td><button class="remove-row">Remove</button></td>
                    </tr>',
                    esc_attr($opt['label']),
                    esc_attr($opt['price'])
                );
            }
            // Empty row for new option
            echo '<tr>
                <td><input type="text" name="aleex_extra_options[label][]" value="" /></td>
                <td><input type="number" name="aleex_extra_options[price][]" value="" step="any" /></td>
                <td></td>
            </tr>';
            echo '</table>';
            echo '<button id="aleex-add-row" type="button">Add Option</button>';
            ?>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('aleex-add-row').onclick = function() {
                    var table = document.getElementById('aleex-options-table');
                    var row = table.rows[1].cloneNode(true);
                    row.querySelectorAll('input').forEach(function(input) { input.value = ''; });
                    row.querySelector('.remove-row').onclick = function() { row.remove(); };
                    table.appendChild(row);
                };
                document.querySelectorAll('.remove-row').forEach(function(btn) {
                    btn.onclick = function() { btn.closest('tr').remove(); };
                });
            });
            </script>
            <?php
        },
        'product_extra', // Change to your custom post type slug
        'normal',
        'default'
    );
});

// Save the options
add_action('save_post_product_extra', function($post_id) {
    if (!isset($_POST['aleex_extra_options_nonce']) || !wp_verify_nonce($_POST['aleex_extra_options_nonce'], 'aleex_save_extra_options')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (!isset($_POST['aleex_extra_options'])) return;

    $labels = $_POST['aleex_extra_options']['label'];
    $prices = $_POST['aleex_extra_options']['price'];
    $options = [];
    for ($i = 0; $i < count($labels); $i++) {
        $label = sanitize_text_field($labels[$i]);
        $price = sanitize_text_field($prices[$i]);
        if ($label !== '' && $price !== '') {
            $options[] = ['label' => $label, 'price' => $price];
        }
    }
    update_post_meta($post_id, '_aleex_extra_options', $options);
});