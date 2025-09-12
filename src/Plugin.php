<?php

namespace DL\WooSellLimit;

defined('ABSPATH') || exit;

class Plugin
{

    public function __construct()
    {
        //Añadimos campos al producto
        $fields = new Fields();
        add_action('woocommerce_product_options_general_product_data', [$fields, 'add_custom_fields']);
        add_action('woocommerce_process_product_meta', [$fields, 'save_custom_fields']);

        //Validación en checkout
        $validation = new Validation();
        add_action('woocommerce_after_checkout_validation', [$validation, 'validate_stock_limit'], 10, 2);

        //Mensajes en frontend
        $frontend = new Frontend();
        add_action('wp_head', [$frontend, 'maybe_show_limit_message'], 60);
        add_action('woocommerce_before_add_to_cart_button', [$frontend, 'product_limit_notice']);
    }
}
