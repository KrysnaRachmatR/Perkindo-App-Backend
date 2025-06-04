@component('mail::message')
# Hai {{ optional($rapat->pesertaRapats->first())->user->name ?? 'Peserta' }}

Anda menerima undangan rapat:

**Judul:** {{ $rapat->judul }}  
**Tanggal:** {{ \Carbon\Carbon::parse($rapat->tanggal_terpilih)->translatedFormat('l, d F Y') }}  
**Lokasi:** {{ $rapat->lokasi ?? '-' }}

Agenda:  
{{ $rapat->agenda ?? '-' }}

Silakan lihat file undangan resmi dalam bentuk PDF yang dilampirkan di email ini.

Terima kasih,  
{{ config('app.name') }}
@endcomponent
