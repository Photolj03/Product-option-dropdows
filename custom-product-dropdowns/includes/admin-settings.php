<?php
// This file handles the admin settings for the plugin, allowing users to configure dropdown options and their associated prices.

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Custom_Product_Dropdowns_Admin_Settings {
    
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    public function add_plugin_page() {
        add_menu_page(
            'Custom Product Dropdowns',
            'Dropdowns',
            'manage_options',
            'custom-product-dropdowns',
            array( $this, 'create_admin_page' ),
            'dashicons-list-view',
            100
        );
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>Custom Product Dropdowns</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'custom_product_dropdowns_group' );
                do_settings_sections( 'custom-product-dropdowns-admin' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function page_init() {
        register_setting(
            'custom_product_dropdowns_group',
            'custom_product_dropdowns_options'
        );

        add_settings_section(
            'setting_section_id',
            'Dropdown Options',
            null,
            'custom-product-dropdowns-admin'
        );

        add_settings_field(
            'dropdown_options',
            'Dropdown Options',
            array( $this, 'dropdown_options_callback' ),
            'custom-product-dropdowns-admin',
            'setting_section_id'
        );
    }

    public function dropdown_options_callback() {
        $options = get_option( 'custom_product_dropdowns_options' );
        ?>
        <textarea id="dropdown_options" name="custom_product_dropdowns_options[dropdown_options]" rows="10" cols="50"><?php echo isset( $options['dropdown_options'] ) ? esc_textarea( $options['dropdown_options'] ) : ''; ?></textarea>
        <p class="description">Enter dropdown options in the format: Option Name|Price (one per line).</p>
        <?php
    }
}



// Add a submenu under WooCommerce for category dropdowns
add_action('admin_menu', function() {
    add_submenu_page(
        'woocommerce',
        'Category Dropdowns',
        'Category Dropdowns',
        'manage_woocommerce',
        'cpd-category-dropdowns',
        'cpd_category_dropdowns_page'
    );
});

function cpd_category_dropdowns_page() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('cpd_save_category_dropdowns')) {
        if (!empty($_POST['cpd_category_dropdowns']) && is_array($_POST['cpd_category_dropdowns'])) {
            foreach ($_POST['cpd_category_dropdowns'] as $cat_id => $dropdowns) {
                $dropdowns = array_values(array_filter($dropdowns, function($d){
                    return !empty($d['label']) && !empty($d['options']);
                }));
                update_option('cpd_dropdowns_cat_' . intval($cat_id), wp_json_encode($dropdowns));
            }
            echo '<div class="updated"><p>Saved!</p></div>';
        }
    }

    $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
    ?>
    <div class="wrap">
        <h1>Category Dropdowns</h1>
        <form method="post">
            <?php wp_nonce_field('cpd_save_category_dropdowns'); ?>
            <table class="form-table">
                <tbody>
                <?php foreach ($terms as $term): 
                    $dropdowns = get_option('cpd_dropdowns_cat_' . $term->term_id, '');
                    $dropdowns = $dropdowns ? json_decode($dropdowns, true) : [];
                ?>
                    <tr>
                        <th scope="row"><?php echo esc_html($term->name); ?></th>
                        <td>
                            <div class="cpd_cat_dropdowns_wrapper_<?php echo $term->term_id; ?>">
                                <?php if (!empty($dropdowns)): foreach ($dropdowns as $i => $dropdown): ?>
                                    <p>
                                        <input type="text" name="cpd_category_dropdowns[<?php echo $term->term_id; ?>][<?php echo $i; ?>][label]" value="<?php echo esc_attr($dropdown['label']); ?>" placeholder="Dropdown Label" style="width:30%;" />
                                        <textarea name="cpd_category_dropdowns[<?php echo $term->term_id; ?>][<?php echo $i; ?>][options]" rows="2" cols="40" placeholder="Option|Price per line"><?php echo esc_textarea($dropdown['options']); ?></textarea>
                                        <button class="button cpd-remove-dropdown" type="button">Remove</button>
                                    </p>
                                <?php endforeach; endif; ?>
                            </div>
                            <button class="button cpd_add_cat_dropdown" data-term="<?php echo $term->term_id; ?>" type="button">Add Dropdown</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php submit_button('Save Category Dropdowns'); ?>
        </form>
    </div>
    <script>
    jQuery(function($){
        $('.cpd_add_cat_dropdown').on('click', function(){
            var term = $(this).data('term');
            var wrapper = $('.cpd_cat_dropdowns_wrapper_' + term);
            var i = wrapper.find('p').length;
            wrapper.append(
                '<p>' +
                '<input type="text" name="cpd_category_dropdowns['+term+']['+i+'][label]" placeholder="Dropdown Label" style="width:30%;" /> ' +
                '<textarea name="cpd_category_dropdowns['+term+']['+i+'][options]" rows="2" cols="40" placeholder="Option|Price per line"></textarea> ' +
                '<button class="button cpd-remove-dropdown" type="button">Remove</button>' +
                '</p>'
            );
        });
        $(document).on('click', '.cpd-remove-dropdown', function(){
            $(this).parent().remove();
        });
    });
    </script>
    <?php
}

