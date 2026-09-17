<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Actions\Payments\StartPayment;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends a client to the gateway for one order (M2.5).
 *
 * The webhook that settles what happens next lands in Session 10; until then a
 * paid order changes nothing locally.
 */
final class PaymentController extends Controller
{
    public function store(Order $order, StartPayment $startPayment): RedirectResponse
    {
        // `pay` is ownership plus an open deadline, so an expired order cannot
        // be pushed to the gateway by re-posting the form.
        Gate::authorize('pay', $order);

        try {
            ['session' => $session] = $startPayment($order);
        } catch (Throwable $exception) {
            Log::error('Gagal membuka transaksi pembayaran.', [
                'order' => $order->order_number,
                'exception' => $exception->getMessage(),
            ]);

            return back()->with('error', __('Gagal menghubungi penyedia pembayaran. Coba lagi sebentar lagi.'));
        }

        if ($session->redirectUrl === null) {
            return back()->with('error', __('Penyedia pembayaran tidak mengembalikan halaman pembayaran.'));
        }

        return redirect()->away($session->redirectUrl);
    }
}
