<?php
declare(strict_types=1);

if ( PHP_SAPI !== 'cli' || ! getenv( 'CODEXAWP_TEST_WP_LOAD' ) ) {
	throw new RuntimeException( 'Set CODEXAWP_TEST_WP_LOAD to a disposable WordPress installation.' );
}
require getenv( 'CODEXAWP_TEST_WP_LOAD' );
if ( 'local' !== wp_get_environment_type() || ! function_exists( 'wc_create_order' ) ) {
	throw new RuntimeException( 'Use a local WooCommerce fixture.' );
}
require dirname( __DIR__ ) . '/examples/check.php';

$order = wc_create_order();
$order->set_currency( 'USD' );
foreach ( [ '25.00', '75.00' ] as $amount ) {
	$item = new WC_Order_Item_Product();
	$item->set_name( 'Synthetic order line' );
	$item->set_quantity( 1 );
	$item->set_total( $amount );
	$order->add_item( $item );
}
$order->set_total( '100.00' );
$order->save();
$fresh = wc_get_order( $order->get_id() );
$input = [];
foreach ( $fresh->get_items() as $id => $item ) {
	$input[ $id ] = [ 'revenue' => wc_format_decimal( $item->get_total(), 2 ), 'quantity' => (int) $item->get_quantity() ];
}
$result = $allocator->allocate( '10.01', $input, $fresh->get_currency(), 2 );
if ( array_values( $result ) !== [ '2.50', '7.51' ] ) {
	throw new RuntimeException( 'Unexpected allocation for native WooCommerce order lines.' );
}
echo "PASS: Persisted WooCommerce order lines receive exactly USD 10.01.\n";
