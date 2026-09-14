<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The payload the drag-to-sort island posts: row ids in their new order.
 *
 * Both catalog controllers share it; each authorizes the `reorder` ability on
 * its own model before calling the action.
 */
class ReorderCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        // A request class shared by four resources cannot know which policy
        // applies, so each reorder route carries `can:reorder,<Model>` in
        // routes/admin.php — that middleware runs before this class does.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // ReorderCatalog writes one UPDATE per id inside a transaction, so
            // an unbounded array is an unbounded transaction.
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return list<int>
     */
    public function orderedIds(): array
    {
        /** @var list<int> $ids */
        $ids = $this->validated('ids');

        return $ids;
    }
}
