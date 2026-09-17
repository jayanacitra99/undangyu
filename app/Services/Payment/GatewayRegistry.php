<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Services\Payment\Contracts\PaymentGateway;

/**
 * Resolves a driver by name rather than from `config('payment.driver')`.
 *
 * Checkout uses the one configured gateway, but a webhook arrives at
 * /webhooks/{gateway} and has to be answered by the driver that sent it — a
 * settlement for a gateway we have since stopped using is still real money.
 */
final class GatewayRegistry
{
    public function has(string $name): bool
    {
        /** @var array<string, class-string<PaymentGateway>> $drivers */
        $drivers = config('payment.drivers', []);

        return isset($drivers[$name]);
    }

    public function driver(string $name): ?PaymentGateway
    {
        /** @var array<string, class-string<PaymentGateway>> $drivers */
        $drivers = config('payment.drivers', []);

        if (! isset($drivers[$name])) {
            return null;
        }

        return app($drivers[$name]);
    }
}
