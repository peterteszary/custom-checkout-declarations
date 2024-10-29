<?php
/*
Plugin Name: Custom Checkout Declarations
Plugin URI: https://github.com/peterteszary/custom-checkout-declarations
Description: Lehetővé teszi a pénztári nyilatkozatok, adatátviteli és kártyaregisztrációs hivatkozások szerkesztését.
Version: 1.0
Author: Teszáry Péter
Author URI: https://peterteszary.com
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires Plugins: WooCommerce
*/

// Register settings page in WordPress admin
add_action( 'admin_menu', 'custom_checkout_declarations_menu' );
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
                settings_fields( 'custom_checkout_declarations_group' );
                do_settings_sections( 'custom-checkout-declarations' );
                submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
add_action( 'admin_init', 'custom_checkout_declarations_settings' );
function custom_checkout_declarations_settings() {
    register_setting( 'custom_checkout_declarations_group', 'data_transfer_declaration' );
    register_setting( 'custom_checkout_declarations_group', 'data_transfer_link' );
    register_setting( 'custom_checkout_declarations_group', 'card_registration_declaration' );
    register_setting( 'custom_checkout_declarations_group', 'card_registration_link' );

    add_settings_section( 'custom_checkout_declarations_section', 'Nyilatkozatok', null, 'custom-checkout-declarations' );

    add_settings_field(
        'data_transfer_declaration',
        'Adattovábbítási nyilatkozat',
        'data_transfer_declaration_callback',
        'custom-checkout-declarations',
        'custom_checkout_declarations_section'
    );

    add_settings_field(
        'data_transfer_link',
        'Adattovábbítási nyilatkozat link',
        'data_transfer_link_callback',
        'custom-checkout-declarations',
        'custom_checkout_declarations_section'
    );

    add_settings_field(
        'card_registration_declaration',
        'Kártyaregisztrációs nyilatkozat',
        'card_registration_declaration_callback',
        'custom-checkout-declarations',
        'custom_checkout_declarations_section'
    );

    add_settings_field(
        'card_registration_link',
        'Kártyaregisztrációs nyilatkozat link',
        'card_registration_link_callback',
        'custom-checkout-declarations',
        'custom_checkout_declarations_section'
    );
}

// Field callbacks
function data_transfer_declaration_callback() {
    $value = get_option( 'data_transfer_declaration', '' );
    echo '<textarea name="data_transfer_declaration" rows="5" style="width:100%;">' . esc_textarea( $value ) . '</textarea>';
}

function data_transfer_link_callback() {
    $value = get_option( 'data_transfer_link', '' );
    echo '<input type="text" name="data_transfer_link" value="' . esc_attr( $value ) . '" style="width:100%;">';
}

function card_registration_declaration_callback() {
    $value = get_option( 'card_registration_declaration', '' );
    echo '<textarea name="card_registration_declaration" rows="3" style="width:100%;">' . esc_textarea( $value ) . '</textarea>';
}

function card_registration_link_callback() {
    $value = get_option( 'card_registration_link', '' );
    echo '<input type="text" name="card_registration_link" value="' . esc_attr( $value ) . '" style="width:100%;">';
}

// Add declarations to checkout page
add_action( 'woocommerce_review_order_before_submit', 'add_custom_checkout_declarations' );
function add_custom_checkout_declarations() {
    $data_transfer_text = get_option( 'data_transfer_declaration', 'Default data transfer declaration text.' );
    $data_transfer_link = get_option( 'data_transfer_link', '#' );
    $card_registration_text = get_option( 'card_registration_declaration', 'Default card registration declaration text.' );
    $card_registration_link = get_option( 'card_registration_link', '#' );

    echo '<div id="custom-checkout-declarations" style="margin-bottom: 20px;">';

    woocommerce_form_field( 'data_transfer_declaration_checkbox', array(
        'type'      => 'checkbox',
        'class'     => array('input-checkbox'),
        'label'     => sprintf(
            '%s <a href="%s" target="_blank">Adattovábbítási nyilatkozat</a>',
            esc_html( $data_transfer_text ),
            esc_url( $data_transfer_link )
        ),
        'required'  => true,
    ),  WC()->checkout->get_value( 'data_transfer_declaration_checkbox' ) );

    woocommerce_form_field( 'card_registration_declaration_checkbox', array(
        'type'      => 'checkbox',
        'class'     => array('input-checkbox'),
        'label'     => sprintf(
            '%s <a href="%s" target="_blank">Kártyaregisztrációs nyilatkozat</a>',
            esc_html( $card_registration_text ),
            esc_url( $card_registration_link )
        ),
        'required'  => true,
    ),  WC()->checkout->get_value( 'card_registration_declaration_checkbox' ) );

    echo '</div>';
}

// Save the declarations' status
add_action( 'woocommerce_checkout_update_order_meta', 'save_custom_checkout_declarations', 10, 1 );
function save_custom_checkout_declarations( $order_id ) {
    if ( ! empty( $_POST['data_transfer_declaration_checkbox'] ) ) {
        update_post_meta( $order_id, 'data_transfer_declaration_checkbox', $_POST['data_transfer_declaration_checkbox'] );
    }
    if ( ! empty( $_POST['card_registration_declaration_checkbox'] ) ) {
        update_post_meta( $order_id, 'card_registration_declaration_checkbox', $_POST['card_registration_declaration_checkbox'] );
    }
}

// Display the declarations' status in admin order edit page
add_action( 'woocommerce_admin_order_data_after_billing_address', 'display_checkout_declarations_on_order_edit', 10, 1 );
function display_checkout_declarations_on_order_edit( $order ) {
    $data_transfer_declaration = get_post_meta( $order->get_id(), 'data_transfer_declaration_checkbox', true );
    $card_registration_declaration = get_post_meta( $order->get_id(), 'card_registration_declaration_checkbox', true );

    if ( $data_transfer_declaration ) {
        echo '<p>Adattovábbítási nyilatkozat: <span style="color:green;">Elfogadva</span></p>';
    }

    if ( $card_registration_declaration ) {
        echo '<p>Kártyaregisztrációs nyilatkozat: <span style="color:green;">Elfogadva</span></p>';
    }
}
?>
