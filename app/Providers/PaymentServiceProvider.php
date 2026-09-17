<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Payment\Contracts\PaymentGateway;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

/**
 * Binds the one gateway the application talks to (docs/05 § 6).
 *
 * Nothing outside app/Services/Payment names a driver: everything type-hints
 * the contract and gets whatever `config('payment.driver')` says.
 */
class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, function (): PaymentGateway {
            $driver = (string) config('payment.driver');

            /** @var array<string, class-string<PaymentGateway>> $drivers */
            $drivers = config('payment.drivers', []);

            if (! isset($drivers[$driver])) {
                // A typo here would otherwise surface as a container error at
                // checkout, which is the worst moment to learn about it.
                throw new InvalidArgumentException(
                    "Payment driver [{$driver}] is not registered in config/payment.php.",
                );
            }

            return $this->app->make($drivers[$driver]);
        });
    }
}
