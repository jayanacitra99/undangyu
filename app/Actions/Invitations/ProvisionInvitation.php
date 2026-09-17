<?php

declare(strict_types=1);

namespace App\Actions\Invitations;

use App\Enums\InvitationStatus;
use App\Enums\InvitationVisibility;
use App\Enums\OrderStatus;
use App\Models\EventType;
use App\Models\Invitation;
use App\Models\Order;
use App\Models\Template;
use App\Services\Entitlements\EntitlementResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Turns a paid order into a draft invitation (M2.9).
 *
 * Idempotent on the order: the gateway can deliver one settlement more than
 * once and a failed worker retries, so running this twice must leave exactly
 * one invitation. The check and the write share a transaction, and the order
 * row is locked for the duration.
 *
 * The entitlements written here are a snapshot. Everything downstream reads
 * quotas from the invitation, never from the live package.
 */
final class ProvisionInvitation
{
    public function __construct(private readonly EntitlementResolver $resolver) {}

    public function __invoke(Order $order): Invitation
    {
        return DB::transaction(function () use ($order): Invitation {
            $order = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // withTrashed: a soft-deleted invitation still occupies this
            // order's slot, and the unique index on `invitations.order_id`
            // counts it too. Reviving is a decision for a human, not a silent
            // second invitation.
            $existing = Invitation::withTrashed()->where('order_id', $order->getKey())->first();

            if ($existing !== null) {
                return $existing;
            }

            $template = $this->template($order);
            $eventType = $this->eventType($order, $template);
            $package = $order->package;

            $invitation = Invitation::query()->create([
                'user_id' => $order->user_id,
                'created_by' => $order->user_id,
                'order_id' => $order->getKey(),
                'event_type_id' => $eventType->getKey(),
                'template_id' => $template->getKey(),
                // Pinned: publishing template v2 must not change what an
                // invitation already renders (M3.9).
                'template_version' => $template->version,
                'package_id' => $package->getKey(),
                'slug' => $this->slug($order),
                'title' => $this->title($order),
                'status' => InvitationStatus::Draft,
                'visibility' => InvitationVisibility::Public,
                'language' => 'id',
                'timezone' => (string) config('app.display_timezone', 'Asia/Jakarta'),
                'theme_config' => $template->default_config,
                'settings' => $this->settings(),
                'entitlements' => $this->resolver->resolve($package),
                // Null until publish: the active window starts when the client
                // puts the invitation live, not when they bought it.
                'expires_at' => null,
            ]);

            $this->seedSections($invitation, $eventType);

            $order->update(['status' => OrderStatus::Provisioned]);

            return $invitation;
        });
    }

    /**
     * The design the client bought, or the first published one that serves
     * their event type when they bought a package alone.
     */
    private function template(Order $order): Template
    {
        if ($order->template_id !== null) {
            $template = Template::query()->find($order->template_id);

            if ($template !== null) {
                return $template;
            }
        }

        $template = Template::query()->published()->ordered()->first();

        if ($template === null) {
            throw new RuntimeException(
                "Tidak ada template terbit untuk pesanan {$order->order_number}.",
            );
        }

        return $template;
    }

    /**
     * Orders carry no event type, so it comes from the template: the first
     * active type that design supports. The client can change it in the
     * builder, which re-seeds the sections.
     */
    private function eventType(Order $order, Template $template): EventType
    {
        $fromTemplate = $template->eventTypes()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->first();

        if ($fromTemplate !== null) {
            return $fromTemplate;
        }

        $fallback = EventType::query()->active()->ordered()->first();

        if ($fallback === null) {
            throw new RuntimeException(
                "Tidak ada jenis acara aktif untuk pesanan {$order->order_number}.",
            );
        }

        return $fallback;
    }

    /**
     * A placeholder the client renames in the builder. Their own name is a
     * better start than "Undangan #4" when they have several.
     */
    private function title(Order $order): string
    {
        return 'Undangan '.$order->user->name;
    }

    /**
     * The slug is the public URL and is frozen once set, so it cannot collide
     * and cannot be a reserved word. A random suffix keeps two clients with
     * the same name apart without leaking a sequence.
     */
    private function slug(Order $order): string
    {
        $base = Str::slug($order->user->name);

        if ($base === '' || Invitation::slugIsBlocked($base)) {
            $base = 'undangan';
        }

        do {
            $slug = Str::limit($base, 100, '').'-'.Str::lower(Str::random(6));
        } while (Invitation::withTrashed()->where('slug', $slug)->exists());

        return $slug;
    }

    /**
     * The toggles a new invitation starts with. The client's package may not
     * grant all of them; the builder hides what the entitlements refuse.
     *
     * @return array<string, mixed>
     */
    private function settings(): array
    {
        return [
            'rsvp_enabled' => true,
            'guestbook_enabled' => true,
            'guestbook_moderation' => 'auto',
            'music_enabled' => true,
            'music_autoplay' => true,
            'countdown_enabled' => true,
            'gift_enabled' => true,
        ];
    }

    /**
     * The blocks this kind of event starts with, in the order the event type
     * declares them.
     */
    private function seedSections(Invitation $invitation, EventType $eventType): void
    {
        /** @var list<string> $keys */
        $keys = $eventType->default_sections;

        foreach ($keys as $index => $key) {
            $invitation->sections()->create([
                'section_key' => $key,
                'is_visible' => true,
                'sort_order' => $index,
            ]);
        }
    }
}
