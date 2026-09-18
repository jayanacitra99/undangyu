<?php

declare(strict_types=1);

namespace App\Actions\Invitations;

use App\Enums\InvitationStatus;
use App\Jobs\GenerateOgImageJob;
use App\Jobs\ScheduleExpiryWarningsJob;
use App\Jobs\WarmInvitationCacheJob;
use App\Models\Invitation;
use App\Notifications\InvitationPublished;
use App\Support\PublishRequirement;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

/**
 * Draft to published (M4.14, 23.1, 23.3).
 *
 * The gate is CheckPublishReadiness — the same checks the builder's checklist
 * draws, so "the button was enabled" and "the action allowed it" can never
 * disagree. This returns the requirements rather than throwing when they fail,
 * because the caller has to say which one is missing.
 *
 * Publishing is also when the clock starts: `expires_at` is computed here from
 * the package's active days, not at provisioning. A client who buys in January
 * and publishes in June gets their full window — which is what they were sold,
 * and what InvitationProvisioned's email already promises them.
 */
final readonly class PublishInvitation
{
    public function __construct(private CheckPublishReadiness $readiness) {}

    /**
     * @return array{published: bool, requirements: list<PublishRequirement>, invitation: Invitation}
     */
    public function __invoke(Invitation $invitation): array
    {
        $requirements = ($this->readiness)($invitation);

        if (! CheckPublishReadiness::isReady($requirements)) {
            return [
                'published' => false,
                'requirements' => $requirements,
                'invitation' => $invitation,
            ];
        }

        $wasPublished = $invitation->status === InvitationStatus::Published;

        $invitation = DB::transaction(function () use ($invitation, $wasPublished): Invitation {
            $invitation->loadMissing('package');

            $invitation->update([
                'status' => InvitationStatus::Published,
                // Republishing keeps the original date: the invitation went
                // live when it went live, and an edit is not a new launch.
                'published_at' => $invitation->published_at ?? now(),
                'expires_at' => $wasPublished
                    ? $invitation->expires_at
                    : now()->addDays($invitation->package->active_days),
            ]);

            return $invitation->refresh();
        });

        activity('invitation')
            ->performedOn($invitation)
            ->causedBy(auth()->user())
            ->withProperties(['expires_at' => $invitation->expires_at?->toIso8601String()])
            ->event('published')
            ->log('Invitation published');

        if (! $wasPublished) {
            $this->afterPublish($invitation);
        }

        return [
            'published' => true,
            'requirements' => $requirements,
            'invitation' => $invitation,
        ];
    }

    /**
     * The post-publish work, in order (23.3).
     *
     * A chain rather than four independent dispatches: the cache warm should
     * run after the OG image exists, or it caches a payload whose `og_image`
     * is still null and a share in the next hour previews without a picture.
     */
    private function afterPublish(Invitation $invitation): void
    {
        Bus::chain([
            new GenerateOgImageJob($invitation->getKey()),
            new WarmInvitationCacheJob($invitation->getKey()),
            new ScheduleExpiryWarningsJob($invitation->getKey()),
        ])->dispatch();

        $invitation->loadMissing('user');
        $invitation->user->notify(new InvitationPublished($invitation));
    }
}
