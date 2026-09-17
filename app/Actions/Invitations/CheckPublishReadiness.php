<?php

declare(strict_types=1);

namespace App\Actions\Invitations;

use App\Enums\FeatureKey;
use App\Enums\MediaType;
use App\Enums\PersonRole;
use App\Enums\TemplateStatus;
use App\Models\Invitation;
use App\Models\Package;
use App\Services\Entitlements\EntitlementResolver;
use App\Support\PublishRequirement;

/**
 * The publish validation gate of docs/04 § 4, as data (16.5).
 *
 * This returns the checks rather than a yes/no, because the builder has to
 * show the client what is still missing and Session 20's publish action has to
 * refuse on the same grounds. One description of "ready", two readers.
 *
 * Quotas are read from `invitations.entitlements` — the snapshot taken at
 * provisioning — never from the live package.
 */
final readonly class CheckPublishReadiness
{
    public function __construct(private EntitlementResolver $entitlements) {}

    /**
     * @return list<PublishRequirement>
     */
    public function __invoke(Invitation $invitation): array
    {
        $invitation->loadMissing(['eventType', 'template', 'package', 'order']);

        return [
            $this->slug($invitation),
            $this->persons($invitation),
            $this->events($invitation),
            $this->cover($invitation),
            $this->template($invitation),
            $this->quotas($invitation),
            $this->order($invitation),
        ];
    }

    /**
     * @param  list<PublishRequirement>  $requirements
     */
    public static function isReady(array $requirements): bool
    {
        foreach ($requirements as $requirement) {
            if (! $requirement->met) {
                return false;
            }
        }

        return true;
    }

    private function slug(Invitation $invitation): PublishRequirement
    {
        if ($invitation->slug === '' || Invitation::slugIsBlocked($invitation->slug)) {
            return PublishRequirement::unmet(
                'slug',
                __('Alamat undangan'),
                __('Pilih alamat undangan yang belum dipakai.'),
                'dasar',
            );
        }

        return PublishRequirement::met('slug', __('Alamat undangan'), 'dasar');
    }

    /**
     * Every role the event type declares is a slot that must be filled: a
     * wedding without a groom is not a wedding invitation. `person_roles` is
     * the list of slots, so "at least one person per declared role" and "the
     * required roles are present" are the same sentence.
     */
    private function persons(Invitation $invitation): PublishRequirement
    {
        /** @var list<string> $required */
        $required = $invitation->eventType->person_roles;

        $filled = $invitation->persons()->pluck('role')
            ->map(fn (PersonRole|string $role): string => $role instanceof PersonRole ? $role->value : $role)
            ->all();

        $missing = array_values(array_diff($required, $filled));

        if ($missing === [] && $filled !== []) {
            return PublishRequirement::met('persons', __('Mempelai'), 'mempelai');
        }

        $names = array_map(
            fn (string $role): string => PersonRole::tryFrom($role)?->label() ?? $role,
            $missing !== [] ? $missing : [],
        );

        return PublishRequirement::unmet(
            'persons',
            __('Mempelai'),
            $names === []
                ? __('Tambahkan minimal satu orang.')
                : __('Lengkapi: :roles', ['roles' => implode(', ', $names)]),
            'mempelai',
        );
    }

    private function events(Invitation $invitation): PublishRequirement
    {
        $complete = $invitation->events()
            ->whereNotNull('start_at')
            ->where('venue_name', '<>', '')
            ->exists();

        return $complete
            ? PublishRequirement::met('events', __('Acara'), 'acara')
            : PublishRequirement::unmet(
                'events',
                __('Acara'),
                __('Tambahkan satu acara dengan tanggal dan nama tempat.'),
                'acara',
            );
    }

    private function cover(Invitation $invitation): PublishRequirement
    {
        $hasCover = $invitation->media()->where('is_cover', true)->exists();

        return $hasCover
            ? PublishRequirement::met('cover', __('Foto sampul'), 'galeri')
            : PublishRequirement::unmet(
                'cover',
                __('Foto sampul'),
                __('Pilih satu foto sebagai sampul undangan.'),
                'galeri',
            );
    }

    /**
     * The template has to still be published, and still be inside the tier the
     * client paid for — an admin archiving a template must not leave a client
     * able to publish against it.
     */
    private function template(Invitation $invitation): PublishRequirement
    {
        $template = $invitation->template;

        if ($template === null || $template->status !== TemplateStatus::Published) {
            return PublishRequirement::unmet(
                'template',
                __('Tema'),
                __('Tema ini tidak lagi tersedia. Pilih tema lain.'),
                'tema',
            );
        }

        if (! $this->withinTier($invitation)) {
            return PublishRequirement::unmet(
                'template',
                __('Tema'),
                __('Tema ini butuh paket yang lebih tinggi.'),
                'tema',
            );
        }

        return PublishRequirement::met('template', __('Tema'), 'tema');
    }

    /**
     * Ranked by `sort_order`, which is how the pricing table already orders
     * packages: a template whose minimum sits above the invitation's package
     * is out of reach.
     */
    private function withinTier(Invitation $invitation): bool
    {
        $minimumId = $invitation->template?->min_package_id;

        if ($minimumId === null || $minimumId === $invitation->package_id) {
            return true;
        }

        $minimum = Package::query()->find($minimumId);

        if ($minimum === null || $invitation->package === null) {
            return true;
        }

        return $invitation->package->sort_order >= $minimum->sort_order;
    }

    private function quotas(Invitation $invitation): PublishRequirement
    {
        $photos = $invitation->media()->where('type', MediaType::Image)->count();
        $videoMb = (int) ceil(
            (int) $invitation->media()->where('type', MediaType::Video)->sum('file_size') / 1_048_576
        );

        // check() answers "is there room for one more", so asking it about
        // used-minus-one answers "is what is already there within the quota".
        $overPhotos = ! $this->entitlements->check($invitation->entitlements, FeatureKey::MaxPhotos, max(0, $photos - 1));
        $overVideo = ! $this->entitlements->check($invitation->entitlements, FeatureKey::MaxVideoMb, max(0, $videoMb - 1));

        if (! $overPhotos && ! $overVideo) {
            return PublishRequirement::met('quotas', __('Kuota media'), 'galeri');
        }

        return PublishRequirement::unmet(
            'quotas',
            __('Kuota media'),
            $overPhotos
                ? __('Jumlah foto melebihi kuota paket.')
                : __('Ukuran video melebihi kuota paket.'),
            'galeri',
        );
    }

    /**
     * A reseller-built invitation with no order behind it is nobody's unpaid
     * order — the gate is about orders that exist and have not been paid.
     */
    private function order(Invitation $invitation): PublishRequirement
    {
        $order = $invitation->order;

        if ($order === null || $order->status->isSettled()) {
            return PublishRequirement::met('order', __('Pembayaran'));
        }

        return PublishRequirement::unmet(
            'order',
            __('Pembayaran'),
            __('Selesaikan pembayaran pesanan :number.', ['number' => $order->order_number]),
        );
    }
}
