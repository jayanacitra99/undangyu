<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\MediaType;
use App\Models\Invitation;
use App\Models\InvitationMedia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Laravel\Facades\Image;
use Throwable;

/**
 * The link preview image (M12.7, 23.4).
 *
 * A composite of the cover photo and the couple's names at 1200×630 — the size
 * WhatsApp, Facebook and Twitter all lay their cards out at. Without it the
 * card falls back to the cover photo, which is a 3:4 portrait and gets cropped
 * to something arbitrary.
 *
 * JPEG, not WebP: this file is fetched by scrapers rather than browsers, and
 * several of them still do not decode WebP.
 *
 * The result is public by definition — its whole purpose is to be fetched by
 * a third party from a link someone shared.
 */
class GenerateOgImageJob implements ShouldQueue
{
    use Queueable;

    public const WIDTH = 1200;

    public const HEIGHT = 630;

    public const DIRECTORY = 'invitations/og';

    public const DISK = 'public';

    /** @var list<int> */
    public array $backoff = [10, 60];

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly int $invitationId) {}

    public function handle(): void
    {
        $invitation = Invitation::acrossAllUsers()
            ->with(['media', 'persons'])
            ->find($this->invitationId);

        if ($invitation === null) {
            return;
        }

        $canvas = Image::createImage(self::WIDTH, self::HEIGHT)->fill('#F5F0E8');

        $cover = $invitation->media->first(
            fn (InvitationMedia $media): bool => $media->is_cover && $media->type === MediaType::Image,
        );

        if ($cover !== null) {
            $this->drawCover($canvas, $cover);
        }

        // A scrim, so the names stay legible over whatever the photo is doing.
        $canvas->drawRectangle(function ($rectangle): void {
            $rectangle->at(0, 0);
            $rectangle->size(self::WIDTH, self::HEIGHT);
            $rectangle->background('#00000059');
        });

        $names = $invitation->persons->isNotEmpty()
            ? $invitation->persons->map->displayName()->join(' & ')
            : $invitation->title;

        $fontFile = (string) config('undangyu.og_image.font');

        $canvas->text($names, (int) (self::WIDTH / 2), (int) (self::HEIGHT / 2), function ($font) use ($fontFile): void {
            // Without a TTF, GD falls back to a bitmap font that ignores
            // size() — the names come out at about 10px on a card that is
            // mostly seen as a thumbnail. The file is configurable; see
            // resources/fonts/README.md.
            if ($fontFile !== '' && is_file($fontFile)) {
                $font->file($fontFile);
                $font->size(72);
                $font->wrap(self::WIDTH - 160);
            }

            $font->color('#FFFFFF');
            // v4 takes both axes in one call, and its vertical alignment word
            // is "center" — there is no valign() and no "middle".
            $font->align('center', 'center');
        });

        $path = self::DIRECTORY.'/'.$invitation->uuid.'.jpg';

        Storage::disk(self::DISK)->put($path, (string) $canvas->encode(new JpegEncoder(quality: 88)));

        // A query builder update, not a model save: `og_image_path` is part of
        // the payload, so the observer *should* flush — but this job runs in a
        // chain ahead of WarmInvitationCacheJob, which rebuilds it straight
        // after. Saving through the model here would be a flush of a cache
        // that does not exist yet.
        Invitation::acrossAllUsers()
            ->whereKey($invitation->getKey())
            ->update(['og_image_path' => $path]);
    }

    /**
     * Cover the canvas with the photo: scale to fill, then crop the overflow,
     * so a portrait cover fills a landscape card without letterboxing.
     */
    private function drawCover(mixed $canvas, InvitationMedia $cover): void
    {
        $disk = Storage::disk($cover->disk ?? InvitationMedia::UPLOAD_DISK);
        $source = $cover->conversions['full'] ?? $cover->path;

        if ($source === null || ! $disk->exists($source)) {
            return;
        }

        $photo = Image::decodeBinary($disk->get($source))->cover(self::WIDTH, self::HEIGHT);

        $canvas->insert($photo);
    }

    public function failed(Throwable $exception): void
    {
        // A missing preview image is a worse share, not a broken invitation —
        // the renderer falls back to the cover photo.
        Log::warning('Gagal membuat OG image.', [
            'invitation_id' => $this->invitationId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
