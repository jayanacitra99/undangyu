<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "Your invitation is ready" (playbook 11.2).
 *
 * Mail plus the database channel: the email is what the client acts on, the
 * database row is what the dashboard bell shows when they were already logged
 * in and never opened it.
 */
class InvitationProvisioned extends Notification implements ShouldQueue
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
            ->subject(__('Undangan Anda siap dibuat'))
            ->greeting(__('Halo, :name!', ['name' => $notifiable->name]))
            ->line(__('Pembayaran Anda sudah kami terima dan :title sudah dibuat sebagai draf.', [
                'title' => $this->invitation->title,
            ]))
            ->line(__('Lengkapi data mempelai, acara dan galeri, lalu terbitkan undangan Anda.'))
            ->action(__('Buka builder'), $this->builderUrl())
            ->line(__('Undangan baru aktif setelah Anda menerbitkannya, jadi tidak ada masa aktif yang terbuang.'));
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
            // The id, not the relation: this runs on a queue worker where
            // preventLazyLoading is on, and the order is not loaded.
            'order_id' => $this->invitation->order_id,
            'url' => $this->builderUrl(),
            'message' => __(':title siap dilengkapi.', ['title' => $this->invitation->title]),
        ];
    }

    /**
     * Straight into the builder for this invitation — the client's next step
     * is filling it in, not finding it in a list.
     */
    private function builderUrl(): string
    {
        return route('client.invitations.edit', $this->invitation);
    }
}
