<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Notulensi</title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            font-size: 12pt;
            line-height: 1.5;
            margin: 30px;
        }

        .kop {
            text-align: center;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .kop img {
            height: 80px;
        }

        .content {
            text-align: justify;
            margin-bottom: 30px;
        }

        table.detail-rapat td {
            padding: 3px 6px;
            vertical-align: top;
        }

        .page-break {
            page-break-after: always;
        }

        .peserta-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .peserta-table th,
        .peserta-table td {
            border: 1px solid #000;
            padding: 6px;
            font-size: 11pt;
        }

        .peserta-table th {
            background-color: #f0f0f0;
            text-align: center;
        }

        .peserta-table td {
            vertical-align: middle;
        }

        .ttd-img {
            max-height: 40px;
            width: auto;
        }
    </style>
</head>
<body>

    {{-- Header Kop Surat --}}
    <div class="kop">
        <table width="100%" style="border-bottom: 4px solid #003366; border-collapse: collapse;">
            <tr>
                {{-- Logo Kiri --}}
                <td width="13%" style="vertical-align: top;">
                    <img src="{{ public_path('storage/kop/kop.png') }}" alt="Logo PERKINDO" style="width: 120px; height: 120px;">
                </td>

                {{-- Tulisan Tengah --}}
                <td width="60%" style="vertical-align: top; padding-left: 10px;">
                    <div style="font-weight: bold; font-size: 12pt; color: #003366; line-height: 1.3; text-align: left;">
                        DEWAN PENGURUS DAERAH<br>
                        PERSATUAN KONSULTAN INDONESIA<br>
                        PROVINSI KALIMANTAN BARAT
                    </div>
                    <div style="font-style: italic; font-weight: bold; color: green; font-size: 12pt; margin-top: 6px; text-align: left;">
                        Profesional Dalam Berkarya
                    </div>
                </td>

                {{-- Alamat Kanan --}}
                <td width="35%" style="font-size: 7.2pt; text-align: right; vertical-align: top; line-height: 1; color: #003366; font-weight: bold;">
                    Jl. Alianyang Gg. Rahayu Ruko No. 21<br>
                    Kel. Sei. Bangkong Kec. Pontianak Kota,<br>
                    Kota Pontianak – Kalimantan Barat,<br>
                    Kode Pos 78116<br>
                    Telp. +6281259050979,<br>
                    E-mail : perkindokalbar@gmail.com,<br>
                    www.perkindokalbar.com
                </td>
            </tr>
        </table>
    </div>

    {{-- Judul dan Info Rapat --}}
    <h2 style="text-align: center; margin-bottom: 10px;">Notulensi Rapat</h2>
    <p><strong>Judul:</strong> {{ $notulensi->rapat->judul ?? '-' }}</p>
    <p><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($notulensi->rapat->tanggal_rapat)->format('d-m-Y') }}</p>
    <hr>

    {{-- Isi Notulensi --}}
    <div class="content">
        {!! nl2br(e($notulensi->isi)) !!}
    </div>

    {{-- Daftar Absensi --}}
    <h3 style="text-align: center;">Daftar Absensi Peserta</h3>
    <table class="peserta-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 35%;">Nama Peserta</th>
                <th style="width: 25%;">Waktu Absen</th>
                <th style="width: 35%;">Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($absensiList as $index => $absen)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>{{ $absen->user->name ?? '-' }}</td>
                    <td style="text-align: center;">
                        {{ $absen->waktu_absen ? \Carbon\Carbon::parse($absen->waktu_absen)->format('d-m-Y H:i') : '-' }}
                    </td>
                    <td style="text-align: center;">
                        @if($absen->signature_path)
                            <img class="ttd-img" src="{{ public_path('storage/' . $absen->signature_path) }}" alt="Tanda Tangan">
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
