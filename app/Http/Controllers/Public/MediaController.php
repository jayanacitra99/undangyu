<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\InvitationMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves a stored image (18.2).
 *
 * Uploads live on the private disk, so this is the only way one reaches a
 * browser. Two kinds of viewer arrive here:
 *
 *   - a guest looking at a published invitation, who gets the file;
 *   - the client in the builder, looking at a draft nobody else may see.
 *
 * Anything else is a 404, not a 403: whether a draft exists is itself the
 * client's business.
 *
 * Nothing is written here — hard rule 1 — and the route is rate limited like
 * every other public endpoint.
 */
final class MediaController extends Controller
{
    /**
     * A week. The bytes at a given path never change: a replaced photo is a
     * new row with a new path, so a long cache costs nothing and saves the
     * origin every scroll through a gallery.
     */
    public const CACHE_SECONDS = 604_800;

    public function __invoke(Request $request, InvitationMedia $media, ?string $variant = null): StreamedResponse
    {
        // Unscoped: the tenant scope would hide another client's invitation
        // from this lookup, and "is it published" is a question about the row,
        // not about who is asking. Who may see it is decided below.
        $invitation = Invitation::acrossAllUsers()->find($media->invitation_id);

        abort_if($invitation === null, 404);

        $media->setRelation('invitation', $invitation);

        abort_unless($this->mayView($request, $media), 404);

        $path = $variant === null
            ? $media->path
            : ($media->conversions[$variant] ?? $media->path);

        abort_if($path === null, 404);

        $disk = Storage::disk($media->disk ?? InvitationMedia::UPLOAD_DISK);

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'public, max-age='.self::CACHE_SECONDS.', immutable',
        ]);
    }

    /**
     * A live invitation's media is public by definition. A draft's is the
     * client's, and the policy already knows who that is.
     */
    private function mayView(Request $request, InvitationMedia $media): bool
    {
        if ($media->invitation->isLive()) {
            return true;
        }

        return $request->user() !== null && Gate::forUser($request->user())->allows('view', $media);
    }
}
