<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\MediaType;
use App\Models\Invitation;
use App\Models\InvitationEvent;
use App\Models\InvitationGift;
use App\Models\InvitationMedia;
use App\Models\InvitationPerson;
use App\Models\InvitationSection;
use App\Models\InvitationStory;
use App\Support\InvitationSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Everything the public renderer needs about one invitation, and nothing else
 * (20.1).
 *
 * This is the single description of what a guest may see. Two rules govern it:
 *
 *   1. **Nothing private leaves.** No entitlements, no order, no owner, no
 *      password hash, no view count. A guest looking at a wedding page must not
 *      be able to read what the couple paid or which package they are on.
 *   2. **Everything is resolved.** The theme is merged, media URLs are built,
 *      times are rendered in the invitation's timezone, sections are filtered
 *      and ordered. The renderer draws; it does not decide.
 *
 * The output is a plain array by design — it goes into a cache, and cached
 * objects come back as `__PHP_Incomplete_Class`.
 *
 * @mixin Invitation
 */
class InvitationPayload extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'invitation' => [
                'uuid' => $this->uuid,
                'slug' => $this->slug,
                'title' => $this->title,
                'language' => $this->language,
                'timezone' => $this->timezone,
                'status' => $this->status->value,
                'is_live' => $this->isLive(),
                // The gate mode, not the password: which door a guest meets.
                'visibility' => $this->visibility->value,
                'meta' => [
                    'title' => $this->meta_title ?? $this->title,
                    'description' => $this->meta_description,
                    // Absolute, because WhatsApp and Facebook fetch this URL
                    // from their own servers — a relative path is a broken
                    // preview card, which is the one thing this product cannot
                    // ship (M4.16).
                    'og_image' => $this->ogImage(),
                ],
                'published_at' => $this->published_at?->toIso8601String(),
                'expires_at' => $this->expires_at?->toIso8601String(),
            ],

            'event_type' => [
                'slug' => $this->eventType->slug,
                'name' => $this->eventType->name,
            ],

            // What the renderer dispatches on: which component folder, and
            // which version of it this invitation was sold.
            'template' => [
                'slug' => $this->template->slug,
                'view_key' => $this->template->view_key,
                'version' => $this->template_version,
            ],

            'theme' => $this->resolvedTheme(),

            // The toggles a guest's experience depends on. Not the package
            // that granted them.
            'settings' => InvitationSettings::for($this->resource),

            'sections' => $this->sections
                ->filter(fn (InvitationSection $section): bool => $section->is_visible)
                ->values()
                ->map(fn (InvitationSection $section): array => [
                    'key' => $section->section_key->value,
                    'heading' => $section->heading(),
                    'body' => $section->content['body'] ?? null,
                ])
                ->all(),

            'persons' => $this->persons->map(fn (InvitationPerson $person): array => [
                'role' => $person->role->value,
                'full_name' => $person->full_name,
                'nickname' => $person->nickname,
                'display_name' => $person->displayName(),
                // Resolved, like everything else here: the renderer must not
                // have to know which disk a person's photo sits on.
                'photo_url' => $person->photo === null
                    ? null
                    : Storage::disk(InvitationPerson::PHOTO_DISK)->url($person->photo),
                'bio' => $person->bio,
                'parents' => [
                    'father' => $person->parent_father,
                    'mother' => $person->parent_mother,
                ],
                'child_order' => $person->child_order,
                'instagram' => $person->instagram,
            ])->all(),

            'events' => $this->events->map(fn (InvitationEvent $event): array => [
                // The id is here for the RSVP form (28.2): a guest answering
                // "coming to the resepsi" has to name which one, and the only
                // handle the renderer has is what this payload gave it.
                'id' => $event->id,
                'name' => $event->name,
                'description' => $event->description,
                // Wall clock in the invitation's own timezone: a Jakarta
                // wedding reads 19:00 whether the guest is in Bali or Berlin.
                'start_at' => $event->local_start_at->toIso8601String(),
                'end_at' => $event->local_end_at?->toIso8601String(),
                'is_all_day' => $event->is_all_day,
                'venue_name' => $event->venue_name,
                'address' => $event->address,
                'maps_url' => $event->maps_url,
                'latitude' => $event->latitude,
                'longitude' => $event->longitude,
                'dress_code' => $event->dress_code,
                'live_stream_url' => $event->live_stream_url,
                'notes' => $event->notes,
            ])->all(),

            'story' => $this->stories->map(fn (InvitationStory $story): array => [
                'date' => $story->date?->format('Y-m-d'),
                'title' => $story->title,
                'description' => $story->description,
                'image_url' => $story->image === null
                    ? null
                    : Storage::disk(InvitationStory::IMAGE_DISK)->url($story->image),
            ])->all(),

            'media' => $this->media->map(fn (InvitationMedia $media): array => [
                'type' => $media->type->value,
                'source' => $media->source->value,
                'caption' => $media->caption,
                'is_cover' => $media->is_cover,
                'url' => $media->url(),
                'thumb' => $media->conversion('thumb'),
                'medium' => $media->conversion('medium'),
                'full' => $media->conversion('full'),
                'embed_url' => $media->embed_url,
            ])->all(),

            // The account number is here on purpose: it is printed on the
            // invitation for guests to copy. The `encrypted` cast protects the
            // database, not this payload.
            'gifts' => $this->gifts->map(fn (InvitationGift $gift): array => [
                'type' => $gift->type->value,
                'label' => $gift->type->label(),
                'provider_name' => $gift->provider_name,
                'account_name' => $gift->account_name,
                'account_number' => $gift->account_number,
                'qris_url' => $gift->qris_image === null
                    ? null
                    : Storage::disk(InvitationGift::IMAGE_DISK)->url($gift->qris_image),
                'recipient_name' => $gift->recipient_name,
                'address' => $gift->address,
                'notes' => $gift->notes,
            ])->all(),
        ];
    }

    /**
     * The link preview image, as an absolute URL.
     *
     * The generated 1200×630 composite (M12.7) when there is one; otherwise
     * the cover photo, which is better than nothing and is what the client
     * chose to lead with. A cover on the private disk resolves to its
     * streaming route, which serves published invitations to anyone — exactly
     * what a scraper is.
     */
    private function ogImage(): ?string
    {
        if ($this->og_image_path !== null) {
            return Storage::disk('public')->url($this->og_image_path);
        }

        $cover = $this->media->first(
            fn (InvitationMedia $media): bool => $media->is_cover && $media->type === MediaType::Image,
        );

        return $cover?->conversion('full');
    }

    /**
     * The template's defaults with the client's choices over the top, so the
     * renderer always has every key the schema declares — even one the client
     * has never touched, and even after an admin adds a new one.
     *
     * @return array<string, array<string, mixed>>
     */
    private function resolvedTheme(): array
    {
        /** @var array<string, array<string, mixed>> $defaults */
        $defaults = $this->template->default_config ?? [];

        /** @var array<string, array<string, mixed>> $chosen */
        $chosen = $this->theme_config ?? [];

        $resolved = $defaults;

        foreach ($chosen as $group => $fields) {
            $resolved[$group] = [...($resolved[$group] ?? []), ...$fields];
        }

        return $resolved;
    }
}
