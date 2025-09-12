<?php

namespace DL\WooSellLimit;

defined('ABSPATH') || exit;

class Validation
{
    /**
     * Valida los límites de compra al procesar el pedido.
     * @param mixed $data
     * @param mixed $errors
     * @return void
     * @author Daniel Lucia
     */
    public function validate_stock_limit($data, $errors)
    {
        if (is_admin() && ! defined('DOING_AJAX')) {
            return;
        }

        $customer_id = get_current_user_id();
        $customer_email = isset($data['billing_email']) ? sanitize_email($data['billing_email']) : '';

        foreach (WC()->cart->get_cart() as $cart_item) {
            $product_id = $cart_item['product_id'];
            $quantity_in_cart = $cart_item['quantity'];

            $limit_quantity = get_post_meta($product_id, '_rw_stock_limit_quantity', true);
            $limit_period   = get_post_meta($product_id, '_rw_stock_limit_period', true);

            if (empty($limit_quantity) || empty($limit_period)) {
                continue;
            }

            $date_query = $this->get_period_start_date($limit_quantity, $limit_period);

            $purchased_qty = $this->get_customer_product_quantity($product_id, $customer_id, $date_query);

            if (($purchased_qty + $quantity_in_cart) > $limit_quantity) {
                $product = wc_get_product($product_id);
                $errors->add(
                    'rw_stock_limit_exceeded',
                    sprintf(
                        __('You have reached the purchase limit for "%1$s". Maximum allowed: %2$d %3$s.', 'dl-woo-sell-limit'),
                        $product ? $product->get_name() : __('this product', 'dl-woo-sell-limit'),
                        $limit_quantity,
                        $this->get_period_label($limit_period)
                    )
                );
            }
        }
    }

    /**
     * Obtiene la cantidad ya comprada por un cliente en un período para un producto.
     * @param mixed $product_id
     * @param mixed $customer_id
     * @param mixed $date_start
     * @return int
     * @author Daniel Lucia
     */
    public function get_customer_product_quantity($product_id, $customer_id, $date_start)
    {
        if (empty($product_id) || empty($customer_id) || empty($date_start)) {
            return 0;
        }

        $orders_args = array(
            'customer_id' => $customer_id,
            'status' => array('wc-completed', 'wc-processing'),
            'date_created' => '>=' . $date_start,
            'limit' => -1,
            'return' => 'ids'
        );

        $orders = wc_get_orders($orders_args);

        if (empty($orders)) {
            return 0;
        }

        $total_quantity = 0;

        foreach ($orders as $order_id) {
            $order = wc_get_order($order_id);

            if (!$order) {
                continue;
            }

            $order_items = $order->get_items();

            foreach ($order_items as $item) {
                $item_product_id = $item->get_product_id();
                $item_variation_id = $item->get_variation_id();

                if ($item_product_id == $product_id || $item_variation_id == $product_id) {
                    $total_quantity += $item->get_quantity();
                }
            }
        }

        return (int) $total_quantity;
    }

    /**
     * Texto legible del período.
     * @param mixed $period
     * @return string
     * @author Daniel Lucia
     */
    public function get_period_label($period)
    {
        switch ($period) {
            case 'day':
                return __('per day', 'dl-woo-sell-limit');
            case 'week':
                return __('per week', 'dl-woo-sell-limit');
            case 'month':
                return __('per month', 'dl-woo-sell-limit');
            case 'year':
                return __('per year', 'dl-woo-sell-limit');
            default:
                return '';
        }
    }

    /**
     * Obtiene la fecha de inicio del período.
     * @param int $quantity
     * @param string $period
     * @return string
     * @author Daniel Lucia
     */
    public function get_period_start_date(int $quantity, string $period)
    {
        $now = current_time('timestamp');
        $interval_spec = '';

        switch ($period) {
            case 'day':
            case 'days':
                $interval_spec = "P{$quantity}D";
                break;
            case 'week':
            case 'weeks':
                $interval_spec = "P" . ($quantity * 7) . "D";
                break;
            case 'month':
            case 'months':
                $interval_spec = "P{$quantity}M";
                break;
            case 'year':
            case 'years':
                $interval_spec = "P{$quantity}Y";
                break;
            default:
                $interval_spec = "P0D";
        }

        $date = new \DateTime();
        $date->setTimestamp($now);
        $date->sub(new \DateInterval($interval_spec));
        return $date->format('Y-m-d H:i:s');
    }
}
