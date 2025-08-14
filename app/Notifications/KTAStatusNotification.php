<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class KTAStatusNotification extends Notification
{
    use Queueable;

    protected $status;
    protected $komentar;

    public function __construct($status, $komentar = null)
    {
        $this->status_diterima = $status;
        $this->komentar = $komentar;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $subject = 'Status Pendaftaran KTA Anda di PERKINDO';
        
        if ($this->status_diterima === 'approve') {
            return (new MailMessage)
                ->subject($subject)
                ->greeting('Halo ' . $notifiable->name . ',')
                ->line('Selamat! Pendaftaran KTA Anda telah **disetujui** oleh admin PERKINDO.')
                ->line('Silakan login ke akun Anda untuk melanjutkan proses berikutnya.')
                ->salutation('Hormat kami,')
                ->line('Tim PERKINDO');
        }

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Mohon maaf, pendaftaran KTA Anda telah **ditolak** oleh admin PERKINDO.')
            ->line('Komentar admin:')
            ->line($this->komentar ?: '-')
            ->line('Silakan login dan perbaiki data Anda sebelum mengajukan kembali.')
            ->salutation('Hormat kami,')
            ->line('Tim PERKINDO');
    }
}
