<?php

declare(strict_types=1);

namespace App\Services\Payment\Data;

use App\Enums\PaymentStatus;

/**
 * One webhook, translated (docs/05 § 6).
 *
 * The handler in Session 10 reads only this — never the gateway's own field
 * names, never its own status vocabulary.
 */
final readonly class PaymentUpdate
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $reference,
        public PaymentStatus $status,
        public ?string $method = null,
        public ?float $amount = null,
        public ?string $paidAt = null,
        public array $raw = [],
    ) {}
}
