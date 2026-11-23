# MarginGuard Order Cost Allocation

A selected PHP engine from CodexaWP’s **MarginGuard 1.0.0** plugin. It distributes a shared order cost across line items in minor units, without losing a cent. Lines are weighted by revenue; a zero-revenue order uses the number of units instead.

Run `php examples/check.php` on PHP 8.2 or newer. Expected example output:

```text
USD 1.00 = 0.34 + 0.33 + 0.33
IRR 9 = 6 + 3
```

The fixture also computes the sum using decimal arithmetic. Follow [the original allocator](src/Engines/OrderAllocator.php) and [its money type](src/Engines/Money.php). The original scoped Brick Math dependency, its notice and provenance metadata are in [the dependency folder](src/Vendor/Brick/Math). The CodexaWP source is [GPL-2.0-or-later](LICENSE).
