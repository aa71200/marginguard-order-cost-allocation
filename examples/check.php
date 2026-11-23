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
$weights = static fn( string $first, string $second ): array => [
	'a' => [ 'revenue' => $first, 'quantity' => 1 ],
	'b' => [ 'revenue' => $second, 'quantity' => 1 ],
];
$cases = [
	[ 'USD', 2, '1.00', [ 'a' => [ 'revenue' => '1.00', 'quantity' => 1 ], 'b' => [ 'revenue' => '1.00', 'quantity' => 1 ], 'c' => [ 'revenue' => '1.00', 'quantity' => 1 ] ], [ 'a' => '0.34', 'b' => '0.33', 'c' => '0.33' ] ],
	[ 'IRR', 0, '9', [ 'a' => [ 'revenue' => '0', 'quantity' => 2 ], 'b' => [ 'revenue' => '0', 'quantity' => 1 ] ], [ 'a' => '6', 'b' => '3' ] ],
	[ 'BHD', 3, '0.001', $weights( '0.003', '0.003' ), [ 'a' => '0.001', 'b' => '0.000' ] ],
	[ 'USD', 2, '10.00', $weights( '3', '7' ), [ 'a' => '3.00', 'b' => '7.00' ] ],
	[ 'USD', 2, '0.00', $weights( '3', '7' ), [ 'a' => '0.00', 'b' => '0.00' ] ],
	[ 'IRR', 0, '100000000000000000000', $weights( '1', '3' ), [ 'a' => '25000000000000000000', 'b' => '75000000000000000000' ] ],
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
	$reversed = $allocator->allocate( $total, array_reverse( $input, true ), $currency, $scale );
	ksort( $reversed );
	if ( $reversed !== $expected ) {
		throw new RuntimeException( 'Line order changed the allocation.' );
	}
	echo "$currency $total = " . implode( ' + ', $result ) . "\n";
}
foreach ( [ [ '-1.00', 2 ], [ '0.00', 19 ], [ 'not money', 2 ] ] as [ $amount, $scale ] ) {
	try {
		$allocator->allocate( $amount, $weights( '3', '7' ), 'USD', $scale );
		throw new RuntimeException( 'Invalid money input was accepted.' );
	} catch ( InvalidArgumentException $error ) {
		echo "Rejected invalid money input.\n";
	}
}
