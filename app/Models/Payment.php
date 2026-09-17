<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One payment attempt against an order (docs/03 § 3.2).
 *
 * `gateway_ref` is what a webhook looks the row up by, so it is unique and
 * never reused. `raw_payload` keeps the gateway's own words for disputes.
 *
 * @property int $order_id
 * @property string $gateway
 * @property string|null $gateway_ref
 * @property string|null $method
 * @property string $amount
 * @property PaymentStatus $status
 * @property string|null $proof_path
 * @property int|null $verified_by
 * @property Carbon|null $paid_at
 * @property array<string, mixed>|null $raw_payload
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'gateway',
        'gateway_ref',
        'method',
        'amount',
        'status',
        'proof_path',
        'verified_by',
        'paid_at',
        'raw_payload',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Who approved a manual transfer. Null for everything the gateway settled.
     *
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * @param  Builder<Payment>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', PaymentStatus::Pending);
    }

    /**
     * @param  Builder<Payment>  $query
     */
    public function scopeSettled(Builder $query): void
    {
        $query->where('status', PaymentStatus::Settled);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }
}
