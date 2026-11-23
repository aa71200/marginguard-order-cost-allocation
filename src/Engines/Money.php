<?php
declare( strict_types=1 );

namespace MarginGuard\Engines;

use MarginGuard\Vendor\Brick\Math\BigDecimal;
use MarginGuard\Vendor\Brick\Math\RoundingMode;
use InvalidArgumentException;

/** A currency amount with an explicit, immutable decimal scale. */
final class Money {
	private function __construct(
		public readonly string $currency,
		public readonly int $scale,
		private readonly BigDecimal $value
	) {}

	public static function of( string $amount, string $currency, int $scale ): self {
		if ( ! preg_match( '/^-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?$/D', $amount ) || ! preg_match( '/^[A-Z]{3}$/D', $currency ) || $scale < 0 || $scale > 18 ) {
			throw new InvalidArgumentException( 'Invalid money value, currency or scale.' );
		}
		$value = BigDecimal::of( $amount );
		if ( strlen( ltrim( (string) $value->getIntegralPart(), '-' ) ) > 30 ) {
			throw new InvalidArgumentException( 'Money exceeds supported magnitude.' );
		}
		return new self( $currency, $scale, $value->toScale( $scale, RoundingMode::UNNECESSARY ) );
	}

	public function decimal(): string {
		return (string) $this->value;
	}

	public function decimalValue(): BigDecimal {
		return $this->value;
	}

	public function minor(): string {
		return (string) $this->value->multipliedBy( BigDecimal::of( 10 )->power( $this->scale ) )->toScale( 0, RoundingMode::UNNECESSARY );
	}
}
