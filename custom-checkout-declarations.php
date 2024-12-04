<?php
/*
Plugin Name: Custom Checkout Declarations
Plugin URI: https://github.com/peterteszary/custom-checkout-declarations
Description: Lehetővé teszi a pénztári nyilatkozatok (beleértve a linkekkel ellátottakat is) és azok kötelező érvényesítését.
Version: 1.3
Author: Teszáry Péter
Author URI: https://peterteszary.com
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires Plugins: WooCommerce
*/

// Register settings page in WordPress admin
add_action('admin_menu', 'custom_checkout_declarations_menu');
function custom_checkout_declarations_menu() {
    add_menu_page(
        'Checkout Declarations',
        'Checkout Declarations',
        'manage_options',
        'custom-checkout-declarations',
        'custom_checkout_declarations_settings_page',
        'dashicons-clipboard',
        20
    );
}

// Settings page content
function custom_checkout_declarations_settings_page() {
    ?>
    <div class="wrap">
        <h1>Checkout Declarations Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('custom_checkout_declarations_group');
            do_settings_sections('custom-checkout-declarations');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
add_action('admin_init', 'custom_checkout_declarations_settings');
function custom_checkout_declarations_settings() {
    $fields = [
        'data_transfer_declaration' => 'Adatkezelési tájékoztató szöveg',
        'data_transfer_link' => 'Adatkezelési tájékoztató link',
        'card_registration_declaration' => 'Kártyaregisztrációs nyilatkozat szöveg',
        'card_registration_link' => 'Kártyaregisztrációs nyilatkozat link',
        'data_sharing_declaration' => 'Adattovábbítási nyilatkozat szöveg',
        'data_sharing_link' => 'Adattovábbítási nyilatkozat link'
    ];

    foreach ($fields as $key => $label) {
        register_setting('custom_checkout_declarations_group', $key);
        add_settings_field(
            $key,
            $label,
            function () use ($key) {
                $value = get_option($key, '');
                $type = strpos($key, 'link') !== false ? 'text' : 'textarea';
                if ($type === 'text') {
                    echo '<input type="text" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" style="width:100%;">';
                } else {
                    echo '<textarea name="' . esc_attr($key) . '" rows="3" style="width:100%;">' . esc_textarea($value) . '</textarea>';
                }
            },
            'custom-checkout-declarations',
            'custom_checkout_declarations_section'
        );
    }

    add_settings_section('custom_checkout_declarations_section', 'Nyilatkozatok', null, 'custom-checkout-declarations');
}

// Add declarations to checkout page
add_action('woocommerce_review_order_before_submit', 'add_custom_checkout_declarations');
function add_custom_checkout_declarations() {
    $fields = [
        'data_transfer_declaration_checkbox' => [
            'text' => get_option('data_transfer_declaration', 'Adatkezelési tájékoztató elfogadása'),
            'link' => get_option('data_transfer_link', '#')
        ],
        'card_registration_declaration_checkbox' => [
            'text' => get_option('card_registration_declaration', 'Kártyaregisztrációs nyilatkozat elfogadása'),
            'link' => get_option('card_registration_link', '#')
        ],
        'data_sharing_declaration_checkbox' => [
            'text' => get_option('data_sharing_declaration', 'Adattovábbítási nyilatkozat elfogadása'),
            'link' => get_option('data_sharing_link', '#')
        ]
    ];

    echo '<div id="custom-checkout-declarations" style="margin-bottom: 20px;">';
    foreach ($fields as $key => $field) {
        woocommerce_form_field($key, [
            'type' => 'checkbox',
            'class' => ['input-checkbox'],
            'label' => sprintf(
                '%s <a href="%s" target="_blank">%s</a>',
                esc_html($field['text']),
                esc_url($field['link']),
                esc_html('link')
            ),
            'required' => true
        ]);
    }
    echo '</div>';
}

// Validation for checkout declarations
add_action('woocommerce_checkout_process', 'validate_custom_checkout_declarations');
function validate_custom_checkout_declarations() {
    $fields = [
        'data_transfer_declaration_checkbox' => 'Adatkezelési tájékoztató elfogadása kötelező!',
        'card_registration_declaration_checkbox' => 'Kártyaregisztrációs nyilatkozat elfogadása kötelező!',
        'data_sharing_declaration_checkbox' => 'Adattovábbítási nyilatkozat elfogadása kötelező!'
    ];

    foreach ($fields as $key => $error_message) {
        if (!isset($_POST[$key])) {
            wc_add_notice(__($error_message, 'woocommerce'), 'error');
        }
    }
}

// Save the declarations' status
add_action('woocommerce_checkout_update_order_meta', 'save_custom_checkout_declarations');
function save_custom_checkout_declarations($order_id) {
    $fields = [
        'data_transfer_declaration_checkbox',
        'card_registration_declaration_checkbox',
        'data_sharing_declaration_checkbox'
    ];

    foreach ($fields as $field) {
        update_post_meta($order_id, $field, isset($_POST[$field]) ? 'accepted' : 'not accepted');
    }
}
?>
