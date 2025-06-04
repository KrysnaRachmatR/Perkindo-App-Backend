<?php

namespace App\Mail;

use App\Models\Rapat;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UndanganRapatMail extends Mailable
{
    use Queueable, SerializesModels;

    public $rapat;

    public function __construct(Rapat $rapat)
    {
        $this->rapat = $rapat;
    }

    public function build()
    {
        return $this->markdown('emails.undangan')
            ->subject('Undangan Rapat: ' . $this->rapat->judul)
            ->attachFromStorage("public/undangan/rapat-{$this->rapat->id}.pdf")
            ->with([
                'rapat' => $this->rapat,
            ]);
    }
}
