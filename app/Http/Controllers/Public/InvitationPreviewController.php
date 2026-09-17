<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Template;
use App\Services\Invitations\InvitationPayloadService;
use Database\Seeders\DemoInvitationSeeder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\URL;

/**
 * The two ways to see an invitation that is not published (21.5).
 *
 * **Template preview** (M3.6) renders the seeded demo invitation through a
 * chosen template, so a buyer can see what they are buying with content in it
 * rather than lorem ipsum.
 *
 * **Draft preview** (M4.15) is a signed, time-limited link to the client's own
 * unpublished invitation. Clients always want to show their mother first, and
 * the alternative is publishing to do it.
 */
final class InvitationPreviewController extends Controller
{
    /**
     * A week is long enough to show the family and short enough that a link
     * forwarded onward stops working before the wedding.
     */
    public const DRAFT_LINK_DAYS = 7;

    public function __construct(private readonly InvitationPayloadService $payloads) {}

    public static function draftUrl(Invitation $invitation): string
    {
        return URL::temporarySignedRoute(
            'preview.invitation',
            now()->addDays(self::DRAFT_LINK_DAYS),
            ['uuid' => $invitation->uuid],
        );
    }

    /**
     * The demo invitation, wearing the requested template.
     */
    public function template(Template $template): View
    {
        $payload = $this->payloads->forSlug(DemoInvitationSeeder::SLUG);

        abort_if($payload === null, 404, 'Undangan demo belum di-seed.');

        // The demo's own content, this template's identity and defaults. The
        // demo is not re-saved: it belongs to whichever template seeded it.
        $payload['template'] = [
            'slug' => $template->slug,
            'view_key' => $template->view_key,
            'version' => $template->version,
        ];

        $payload['theme'] = $template->default_config ?? [];

        return view('public.invitation', [
            'payload' => $payload,
            'isPreview' => true,
            'previewNotice' => __('Pratinjau template :name dengan data contoh.', ['name' => $template->name]),
        ]);
    }

    /**
     * One client's unpublished invitation, behind a signed link. The `signed`
     * middleware has already refused a tampered or expired one.
     */
    public function draft(string $uuid): View
    {
        $invitation = Invitation::acrossAllUsers()->where('uuid', $uuid)->first();

        abort_if($invitation === null, 404);

        return view('public.invitation', [
            'payload' => $this->payloads->forInvitation($invitation),
            'isPreview' => true,
            'previewNotice' => __('Pratinjau. Undangan ini belum terbit.'),
        ]);
    }
}
