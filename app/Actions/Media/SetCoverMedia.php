<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Enums\MediaType;
use App\Models\InvitationMedia;
use Illuminate\Support\Facades\DB;

/**
 * Makes one photo the cover (18.4, M4.12).
 *
 * Exactly one per invitation: the clear-then-set runs in a transaction, so a
 * client clicking two covers quickly cannot end up with both or neither.
 */
final class SetCoverMedia
{
    public function __invoke(InvitationMedia $media): InvitationMedia
    {
        $media->loadMissing('invitation');

        return DB::transaction(function () use ($media): InvitationMedia {
            $media->invitation->media()
                ->where('type', MediaType::Image)
                ->where('is_cover', true)
                ->update(['is_cover' => false]);

            $media->update(['is_cover' => true]);

            return $media->refresh();
        });
    }
}
