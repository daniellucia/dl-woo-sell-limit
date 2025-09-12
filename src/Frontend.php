<?php

namespace DL\WooSellLimit;

defined('ABSPATH') || exit;

class Frontend
{
    /**
     * Muestra mensaje en el frontend si el usuario ha alcanzado el límite de compra.
     * @return void
     * @author Daniel Lucia
     */
    public function maybe_show_limit_message()
    {
        if (is_admin()) {
            return;
        }

        if (! is_user_logged_in()) {
            return;
        }

        $customer_id = get_current_user_id();

        $args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => '_rw_stock_limit_quantity',
                    'compare' => 'EXISTS',
                ],
            ],
        ];

        $query = new \WP_Query($args);
        $showed = false;

        if ($query->have_posts()) {
            while ($query->have_posts()) {

                if ($showed) {
                    break;
                }

                $query->the_post();
                $product_id = get_the_ID();
                $limit_quantity = get_post_meta($product_id, '_rw_stock_limit_quantity', true);
                $limit_period = get_post_meta($product_id, '_rw_stock_limit_period', true);

                if (empty($limit_quantity) || empty($limit_period)) {
                    continue;
                }

                $validation = new Validation();
                $date_start = $validation->get_period_start_date($limit_quantity, $limit_period);
                $purchased_qty = $validation->get_customer_product_quantity($product_id, $customer_id, $date_start);

                if ($purchased_qty >= $limit_quantity) {

                    $showed = true;
                    $period_end = $this->get_period_end_date($limit_period, $date_start);
                    $remaining  = $period_end - time();

                    //if ($remaining > 0) {
                        $remaining_text = human_time_diff(time(), $period_end);

                        printf(
                            '<div class="woocommerce-info" style="margin: 0;position: fixed;left: 20px;bottom: 20px;right: 20px;z-index: 999;text-align: center;">%s</div>',
                            sprintf(
                                __('You have reached the purchase limit for this product. You will be able to buy it again in %s.', 'rw-stock-limit'),
                                $remaining_text
                            )
                        );
                    //}
                }
            }
            wp_reset_postdata();
        }
    }

    /**
     * Muestra aviso en la página del producto si el usuario ha alcanzado el límite de compra.
     * @return void
     * @author Daniel Lucia
     */
    public function product_limit_notice()
    {
        global $product;

        if (! $product instanceof \WC_Product) {
            return;
        }

        if (! is_user_logged_in()) {
            return;
        }

        $product_id = $product->get_id();
        $limit_quantity = get_post_meta($product_id, '_rw_stock_limit_quantity', true);
        $limit_period = get_post_meta($product_id, '_rw_stock_limit_period', true);

        if (empty($limit_quantity) || empty($limit_period)) {
            return;
        }

        $customer_id = get_current_user_id();

        if (! $customer_id) {
            return;
        }

        $validation = new Validation();
        $date_start = $validation->get_period_start_date($limit_quantity, $limit_period);
        $purchased_qty = $validation->get_customer_product_quantity($product_id, $customer_id, $date_start);

        if ($purchased_qty >= $limit_quantity) {
            $period_end = $this->get_period_end_date($limit_period, $date_start);
            //$remaining = $period_end - time();

            //if ($remaining > 0) {
                
                $remaining_text = human_time_diff(time(), $period_end);

                printf(
                    '<div class="woocommerce-info" style="margin-top:10px;">%s</div>',
                    sprintf(
                        __('You have reached the purchase limit for this product. You will be able to buy it again in %s.', 'rw-stock-limit'),
                        $remaining_text
                    )
                );
            //}
        }
    }

    /**
     * Fecha de fin del período.
     * @param mixed $period
     * @param mixed $date_start
     * @author Daniel Lucia
     */
    private function get_period_end_date($period, $date_start)
    {
        $timestamp = strtotime($date_start);

        switch ($period) {
            case 'day':
            case 'days':
                return strtotime('+1 day', $timestamp);
            case 'week':
            case 'weeks':
                return strtotime('+1 week', $timestamp);
            case 'month':
            case 'months':
                return strtotime('+1 month', $timestamp);
            case 'year':
            case 'years':
                return strtotime('+1 year', $timestamp);
            default:
                return $timestamp;
        }
    }
}