// Add multiple dropdown fields to product edit page
add_action('woocommerce_product_options_general_product_data', function() {
    global $post;
    $dropdowns = get_post_meta($post->ID, '_cpd_dropdowns', true);
    $dropdowns = $dropdowns ? json_decode($dropdowns, true) : [];

    echo '<div id="cpd_dropdowns_wrapper">';
    if (!empty($dropdowns)) {
        foreach ($dropdowns as $i => $dropdown) {
            ?>
            <p>
                <input type="text" name="cpd_dropdowns[<?php echo $i; ?>][label]" value="<?php echo esc_attr($dropdown['label']); ?>" placeholder="Dropdown Label" style="width:30%;" />
                <textarea name="cpd_dropdowns[<?php echo $i; ?>][options]" rows="2" cols="40" placeholder="Option|Price per line"><?php echo esc_textarea($dropdown['options']); ?></textarea>
                <button class="button cpd-remove-dropdown" type="button">Remove</button>
            </p>
            <?php
        }
    }
    ?>
    </div>
    <button class="button" id="cpd_add_dropdown" type="button">Add Dropdown</button>
    <p class="description">Each dropdown: label + options (one per line, format: Option|Price)</p>
    <script>
    jQuery(function($){
        $('#cpd_add_dropdown').on('click', function(){
            var i = $('#cpd_dropdowns_wrapper p').length;
            $('#cpd_dropdowns_wrapper').append(
                '<p>' +
                '<input type="text" name="cpd_dropdowns['+i+'][label]" placeholder="Dropdown Label" style="width:30%;" /> ' +
                '<textarea name="cpd_dropdowns['+i+'][options]" rows="2" cols="40" placeholder="Option|Price per line"></textarea> ' +
                '<button class="button cpd-remove-dropdown" type="button">Remove</button>' +
                '</p>'
            );
        });
        $(document).on('click', '.cpd-remove-dropdown', function(){
            $(this).parent().remove();
        });
    });
    </script>
    <?php
});

// Save multiple dropdowns for product
add_action('woocommerce_process_product_meta', function($post_id) {
    if (isset($_POST['cpd_dropdowns'])) {
        $dropdowns = array_values(array_filter($_POST['cpd_dropdowns'], function($d){
            return !empty($d['label']) && !empty($d['options']);
        }));
        update_post_meta($post_id, '_cpd_dropdowns', wp_json_encode($dropdowns));
    } else {
        delete_post_meta($post_id, '_cpd_dropdowns');
    }
});

if ( is_admin() ) {
    $custom_product_dropdowns_admin_settings = new Custom_Product_Dropdowns_Admin_Settings();
}
?>