# MarginGuard Order Cost Allocation

**What to try.** Run the PHP examples to see how shared costs are allocated without losing small currency units.

[![Standalone PHP checks](https://github.com/aa71200/marginguard-order-cost-allocation/actions/workflows/checks.yml/badge.svg?branch=main)](https://github.com/aa71200/marginguard-order-cost-allocation/actions/workflows/checks.yml)

Dividing $1.00 across three order lines can lose a cent if each share is rounded independently. The **MarginGuard 1.0.0** allocator preserves the original total. It weights shares by revenue and uses quantities when revenue is zero.

Run `php examples/check.php` on PHP 8.2 or newer. Expected example output:

```text
USD 1.00 = 0.34 + 0.33 + 0.33
IRR 9 = 6 + 3
```

The fixture also computes the sum using decimal arithmetic. Follow [the original allocator](src/Engines/OrderAllocator.php) and [its money type](src/Engines/Money.php). The original scoped Brick Math dependency, its notice and provenance metadata are in [the dependency folder](src/Vendor/Brick/Math). The CodexaWP source is [GPL-2.0-or-later](LICENSE).

[Verification output](docs/verification.txt)

The extended fixture covers 0-, 2- and 3-decimal currency scales, unequal weights, zero cost, a stable tie-break regardless of input order, values beyond native integer capacity and invalid inputs. Set `CODEXAWP_TEST_WP_LOAD` to the `wp-load.php` of a disposable WooCommerce site and run `php tests/check.php` to allocate money against persisted synthetic order lines.
