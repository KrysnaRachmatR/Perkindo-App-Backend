<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Undangan Rapat</title>
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

        .info-surat {
            margin-bottom: 20px;
        }

        .info-surat table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-surat td {
            padding: 2px 4px;
            vertical-align: top;
        }

        .content {
            text-align: justify;
            margin-bottom: 40px;
        }

        table.detail-rapat td {
            padding: 3px 6px;
            vertical-align: top;
        }

        ul {
            margin: 0;
            padding-left: 18px;
        }

        .signature {
            margin-top: 60px;
            text-align: center;
        }

        .signature img {
            height: 200px;
            width: 500px;
        }

        .note {
            text-align: left;
            font-style: italic;
            margin-top: 10px;
        }

        .page-break {
            page-break-after: always;
        }

        .peserta-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .peserta-table th,
        .peserta-table td {
            border: 1px solid #000;
            padding: 6px;
            font-size: 11pt;
        }

        .peserta-table th {
            background-color: #eee;
        }

        h3 {
            text-align: center;
            margin-bottom: 10px;
        }

    </style>
</head>
<body>

    {{-- KOP --}}
<div class="kop">
     <table width="100%" style="border-bottom: 4px solid #003366; border-collapse: collapse;">
        <tr>
            {{-- Logo di kiri --}}
            <td width="13%" style="vertical-align: top;">
                <img src="{{ public_path('storage/kop/kop.png') }}" alt="Logo PERKINDO" style="width: 120px; height: 120px;">
                {{-- Jika ada logo tambahan --}}
            </td>

            {{-- Tulisan utama di tengah, rata kiri --}}
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

            {{-- Alamat di kanan, kecil dan rata kanan --}}
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

    {{-- INFO SURAT --}}
    <div class="info-surat">
        <table>
            <tr>
                <td style="width: 100px;"><strong>Nomor</strong></td>
                <td style="width: 10px;">:</td>
                <td><strong>{{ $rapat->nomor ?? '-' }}</strong></td>
            </tr>
            <tr>
                <td><strong>Lampiran</strong></td>
                <td>:</td>
                <td><strong>{{ $rapat->lampiran ?? '-' }}</strong></td>
            </tr>
            <tr>
                <td><strong>Hal</strong></td>
                <td>:</td>
                <td><strong>{{ $rapat->hal ?? '-' }}</strong></td>
            </tr>
        </table>
    </div>

    {{-- TUJUAN --}}
    <p>Yth.<br>
    Bapak/Ibu Pengurus Harian<br>
    DPD PERKINDO Provinsi Kalimantan Barat<br>
    di Tempat</p>

    <p>Assalamualaikum Warahmatullahi Wabarakatuh.</p>

    {{-- ISI UNDANGAN --}}
    <div class="content">
        Dengan ini kami mengundang Bapak/Ibu untuk hadir dalam rapat yang akan dilaksanakan pada:
        <br><br>
        <table class="detail-rapat">
            <tr>
                <td>Hari / Tanggal</td>
                <td>:</td>
                <td>{{ \Carbon\Carbon::parse($rapat->tanggal_terpilih)->translatedFormat('l, d F Y') }}</td>
            </tr>
            <tr>
                <td>Jam</td>
                <td>:</td>
                <td>13.00 WIB – Selesai</td>
            </tr>
            <tr>
                <td>Tempat</td>
                <td>:</td>
                <td>{{ $rapat->lokasi }}</td>
            </tr>
            <tr>
                <td>Agenda</td>
                <td>:</td>
                <td>
                    @if (is_array($rapat->topik))
                        <ul>
                            @foreach ($rapat->topik as $topik)
                                <li>{{ $topik }}</li>
                            @endforeach
                        </ul>
                    @else
                        {{ $rapat->agenda ?? '-' }}
                    @endif
                </td>
            </tr>
        </table>

        <br>
        Demikian disampaikan. Atas perhatian dan kehadirannya diucapkan terima kasih.
    </div>

    {{-- TANDA TANGAN --}}
    <div class="signature">
        @if ($rapat->tanda_tangan_image)
            <img src="{{ public_path('storage/' . $rapat->tanda_tangan_image) }}">
        @endif
        <br>
    </div>

    <div class="note">Diharapkan datang tepat waktu</div>

    {{-- HALAMAN 2 --}}
    <div class="page-break"></div>
    <div class="kop">
     <table width="100%" style="border-bottom: 4px solid #003366; border-collapse: collapse;">
        <tr>
            {{-- Logo di kiri --}}
            <td width="13%" style="vertical-align: top;">
                <img src="{{ public_path('storage/kop/kop.png') }}" alt="Logo PERKINDO" style="width: 120px; height: 120px;">
                {{-- Jika ada logo tambahan --}}
            </td>

            {{-- Tulisan utama di tengah, rata kiri --}}
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

            {{-- Alamat di kanan, kecil dan rata kanan --}}
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
    <h3>Daftar Peserta Rapat</h3>
    <table class="peserta-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Peserta</th>
                <th>Jabatan</th>
                <th>Pengurus</th>
                <th>Hadir</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rapat->pesertaRapats as $index => $peserta)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $peserta->user->name }}</td>
                    <td>{{ $peserta->jabatan ?? '-' }}</td>
                    <td>{{ $peserta->is_pengurus ? 'Ya' : 'Tidak' }}</td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
