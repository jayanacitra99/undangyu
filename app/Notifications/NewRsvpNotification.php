<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Invitation;
use App\Models\Rsvp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "Someone confirmed" (28.5).
 *
 * Throttled by the action that sends it — one per invitation per window — so
 * the mail says what the totals are now rather than pretending to be the only
 * answer that arrived. A client who wants every name opens the dashboard.
 */
class NewRsvpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Invitation $invitation,
        public readonly Rsvp $rsvp,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $totals = $this->totals();

        return (new MailMessage)
            ->subject(__('Konfirmasi kehadiran baru untuk :title', ['title' => $this->invitation->title]))
            ->greeting(__('Halo, :name!', ['name' => $notifiable->name]))
            ->line(__(':guest menjawab ":answer".', [
                'guest' => $this->rsvp->name,
                'answer' => $this->rsvp->attendance->label(),
            ]))
            ->line(__('Sejauh ini: :yes hadir (:pax orang), :no tidak hadir, :maybe masih ragu.', $totals))
            ->action(__('Lihat daftar RSVP'), $this->dashboardUrl())
            ->line(__('Kami hanya mengirim satu email per beberapa menit, jadi jawaban lain mungkin sudah masuk.'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->getKey(),
            'rsvp_id' => $this->rsvp->getKey(),
            'name' => $this->rsvp->name,
            'attendance' => $this->rsvp->attendance->value,
            'url' => $this->dashboardUrl(),
            'message' => __(':guest menjawab :answer.', [
                'guest' => $this->rsvp->name,
                'answer' => $this->rsvp->attendance->label(),
            ]),
        ];
    }

    /**
     * @return array<string, int|string>
     */
    private function totals(): array
    {
        $rsvps = $this->invitation->rsvps();

        return [
            'yes' => (clone $rsvps)->where('attendance', 'yes')->count(),
            'pax' => (int) (clone $rsvps)->where('attendance', 'yes')->sum('pax'),
            'no' => (clone $rsvps)->where('attendance', 'no')->count(),
            'maybe' => (clone $rsvps)->where('attendance', 'maybe')->count(),
        ];
    }

    private function dashboardUrl(): string
    {
        return route('client.invitations.rsvps.index', $this->invitation);
    }
}
