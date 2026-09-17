<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * One purchase (docs/03 § 3.2).
 *
 * Totals are written once by CreateOrder from the line-item snapshots and are
 * never recomputed from the live package — see the warning in § 3.2.
 *
 * @property string $order_number
 * @property int $user_id
 * @property int $package_id
 * @property int|null $template_id
 * @property int|null $coupon_id
 * @property string $subtotal
 * @property string $discount_amount
 * @property string $tax_amount
 * @property string $total
 * @property OrderStatus $status
 * @property Carbon|null $payment_deadline
 * @property Carbon|null $paid_at
 * @property string|null $notes
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'package_id',
        'template_id',
        'coupon_id',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'status',
        'payment_deadline',
        'paid_at',
        'notes',
    ];

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * @return BelongsTo<Template, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Every attempt to pay this order — a failed card and the VA that worked
     * are both rows here.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * What this order bought, once provisioning has run.
     *
     * @return HasOne<Invitation, $this>
     */
    public function invitation(): HasOne
    {
        return $this->hasOne(Invitation::class);
    }

    /**
     * @return HasOne<Invoice, $this>
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * A pending order whose deadline has passed. The hourly sweep and the
     * client's own order list both need exactly this definition.
     *
     * @param  Builder<Order>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->where('status', OrderStatus::Pending)
            ->whereNotNull('payment_deadline')
            ->where('payment_deadline', '<', now());
    }

    /**
     * @param  Builder<Order>  $query
     */
    public function scopeLatestFirst(Builder $query): void
    {
        $query->orderByDesc('created_at')->orderByDesc('id');
    }

    public function isPayable(): bool
    {
        return $this->status->isOpen()
            && ($this->payment_deadline === null || $this->payment_deadline->isFuture());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'status' => OrderStatus::class,
            'payment_deadline' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }
}
