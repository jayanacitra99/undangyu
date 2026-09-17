<?php

declare(strict_types=1);

namespace App\Services\Payment\Data;

/**
 * The outcome of a refund call (docs/05 § 6).
 *
 * A failed refund is a returned value, not an exception: the admin screen that
 * asks for one needs the gateway's reason to show, and the local record has to
 * be written either way.
 */
final readonly class RefundResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public bool $successful,
        public ?string $reference = null,
        // Decimal string, like every other money value in the system.
        public ?string $amount = null,
        public ?string $message = null,
        public array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function failed(string $message, array $raw = []): self
    {
        return new self(successful: false, message: $message, raw: $raw);
    }
}
