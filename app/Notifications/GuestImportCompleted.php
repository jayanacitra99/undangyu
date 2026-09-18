<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\GuestImportStatus;
use App\Models\GuestImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "Your guest list finished importing" (25.5).
 *
 * A 500-row import takes long enough that the client has left the page, so the
 * result has to find them: mail for the answer, the database channel for the
 * dashboard bell when they were still logged in.
 *
 * The subject carries the counts, because "488 of 500" is the whole message
 * for most imports and a client should not have to open anything to read it.
 */
class GuestImportCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly GuestImport $import) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->import->status === GuestImportStatus::Failed) {
            return (new MailMessage)
                ->subject(__('Impor tamu gagal'))
                ->greeting(__('Halo, :name!', ['name' => $notifiable->name]))
                ->line(__('Berkas :file tidak bisa kami baca.', ['file' => $this->import->original_filename]))
                ->line(__('Unduh templat dari halaman tamu, isi ulang, lalu coba lagi.'))
                ->action(__('Buka daftar tamu'), $this->guestsUrl());
        }

        $message = (new MailMessage)
            ->subject(__('Impor tamu selesai: :success dari :total baris', [
                'success' => $this->import->success_rows,
                'total' => $this->import->total_rows,
            ]))
            ->greeting(__('Halo, :name!', ['name' => $notifiable->name]))
            ->line(__(':success tamu berhasil ditambahkan dari :file.', [
                'success' => $this->import->success_rows,
                'file' => $this->import->original_filename,
            ]));

        if ($this->import->failed_rows > 0) {
            $message->line(__(':failed baris tidak bisa diimpor. Laporan kesalahannya menyebut nomor barisnya.', [
                'failed' => $this->import->failed_rows,
            ]));
        }

        return $message->action(__('Buka daftar tamu'), $this->guestsUrl());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'guest_import_id' => $this->import->getKey(),
            // The id, not the relation: this runs on a queue worker where
            // preventLazyLoading is on.
            'invitation_id' => $this->import->invitation_id,
            'status' => $this->import->status->value,
            'success_rows' => $this->import->success_rows,
            'failed_rows' => $this->import->failed_rows,
            'url' => $this->guestsUrl(),
            'message' => __('Impor tamu selesai: :success berhasil, :failed gagal.', [
                'success' => $this->import->success_rows,
                'failed' => $this->import->failed_rows,
            ]),
        ];
    }

    /**
     * The invitation, not its id: the route binds on the slug, and a URL built
     * from the key resolves to nothing. The job eager-loads the relation, which
     * is what makes this safe on a worker with preventLazyLoading on.
     */
    private function guestsUrl(): string
    {
        return route('client.invitations.guests.index', $this->import->invitation);
    }
}
