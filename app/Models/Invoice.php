<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The document for a paid order (docs/03 § 3.2).
 *
 * Nothing writes rows yet: an invoice belongs to an order that was actually
 * paid, and the PDF is M2.8 in a later session.
 *
 * @property int $order_id
 * @property string $invoice_number
 * @property string|null $pdf_path
 * @property Carbon|null $issued_at
 * @property Carbon|null $due_at
 */
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'invoice_number',
        'pdf_path',
        'issued_at',
        'due_at',
    ];

    /**
     * Bound by id, not by `invoice_number`: the number is `INV/2026/000001`,
     * and those slashes cannot live in a single URL path segment. The number
     * is for humans and accountants; the id is for routing.
     */

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'due_at' => 'datetime',
        ];
    }
}
