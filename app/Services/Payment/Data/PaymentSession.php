<?php

declare(strict_types=1);

namespace App\Services\Payment\Data;

/**
 * What a gateway hands back when a transaction is opened (docs/05 § 6).
 *
 * `reference` becomes `payments.gateway_ref` — the idempotency key a webhook
 * looks the row up by. `redirectUrl` is where the client is sent; `token` is
 * what a popup would need, and is null for drivers that only redirect.
 */
final readonly class PaymentSession
{
    /**
     * @param  array<string, mixed>  $raw  the gateway's own response, stored for disputes
     */
    public function __construct(
        public string $reference,
        public ?string $redirectUrl = null,
        public ?string $token = null,
        public array $raw = [],
    ) {}
}
