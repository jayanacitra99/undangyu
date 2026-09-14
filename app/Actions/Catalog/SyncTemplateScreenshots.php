<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Models\Template;
use App\Models\TemplateScreenshot;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Applies one edit of a template's screenshot strip: remove, reorder, append.
 *
 * Removals are resolved against the template's own rows, so an id belonging to
 * another template deletes nothing.
 */
final class SyncTemplateScreenshots
{
    public const DIRECTORY = 'templates/screenshots';

    /**
     * @param  list<UploadedFile>  $uploads
     * @param  list<string|null>  $captions
     * @param  list<int>  $removeIds
     * @param  list<int>  $orderedIds
     */
    public function __invoke(
        Template $template,
        array $uploads = [],
        array $captions = [],
        array $removeIds = [],
        array $orderedIds = [],
    ): void {
        $removedPaths = [];

        DB::transaction(function () use ($template, $uploads, $captions, $removeIds, $orderedIds, &$removedPaths): void {
            if ($removeIds !== []) {
                $doomed = $template->screenshots()->whereIn('id', $removeIds)->get();
                $removedPaths = $doomed->pluck('path')->all();

                TemplateScreenshot::query()->whereIn('id', $doomed->modelKeys())->delete();
            }

            foreach ($orderedIds as $position => $id) {
                $template->screenshots()->whereKey($id)->update(['sort_order' => $position]);
            }

            $next = (int) $template->screenshots()->max('sort_order') + 1;

            foreach ($uploads as $index => $upload) {
                $template->screenshots()->create([
                    'path' => $upload->store(self::DIRECTORY, 'public'),
                    'caption' => $captions[$index] ?? null,
                    'sort_order' => $next++,
                ]);
            }
        });

        foreach ($removedPaths as $path) {
            Storage::disk('public')->delete($path);
        }
    }
}
