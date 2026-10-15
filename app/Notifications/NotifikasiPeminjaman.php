<?php

namespace App\Notifications;

use App\Models\Peminjaman;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Dasar semua notifikasi peminjaman: disimpan di database (menu Notifikasi)
 * dan dikirim lewat email (lokal: MAIL_MAILER=log → storage/logs/laravel.log).
 */
abstract class NotifikasiPeminjaman extends Notification
{
    use Queueable;

    public function __construct(public Peminjaman $peminjaman)
    {
    }

    abstract public function judul(): string;

    abstract public function pesan(): string;

    /** URL tujuan saat notifikasi diklik, sesuai peran penerima. */
    public function url(object $notifiable): string
    {
        return $notifiable->isAdmin()
            ? route('admin.peminjaman.show', $this->peminjaman)
            : route('peminjam.pinjaman.show', $this->peminjaman);
    }

    /** Baris tambahan di email (opsional). */
    protected function rincian(): array
    {
        return [];
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject($this->judul().' — '.$this->peminjaman->kode_peminjaman)
            ->greeting('Halo, '.$notifiable->name)
            ->line($this->pesan());

        foreach ($this->rincian() as $baris) {
            $mail->line($baris);
        }

        return $mail->action('Lihat Detail', $this->url($notifiable))
            ->salutation('Salam, '.config('peminjaman.mitra'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'peminjaman_id' => $this->peminjaman->id,
            'kode' => $this->peminjaman->kode_peminjaman,
            'judul' => $this->judul(),
            'pesan' => $this->pesan(),
            'url' => $this->url($notifiable),
        ];
    }

    protected function rentang(): string
    {
        return $this->peminjaman->tanggal_pinjam->translatedFormat('d F Y').' s/d '
            .$this->peminjaman->tanggal_rencana_kembali->translatedFormat('d F Y');
    }
}
