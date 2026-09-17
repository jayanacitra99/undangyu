<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Support\Money;
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
 * @property string|null $verification_note
 * @property Carbon|null $verified_at
 * @property Carbon|null $paid_at
 * @property array<string, mixed>|null $raw_payload
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * The gateway name a bank transfer carries. Not a driver — nothing calls
     * an API for it — but it keeps one column answering "how was this paid".
     */
    public const GATEWAY_MANUAL = 'manual';

    /**
     * Where a client's transfer proof is stored. The `local` disk is private
     * (storage/app/private), so these are never web-reachable; the admin
     * screen streams them through an authorized route.
     */
    public const PROOF_DISK = 'local';

    public const PROOF_DIRECTORY = 'payments/proofs';

    protected $fillable = [
        'order_id',
        'gateway',
        'gateway_ref',
        'method',
        'amount',
        'status',
        'proof_path',
        'verified_by',
        'verification_note',
        'verified_at',
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
     * Bank transfers a human has to look at (M2.7).
     *
     * @param  Builder<Payment>  $query
     */
    public function scopeManual(Builder $query): void
    {
        $query->where('gateway', self::GATEWAY_MANUAL);
    }

    /**
     * Manual transfers with proof uploaded and no decision yet — the admin
     * queue, exactly.
     *
     * @param  Builder<Payment>  $query
     */
    public function scopeAwaitingVerification(Builder $query): void
    {
        $query->manual()
            ->pending()
            ->whereNotNull('proof_path');
    }

    /**
     * Does the proof cover what the order actually costs? The admin screen
     * shows this rather than deciding on it — a client who transferred an
     * extra thousand rupiah should not be rejected automatically.
     */
    public function matchesOrderTotal(): bool
    {
        // Decimal comparison, not float equality: two amounts that are exactly
        // equal on paper can differ once they have been through a float.
        return Money::equals($this->amount, $this->order->total);
    }

    public function isManual(): bool
    {
        return $this->gateway === self::GATEWAY_MANUAL;
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
            'verified_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }
}
