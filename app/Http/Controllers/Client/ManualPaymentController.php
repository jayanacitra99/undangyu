<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Actions\Payments\SubmitManualPayment;
use App\Facades\Setting;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreManualPaymentRequest;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

/**
 * The bank-transfer path (M2.7).
 *
 * Still ~30% of Indonesian transactions, so it is a first-class route rather
 * than a fallback: destination account, then proof upload, then a wait.
 */
final class ManualPaymentController extends Controller
{
    public function show(Order $order): View|RedirectResponse
    {
        Gate::authorize('pay', $order);

        if (! Setting::get('payment.manual_transfer_enabled', false)) {
            return to_route('client.orders.show', $order)
                ->with('error', __('Transfer manual sedang tidak tersedia.'));
        }

        return view('client.payments.manual', [
            'order' => $order,
            'payment' => $order->payments()->manual()->latest('id')->first(),
            'bank' => [
                'name' => Setting::get('payment.manual_bank_name'),
                'account_number' => Setting::get('payment.manual_account_number'),
                'account_name' => Setting::get('payment.manual_account_name'),
                'instructions' => Setting::get('payment.manual_instructions'),
            ],
        ]);
    }

    public function store(
        StoreManualPaymentRequest $request,
        Order $order,
        SubmitManualPayment $submit,
    ): RedirectResponse {
        if (! Setting::get('payment.manual_transfer_enabled', false)) {
            return to_route('client.orders.show', $order)
                ->with('error', __('Transfer manual sedang tidak tersedia.'));
        }

        /** @var UploadedFile $proof */
        $proof = $request->file('proof');

        $submit($order, $proof);

        return to_route('client.orders.show', $order)
            ->with('status', __('Bukti transfer terkirim. Kami verifikasi maksimal 1x24 jam pada hari kerja.'));
    }
}
