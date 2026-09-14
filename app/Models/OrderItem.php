<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One line of an order (docs/03 § 3.2).
 *
 * `name` and `unit_price` are snapshots taken at purchase time. Nothing here
 * reads back through `itemable` for a price — that relation exists to say what
 * was bought, not what it costs today.
 *
 * @property int $order_id
 * @property string $itemable_type
 * @property int $itemable_id
 * @property string $name
 * @property string $unit_price
 * @property int $quantity
 * @property string $total
 * @property array<string, mixed>|null $meta
 */
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'itemable_type',
        'itemable_id',
        'name',
        'unit_price',
        'quantity',
        'total',
        'meta',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
            'quantity' => 'integer',
            'meta' => 'array',
        ];
    }
}
