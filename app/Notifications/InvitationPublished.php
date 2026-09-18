<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "Your invitation is live" (23.3, M8.1).
 *
 * The public URL is the point of this email: it is what the client copies
 * into WhatsApp, so it is the first thing in the body and the button.
 */
class InvitationPublished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Invitation $invitation) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Undangan Anda sudah terbit'))
            ->greeting(__('Halo, :name!', ['name' => $notifiable->name]))
            ->line(__(':title sudah bisa dibuka tamu Anda.', ['title' => $this->invitation->title]))
            ->line($this->publicUrl())
            ->action(__('Lihat undangan'), $this->publicUrl())
            ->line(__('Aktif sampai :date.', [
                'date' => $this->invitation->expires_at?->timezone($this->invitation->timezone)->translatedFormat('d F Y') ?? '-',
            ]))
            ->line(__('Anda masih bisa mengubah isinya kapan saja — perubahan langsung terlihat.'));
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
            'url' => $this->publicUrl(),
            'expires_at' => $this->invitation->expires_at?->toIso8601String(),
            'message' => __(':title sudah terbit.', ['title' => $this->invitation->title]),
        ];
    }

    private function publicUrl(): string
    {
        return route('invitation.show', ['slug' => $this->invitation->slug]);
    }
}
