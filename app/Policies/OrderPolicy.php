<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * Orders are the first model a client owns, so this is the first policy where
 * ownership matters rather than a permission alone (docs/04 § 11).
 *
 * `orders.viewAny` is the admin/support ability — "manage all orders". A client
 * holds no orders permission at all and still reaches their own rows.
 */
class OrderPolicy
{
    /**
     * Any signed-in user may open their own order list; the controller scopes
     * the query to them. Staff with `orders.viewAny` get the admin list, which
     * is a different screen in a later session.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Order $order): bool
    {
        return $this->owns($user, $order) || $user->can('orders.viewAny');
    }

    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Cancelling and paying are the client's own actions on an open order.
     * Staff cannot pay on someone's behalf, so ownership is the whole rule.
     */
    public function pay(User $user, Order $order): bool
    {
        return $this->owns($user, $order) && $order->isPayable();
    }

    public function cancel(User $user, Order $order): bool
    {
        return $this->owns($user, $order) && $order->status->isOpen();
    }

    public function update(User $user, Order $order): bool
    {
        return $user->can('orders.manage');
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->can('orders.manage');
    }

    private function owns(User $user, Order $order): bool
    {
        return $order->user_id === $user->getKey();
    }
}
