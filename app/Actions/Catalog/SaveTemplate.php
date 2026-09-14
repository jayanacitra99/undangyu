<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Models\Template;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Creates or updates one template together with its thumbnail and the event
 * types it supports (M3.3).
 *
 * The old thumbnail is deleted only after the row has been written, so a failed
 * save never leaves the template pointing at a file that is gone.
 */
final class SaveTemplate
{
    public const THUMBNAIL_DIRECTORY = 'templates/thumbnails';

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $eventTypeIds
     */
    public function __invoke(
        ?Template $template,
        array $attributes,
        array $eventTypeIds,
        ?UploadedFile $thumbnail = null,
    ): Template {
        $previousThumbnail = $template?->thumbnail;

        if ($thumbnail instanceof UploadedFile) {
            $attributes['thumbnail'] = $thumbnail->store(self::THUMBNAIL_DIRECTORY, 'public');
        }

        $saved = DB::transaction(function () use ($template, $attributes, $eventTypeIds): Template {
            if ($template === null) {
                $template = Template::query()->create($attributes);
            } else {
                $template->update($attributes);
            }

            $template->eventTypes()->sync($eventTypeIds);

            return $template;
        });

        if ($thumbnail instanceof UploadedFile && $previousThumbnail !== null && $previousThumbnail !== $saved->thumbnail) {
            Storage::disk('public')->delete($previousThumbnail);
        }

        return $saved->refresh();
    }
}
