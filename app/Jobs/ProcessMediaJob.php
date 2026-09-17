<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\MediaSource;
use App\Enums\MediaType;
use App\Models\InvitationMedia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;
use Throwable;

/**
 * Makes the renditions a phone photo should have been (18.3).
 *
 * An 8MB 4000px original is not what a guest on mobile data should download to
 * see a thumbnail, so each upload gets thumb/medium/full in WebP. WebP only:
 * every browser this product targets has supported it for years, it is roughly
 * a third of the JPEG bytes, and keeping one format per size halves the files
 * we store and the paths we track.
 *
 * The original is kept. It is what a re-run of this job reads, and the client
 * uploaded it.
 */
class ProcessMediaJob implements ShouldQueue
{
    use Queueable;

    /**
     * Image work is CPU-bound and can stall on a huge file; the retries are
     * for a transient disk error, not for an image GD cannot decode.
     *
     * @var list<int>
     */
    public array $backoff = [10, 60];

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * WebP at 85 is visually indistinguishable from the original at a fraction
     * of the bytes — the same quality the browser compresses to before upload.
     */
    public const QUALITY = 85;

    public function __construct(public readonly int $mediaId) {}

    public function handle(): void
    {
        $media = InvitationMedia::query()->find($this->mediaId);

        if ($media === null) {
            // Deleted between upload and processing. Nothing to do, and not an
            // error worth retrying.
            return;
        }

        if ($media->type !== MediaType::Image || $media->source !== MediaSource::Upload) {
            return;
        }

        $disk = Storage::disk($media->disk ?? InvitationMedia::UPLOAD_DISK);

        if ($media->path === null || ! $disk->exists($media->path)) {
            Log::warning('Konversi media dilewati: berkas tidak ditemukan.', [
                'media_id' => $media->getKey(),
                'path' => $media->path,
            ]);

            return;
        }

        $source = $disk->get($media->path);

        if ($source === null) {
            return;
        }

        $conversions = [];

        foreach (InvitationMedia::CONVERSIONS as $name => $edge) {
            // decodeBinary, because the file is read off a disk that may be S3
            // in production — there is no local path to hand a decoder.
            $image = Image::decodeBinary($source);

            // scaleDown, never scale: a 300px photo must not be blown up to
            // 2000px and stored three times at the same quality.
            $image->scaleDown(width: $edge, height: $edge);

            $path = $this->conversionPath($media, $name);

            $disk->put($path, (string) $image->encode(new WebpEncoder(quality: self::QUALITY)));

            $conversions[$name] = $path;
        }

        $media->update([
            'conversions' => $conversions,
            'thumbnail' => $conversions['thumb'],
            // The row was written with the upload's own size; re-read it so a
            // re-run after a replaced file still reconciles against the quota.
            'file_size' => $disk->size($media->path),
        ]);
    }

    /**
     * Renditions sit beside the original, suffixed by name — one folder per
     * invitation, and deleting the row deletes them together.
     */
    private function conversionPath(InvitationMedia $media, string $name): string
    {
        $directory = trim((string) pathinfo((string) $media->path, PATHINFO_DIRNAME), '.');
        $base = pathinfo((string) $media->path, PATHINFO_FILENAME);

        return ($directory === '' ? '' : $directory.'/')."{$base}-{$name}.webp";
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Konversi media gagal.', [
            'media_id' => $this->mediaId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
