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
        // The controller authorizes against its own model — a request class
        // shared by two resources can't know which policy applies.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer'],
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
