<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "Your invitation expires in N days" (23.6, M8.6).
 *
 * Queued a week and a day ahead by ScheduleExpiryWarningsJob, so by the time
 * it runs the invitation may have been unpublished, renewed or taken down.
 * `shouldSend` re-reads it rather than trusting the world it was queued in.
 */
class InvitationExpiringSoon extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Invitation $invitation,
        public readonly int $days,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Still published, and still expiring when we thought it would.
     */
    public function shouldSend(object $notifiable, string $channel): bool
    {
        $current = Invitation::acrossAllUsers()->find($this->invitation->getKey());

        if ($current === null || $current->status !== InvitationStatus::Published) {
            return false;
        }

        return $current->expires_at !== null
            && $current->expires_at->isSameDay($this->invitation->expires_at);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Undangan Anda berakhir dalam :days hari', ['days' => $this->days]))
            ->greeting(__('Halo, :name!', ['name' => $notifiable->name]))
            ->line(__('Masa aktif :title berakhir :date.', [
                'title' => $this->invitation->title,
                'date' => $this->invitation->expires_at?->timezone($this->invitation->timezone)->translatedFormat('d F Y') ?? '-',
            ]))
            ->line(__('Setelah itu tautannya menampilkan halaman "undangan telah berakhir", bukan halaman kosong.'))
            ->action(__('Perpanjang undangan'), route('pricing.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->getKey(),
            'invitation_slug' => $this->invitation->slug,
            'title' => $this->invitation->title,
            'days' => $this->days,
            'expires_at' => $this->invitation->expires_at?->toIso8601String(),
            'message' => __('Masa aktif :title berakhir dalam :days hari.', [
                'title' => $this->invitation->title,
                'days' => $this->days,
            ]),
        ];
    }
}
