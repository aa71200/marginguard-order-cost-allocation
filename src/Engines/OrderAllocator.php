<?php
declare( strict_types=1 );

namespace MarginGuard\Engines;

use MarginGuard\Vendor\Brick\Math\BigDecimal;
use MarginGuard\Vendor\Brick\Math\BigInteger;
use MarginGuard\Vendor\Brick\Math\RoundingMode;
use InvalidArgumentException;

/** Allocates a non-negative shared amount without losing a minor unit. */
final class OrderAllocator {
	/**
	 * @param array<string,array{revenue:string,quantity:int}> $lines
	 * @return array<string,string>
	 */
	public function allocate( string $amount, array $lines, string $currency, int $scale ): array {
		if ( [] === $lines ) {
			return [];
		}
		$total = Money::of( $amount, $currency, $scale );
		if ( $total->decimalValue()->isNegative() ) {
			throw new InvalidArgumentException( 'Negative shared cost.' );
		}
		$weights = [];
		foreach ( $lines as $key => $line ) {
			$revenue = Money::of( $line['revenue'], $currency, $scale )->decimalValue();
			if ( $revenue->isNegative() || $line['quantity'] < 1 ) {
				throw new InvalidArgumentException( 'Invalid allocation weight.' );
			}
			$weights[ $key ] = $revenue;
		}
		$sum = array_reduce( $weights, static fn( BigDecimal $carry, BigDecimal $value ): BigDecimal => $carry->plus( $value ), BigDecimal::zero() );
		if ( $sum->isZero() ) {
			foreach ( $lines as $key => $line ) {
				$weights[ $key ] = BigDecimal::of( $line['quantity'] );
			}
			$sum = array_reduce( $weights, static fn( BigDecimal $carry, BigDecimal $value ): BigDecimal => $carry->plus( $value ), BigDecimal::zero() );
		}
		$minor = BigInteger::of( $total->minor() );
		$sumMinor = BigInteger::of( (string) $sum->multipliedBy( BigDecimal::of( 10 )->power( $scale ) )->toScale( 0, RoundingMode::UNNECESSARY ) );
		$floor = [];
		$remainders = [];
		$allocated = BigInteger::zero();
		foreach ( $weights as $key => $weight ) {
			$weightMinor = BigInteger::of( (string) $weight->multipliedBy( BigDecimal::of( 10 )->power( $scale ) )->toScale( 0, RoundingMode::UNNECESSARY ) );
			$numerator = $minor->multipliedBy( $weightMinor );
			$floor[ $key ] = $numerator->quotient( $sumMinor );
			$remainders[ $key ] = $numerator->remainder( $sumMinor );
			$allocated = $allocated->plus( $floor[ $key ] );
		}
		uksort( $remainders, static function ( string $a, string $b ) use ( $remainders ): int {
			$comparison = $remainders[ $b ]->compareTo( $remainders[ $a ] );
			return 0 === $comparison ? strcmp( $a, $b ) : $comparison;
		} );
		$remaining = (int) (string) $minor->minus( $allocated );
		foreach ( array_keys( $remainders ) as $key ) {
			if ( $remaining-- <= 0 ) {
				break;
			}
			$floor[ $key ] = $floor[ $key ]->plus( 1 );
		}
		$result = [];
		foreach ( $floor as $key => $units ) {
			$result[ $key ] = (string) BigDecimal::of( (string) $units )->dividedBy( BigDecimal::of( 10 )->power( $scale ), $scale, RoundingMode::UNNECESSARY );
		}
		return $result;
	}
}
