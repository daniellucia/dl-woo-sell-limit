<?php

namespace DL\WooSellLimit;

defined('ABSPATH') || exit;

class Fields
{

    /**
     * Añadimos campos al producto
     * @return void
     * @author Daniel Lucia
     */
    public function add_custom_fields()
    {
        echo '<div class="options_group">';

        woocommerce_wp_text_input([
            'id'          => '_rw_stock_limit_quantity',
            'label'       => __('Limit per customer', 'dl-woo-sell-limit'),
            'desc_tip'    => true,
            'description' => __('Maximum number of units a customer can purchase in the defined period.', 'dl-woo-sell-limit'),
            'type'        => 'number',
            'custom_attributes' => [
                'min'  => '0',
                'step' => '1',
            ],
        ]);

        woocommerce_wp_select([
            'id'      => '_rw_stock_limit_period',
            'label'   => __('Limit period', 'dl-woo-sell-limit'),
            'options' => [
                ''       => __('No limit', 'dl-woo-sell-limit'),
                'day'    => __('Day', 'dl-woo-sell-limit'),
                'week'   => __('Week', 'dl-woo-sell-limit'),
                'month'  => __('Month', 'dl-woo-sell-limit'),
                'year'   => __('Year', 'dl-woo-sell-limit'),
            ],
        ]);

        //Mostramos ejemplo
        $this->show_example();

        echo '</div>';
    }

    /**
     * Guarda los valores de los campos.
     * @param mixed $post_id
     * @return void
     * @author Daniel Lucia
     */
    public function save_custom_fields($post_id)
    {
        $quantity = isset($_POST['_rw_stock_limit_quantity']) ? intval($_POST['_rw_stock_limit_quantity']) : '';
        $period   = isset($_POST['_rw_stock_limit_period']) ? sanitize_text_field($_POST['_rw_stock_limit_period']) : '';

        if ($quantity !== '') {
            update_post_meta($post_id, '_rw_stock_limit_quantity', $quantity);
        } else {
            delete_post_meta($post_id, '_rw_stock_limit_quantity');
        }

        if ($period !== '') {
            update_post_meta($post_id, '_rw_stock_limit_period', $period);
        } else {
            delete_post_meta($post_id, '_rw_stock_limit_period');
        }
    }

    private function show_example()
    {
        $quantity = get_post_meta(get_the_ID(), '_rw_stock_limit_quantity', true);
        $period   = get_post_meta(get_the_ID(), '_rw_stock_limit_period', true);

        if ($quantity && $period) {
            
            $validation = new Validation();
            $label = $validation->get_period_label($period);

            printf(
                '<p class="form-field"><em>%s</em></p>',
                sprintf(
                    __('Example: Maximum %1$d units per customer %2$s.', 'dl-woo-sell-limit'),
                    intval($quantity),
                    $label
                )
            );
        } else {
            echo '<p class="form-field"><em>' . __('Example: Maximum X units per customer per X.', 'dl-woo-sell-limit') . '</em></p>';
        }
    }
}
