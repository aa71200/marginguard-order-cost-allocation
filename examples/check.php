<?php
declare(strict_types=1);

use MarginGuard\Engines\OrderAllocator;

spl_autoload_register( static function ( string $class ): void {
	$prefix = 'MarginGuard\\';
	if ( str_starts_with( $class, $prefix ) ) {
		require __DIR__ . '/../src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
	}
} );

$allocator = new OrderAllocator();
$cases = [
	[ 'USD', 2, '1.00', [ 'a' => [ 'revenue' => '1.00', 'quantity' => 1 ], 'b' => [ 'revenue' => '1.00', 'quantity' => 1 ], 'c' => [ 'revenue' => '1.00', 'quantity' => 1 ] ], [ 'a' => '0.34', 'b' => '0.33', 'c' => '0.33' ] ],
	[ 'IRR', 0, '9', [ 'a' => [ 'revenue' => '0', 'quantity' => 2 ], 'b' => [ 'revenue' => '0', 'quantity' => 1 ] ], [ 'a' => '6', 'b' => '3' ] ],
];
foreach ( $cases as [ $currency, $scale, $total, $input, $expected ] ) {
	$result = $allocator->allocate( $total, $input, $currency, $scale );
	$sum = array_reduce(
		$result,
		static fn( $value, string $part ) => $value->plus( $part ),
		\MarginGuard\Vendor\Brick\Math\BigDecimal::zero()
	);
	if ( $result !== $expected || ! $sum->isEqualTo( $total ) ) {
		throw new RuntimeException( 'Allocation changed or lost a minor unit.' );
	}
	echo "$currency $total = " . implode( ' + ', $result ) . "\n";
}
