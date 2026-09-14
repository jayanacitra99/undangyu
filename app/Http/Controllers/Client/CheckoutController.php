<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Actions\Orders\CreateOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreOrderRequest;
use App\Models\Order;
use App\Models\Package;
use App\Models\Template;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Checkout: package (+ optional template) becomes a pending order (M2.4).
 *
 * Auth is required, so a guest arriving from the pricing page is sent to login
 * and returned here by the framework's intended-url handling.
 */
final class CheckoutController extends Controller
{
    /**
     * The summary screen. Nothing is written here — the totals shown are the
     * same arithmetic CreateOrder will snapshot on POST.
     */
    public function show(Request $request): View|RedirectResponse
    {
        Gate::authorize('create', Order::class);

        $package = Package::query()
            ->active()
            ->where('slug', $request->string('package')->toString())
            ->first();

        if ($package === null) {
            return to_route('pricing.index')
                ->with('status', __('Pilih paket terlebih dahulu.'));
        }

        $template = Template::query()
            ->with('category:id,name')
            ->published()
            ->where('slug', $request->string('template')->toString())
            ->first();

        $templatePrice = $template !== null && $template->is_premium
            ? (float) $template->extra_price
            : 0.0;

        return view('client.checkout.show', [
            'package' => $package,
            'template' => $template,
            'packagePrice' => $package->effectivePrice(),
            'templatePrice' => $templatePrice,
            'total' => $package->effectivePrice() + $templatePrice,
        ]);
    }

    public function store(StoreOrderRequest $request, CreateOrder $createOrder): RedirectResponse
    {
        $order = $createOrder(
            $request->user(),
            $request->package(),
            $request->template(),
        );

        return to_route('client.orders.show', $order)
            ->with('status', __('Pesanan dibuat. Selesaikan pembayaran sebelum batas waktu.'));
    }
}
