<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Writes a new `sort_order` for one drag-and-drop reorder.
 *
 * Ids that don't belong to the table simply match nothing, so a tampered payload
 * reorders nothing rather than erroring.
 */
final class ReorderCatalog
{
    /**
     * @param  class-string<Model>  $model
     * @param  list<int>  $orderedIds
     */
    public function __invoke(string $model, array $orderedIds): void
    {
        DB::transaction(function () use ($model, $orderedIds): void {
            foreach ($orderedIds as $position => $id) {
                $model::query()->whereKey($id)->update(['sort_order' => $position]);
            }
        });
    }
}
