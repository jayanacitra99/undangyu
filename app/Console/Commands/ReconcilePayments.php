<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Payments\ApplyPaymentUpdate;
use App\Models\Payment;
use App\Services\Payment\GatewayRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Asks each gateway what it thinks happened, and reports where we disagree
 * (M2.6, docs/04 § 5 — "webhooks do get lost").
 *
 * Read-only by default. `--apply` settles the payments the gateway says are
 * settled, which is the same path a webhook takes, so it is idempotent for the
 * same reason.
 */
class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile
        {--days=3 : How far back to look}
        {--apply : Apply the gateway\'s answer instead of only reporting it}';

    protected $description = 'Compare local payments against what the gateway reports';

    public function handle(GatewayRegistry $registry, ApplyPaymentUpdate $applyUpdate): int
    {
        $since = now()->subDays((int) $this->option('days'));
        $apply = (bool) $this->option('apply');
        $discrepancies = 0;

        // Manual transfers have no gateway to ask, and a payment with no
        // reference never reached one.
        $payments = Payment::query()
            ->whereNotNull('gateway_ref')
            ->where('gateway', '!=', 'manual')
            ->where('created_at', '>=', $since)
            ->with('order')
            ->cursor();

        foreach ($payments as $payment) {
            $driver = $registry->driver($payment->gateway);

            if ($driver === null) {
                continue;
            }

            $remote = $driver->fetchStatus($payment);

            if ($remote === null) {
                $discrepancies++;
                $this->reportMissing($payment);

                continue;
            }

            if ($remote->status === $payment->status) {
                continue;
            }

            $discrepancies++;

            $this->warn(sprintf(
                '%s: lokal %s, gateway %s%s',
                $payment->gateway_ref,
                $payment->status->value,
                $remote->status->value,
                $apply ? ' — diterapkan' : '',
            ));

            Log::warning('Selisih rekonsiliasi pembayaran.', [
                'reference' => $payment->gateway_ref,
                'local' => $payment->status->value,
                'remote' => $remote->status->value,
            ]);

            if ($apply) {
                $applyUpdate($remote, $payment->gateway);
            }
        }

        $this->info($discrepancies === 0
            ? 'Semua pembayaran cocok dengan gateway.'
            : "Ditemukan {$discrepancies} selisih.");

        return self::SUCCESS;
    }

    private function reportMissing(Payment $payment): void
    {
        $this->warn("{$payment->gateway_ref}: gateway tidak mengenali referensi ini.");

        Log::warning('Gateway tidak mengenali referensi pembayaran.', [
            'reference' => $payment->gateway_ref,
            'gateway' => $payment->gateway,
        ]);
    }
}
