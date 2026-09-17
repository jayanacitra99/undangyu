<?php

declare(strict_types=1);

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

/**
 * The client's own orders (M2.4).
 *
 * The list is scoped to the signed-in user by the query; the detail page is
 * scoped by the policy. Both, because a scoped query that someone later
 * "optimises" must still not hand over another client's order.
 */
final class OrderController extends Controller
{
    public const PER_PAGE = 15;

    public function index(): View
    {
        Gate::authorize('viewAny', Order::class);

        return view('client.orders.index', [
            'orders' => Order::query()
                ->with('package:id,name')
                ->where('user_id', auth()->id())
                ->latestFirst()
                ->paginate(self::PER_PAGE),
        ]);
    }

    public function show(Order $order): View
    {
        Gate::authorize('view', $order);

        $order->load(['items', 'package:id,name,slug', 'template:id,name,slug', 'invoice']);

        return view('client.orders.show', [
            'order' => $order,
        ]);
    }
}
