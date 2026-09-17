<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Payments\VerifyManualPayment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifyPaymentRequest;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The manual transfer queue (M2.7).
 *
 * Approving here dispatches provisioning through exactly the same action a
 * settled webhook uses — there is no second copy of that logic to drift.
 */
final class PaymentVerificationController extends Controller
{
    public const PER_PAGE = 20;

    public function index(): View
    {
        Gate::authorize('viewAny', Payment::class);

        return view('admin.payments.pending', [
            'payments' => Payment::query()
                ->with(['order.user:id,name,email', 'order.package:id,name'])
                ->awaitingVerification()
                ->orderBy('created_at')
                ->paginate(self::PER_PAGE),
        ]);
    }

    /**
     * The proof itself. Private disk, so it is streamed behind the policy
     * rather than linked.
     */
    public function proof(Payment $payment): StreamedResponse
    {
        Gate::authorize('viewProof', $payment);

        $disk = Storage::disk(Payment::PROOF_DISK);

        abort_unless($disk->exists((string) $payment->proof_path), 404);

        return $disk->response((string) $payment->proof_path);
    }

    public function approve(VerifyPaymentRequest $request, Payment $payment, VerifyManualPayment $verify): RedirectResponse
    {
        $applied = $verify->approve($payment, $request->user(), $request->note());

        return to_route('admin.payments.pending')->with(
            $applied ? 'status' : 'error',
            $applied
                ? __('Pembayaran disetujui. Undangan sedang disiapkan.')
                : __('Pembayaran tidak dapat disetujui.'),
        );
    }

    public function reject(VerifyPaymentRequest $request, Payment $payment, VerifyManualPayment $verify): RedirectResponse
    {
        $verify->reject($payment, $request->user(), (string) $request->note());

        return to_route('admin.payments.pending')
            ->with('status', __('Pembayaran ditolak. Klien dapat mengunggah bukti baru.'));
    }
}
