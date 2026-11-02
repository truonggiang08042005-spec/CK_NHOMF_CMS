<?php
/**
 * Orders template override for NEM Fashion
 *
 * @package WooCommerce\Templates
 * @version 9.5.0
 */

defined( 'ABSPATH' ) || exit;
// WooCommerce supplies the current customer's query and checks access to order actions.
if ( isset( $customer_orders, $has_orders ) && function_exists( 'WC' ) ) {
    include WC()->plugin_path() . '/templates/myaccount/orders.php';
    return;
}
?>

<div class="nem-orders-wrap">
    <div class="nem-orders-empty">customer.orders.none</div>
</div>
