<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Guests\MarkGuestsSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\MarkGuestsSentRequest;
use App\Http\Requests\Client\ResolveGuestMessagesRequest;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\MessageTemplate;
use App\Services\Messaging\MessageVariables;
use Illuminate\Http\JsonResponse;

/**
 * Resolved messages and the send-tracking behind them (26.3-26.5).
 *
 * The substitution happens here rather than in the island, so the preview, the
 * per-guest link and the 400-guest run all read one implementation of what
 * `{event_date}` means.
 */
final class GuestMessageController extends Controller
{
    public function resolve(
        ResolveGuestMessagesRequest $request,
        Invitation $invitation,
        MessageVariables $variables,
    ): JsonResponse {
        $body = $this->body($request, $invitation);
        $ids = $request->ids();

        // No ids is the editor's live preview: the first guest stands in, and
        // an invitation with no guests yet still previews against the
        // placeholder values.
        $guests = $ids === []
            ? $invitation->guests()->orderBy('name')->limit(1)->get()
            : $invitation->guests()->whereKey($ids)->orderBy('name')->get();

        if ($guests->isEmpty()) {
            $message = $variables->resolve($body, $invitation);

            return response()->json(['data' => [[
                'guest_id' => null,
                'name' => __('Contoh tamu'),
                'phone' => null,
                'message' => $message,
                'invitation_url' => $variables->url($invitation),
                'whatsapp_url' => null,
                'sent_at' => null,
            ]]]);
        }

        $messages = $guests->map(function (Guest $guest) use ($body, $invitation, $variables): array {
            $message = $variables->resolve($body, $invitation, $guest);

            return [
                'guest_id' => $guest->getKey(),
                'name' => $guest->displayName(),
                'phone' => $guest->phone,
                'message' => $message,
                'invitation_url' => $variables->url($invitation, $guest),
                'whatsapp_url' => $variables->whatsappLink($message, $guest),
                'sent_at' => $guest->sent_at?->toIso8601String(),
            ];
        });

        return response()->json(['data' => $messages->values()]);
    }

    /**
     * Marks a selection as sent (26.5). The client is working through the list
     * in their own WhatsApp, so this is their word for it, recorded as they go.
     */
    public function markSent(
        MarkGuestsSentRequest $request,
        Invitation $invitation,
        MarkGuestsSent $markSent,
    ): JsonResponse {
        return response()->json(['marked' => $markSent($invitation, $request->ids())]);
    }

    /**
     * The text to resolve: an unsaved body if the editor sent one, otherwise
     * the named template — which must be one this invitation may use, or the
     * lookup finds nothing and the default applies.
     */
    private function body(ResolveGuestMessagesRequest $request, Invitation $invitation): string
    {
        $validated = $request->validated();
        $body = trim((string) ($validated['body'] ?? ''));

        if ($body !== '') {
            return $body;
        }

        $template = MessageTemplate::query()
            ->availableTo($invitation)
            ->when(
                isset($validated['template_id']),
                fn ($query) => $query->whereKey($validated['template_id']),
            )
            ->first();

        return $template === null ? '' : $template->body;
    }
}
