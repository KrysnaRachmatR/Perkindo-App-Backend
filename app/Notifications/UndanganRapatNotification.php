<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Storage;

class UndanganRapatNotification extends Notification
{
    use Queueable;

    protected $rapat;
    protected $peserta;

    public function __construct($rapat, $peserta)
    {
        $this->rapat = $rapat;
        $this->peserta = $peserta;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->subject('Undangan Rapat')
            ->greeting('Yth. ' . $notifiable->name)
            ->line('Anda diundang untuk menghadiri rapat yang akan diselenggarakan oleh PERKINDO.')
            ->line('Detail Rapat:')
            ->line('📅 Tanggal: ' . $this->rapat->tanggal_terpilih)
            // ->line('⏰ Waktu: ' . $this->rapat->waktu)
            ->line('🏢 Tempat: ' . $this->rapat->lokasi)
            ->line('📄 Agenda: ' . $this->rapat->agenda)
            ->salutation('Hormat kami,')
            ->line('Sekretariat PERKINDO');

        // Cek jika file undangan ada dan attach file
        if (!empty($this->rapat->undangan) && Storage::disk('public')->exists($this->rapat->undangan)) {
            $path = Storage::disk('public')->path($this->rapat->undangan);
            $mail->attach($path);
        }

        return $mail;
    }
}
