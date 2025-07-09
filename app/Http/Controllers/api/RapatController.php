<?php

namespace App\Http\Controllers\Api;

use App\Models\Notulensi;
use App\Models\NotulensiFile;
use App\Models\Rapat;
use App\Models\User;
use App\Mail\UndanganRapatMail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;


class RapatController extends Controller
{
    // USER FUNCTON
    public function undanganMasuk()
{
    $user = auth()->user();

    // Cek apakah user sudah login
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User belum login atau token tidak valid.',
        ], 401); // HTTP 401 Unauthorized
    }

    // Ambil rapat yang diikuti user
    $rapats = Rapat::whereHas('pesertaRapats', function ($query) use ($user) {
        $query->where('user_id', $user->id);
    })
    ->with(['pesertaRapats.user']) // Include data peserta
    ->orderByDesc('tanggal_terpilih')
    ->get();

    return response()->json([
        'success' => true,
        'message' => 'Daftar undangan rapat yang masuk.',
        'rapats' => $rapats
    ]);
}


    // CREATE NOTULEN
    public function createNotulen(Request $request, $rapatId)
    {
        $user = auth()->user();

        // Cek hak akses
        if (!$user->is_pengurus || strtolower($user->jabatan) !== 'ketua_sekretaris') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya ketua sekretaris yang dapat membuat notulensi.'
            ], 403);
        }

        // Validasi input
        $data = $request->validate([
            'isi' => 'required|string',
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:pdf,doc,docx|max:2048',
        ]);

        // Cek rapat
        $rapat = Rapat::find($rapatId);
        if (!$rapat) {
            return response()->json([
                'success' => false,
                'message' => 'Rapat tidak ditemukan.'
            ], 404);
        }

        // Cek tanggal rapat sudah lewat atau belum
        if (Carbon::now()->lt(Carbon::parse($rapat->tanggal_terpilih))) {
            return response()->json([
                'success' => false,
                'message' => 'Notulensi hanya dapat dibuat setelah tanggal rapat berlangsung.'
            ], 400);
        }

        // Cek apakah notulensi sudah dibuat sebelumnya
        if ($rapat->notulensi) {
            return response()->json([
                'success' => false,
                'message' => 'Notulensi sudah pernah dibuat untuk rapat ini.'
            ], 400);
        }

        // Simpan notulensi
        $notulensi = Notulensi::create([
            'rapat_id' => $rapat->id,
            'user_id' => $user->id,
            'isi' => $data['isi'],
        ]);

        // Simpan file (jika ada)
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('notulensi/dokumen', 'public');
                $notulensi->files()->create([
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Notulensi berhasil disimpan.',
            'notulensi' => $notulensi->load('files'),
        ], 201);
    }

    public function getNotulen($rapatId)
    {
        $user = auth()->user();

        $rapat = Rapat::with(['notulensi.files'])->find($rapatId);

        if (!$rapat) {
            return response()->json([
                'success' => false,
                'message' => 'Rapat tidak ditemukan.'
            ], 404);
        }

        // Cek apakah user adalah peserta rapat
        $isPeserta = $rapat->pesertaRapats()->where('user_id', $user->id)->exists();

        if (!$isPeserta) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak diundang ke rapat ini.'
            ], 403);
        }

        if (!$rapat->notulensi) {
            return response()->json([
                'success' => false,
                'message' => 'Notulensi belum tersedia.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notulensi ditemukan.',
            'notulensi' => [
                'isi' => $rapat->notulensi->isi,
                'ditulis_oleh' => $rapat->notulensi->user->name ?? 'N/A',
                'files' => $rapat->notulensi->files->map(function ($file) {
                    return [
                        'original_name' => $file->original_name,
                        'file_url' => asset('storage/' . $file->file_path),
                    ];
                }),
            ]
        ]);
    }

    public function selesai()
    {
        $user = auth()->user();

        $rapats = Rapat::with(['pesertaRapats'])
            ->where('status', 'selesai')
            ->whereHas('pesertaRapats', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orderBy('tanggal_terpilih', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rapats,
        ]);
    }

// -------------{}{}{}--------------//

    // ADMIN FUNCTON
    public function store(Request $request)
    {
        if (!auth('admin')->check()) {
            return response()->json(['message' => 'Akses ditolak. Hanya admin yang dapat membuat rapat.'], 403);
        }

        $data = $request->validate([
            'judul' => 'required|string|max:255',
            'agenda' => 'nullable|string',
            'lokasi' => 'nullable|string',
            'urgensi' => 'required|in:rutin,mendesak,kritis',
            'tanggal_terpilih' => 'required|date|after_or_equal:today',
            'jam' => 'nullable|date_format:H:i',
            'nomor' => 'nullable|string|max:255',
            'lampiran' => 'nullable|string|max:255',
            'hal' => 'nullable|string|max:255',
            'header_image' => 'nullable|image|mimes:jpg,jpeg,png',
            'tanda_tangan_image' => 'nullable|image|mimes:jpg,jpeg,png',
            'peserta' => 'required|array|min:1',
            'peserta.*.user_id' => 'required|integer|exists:users,id',
            'topik' => 'nullable|array',
            'topik.*' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            Carbon::setLocale('id');
            $tanggalTerpilih = Carbon::parse($data['tanggal_terpilih']);

            // Siapkan waktu lengkap (tanggal + jam)
            $rapatWaktu = $tanggalTerpilih->copy();
            if (!empty($data['jam'])) {
                [$jam, $menit] = explode(':', $data['jam']);
                $rapatWaktu->setTime($jam, $menit);
            }

            // ✅ Cek tabrakan jadwal rapat
            $existingRapat = Rapat::whereDate('tanggal_terpilih', $tanggalTerpilih)
                ->where('jam', $data['jam'] ?? null)
                ->first();

            if ($existingRapat) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sudah ada rapat yang dijadwalkan pada tanggal dan jam yang sama.'
                ], 409);
            }

            // Tentukan status berdasarkan urgensi dan waktu
            $isRapatSelesai = $rapatWaktu->lt(Carbon::now());
            $status = 'draft';
            $pengirimanDijadwalkan = null;

            if ($isRapatSelesai) {
                $status = 'selesai';
            } elseif ($data['urgensi'] === 'rutin') {
                $pengirimanDijadwalkan = $tanggalTerpilih->copy()->subDays(3);
                if ($pengirimanDijadwalkan->lte(Carbon::now())) {
                    $status = 'finalisasi';
                }
            } elseif ($data['urgensi'] === 'mendesak') {
                $pengirimanDijadwalkan = $tanggalTerpilih->copy()->subDays(2);
                if ($pengirimanDijadwalkan->lte(Carbon::now())) {
                    $status = 'finalisasi';
                }
            } elseif ($data['urgensi'] === 'kritis') {
                $status = 'finalisasi';
            }

            $headerPath = $request->hasFile('header_image')
                ? $request->file('header_image')->store('undangan/header', 'public')
                : null;

            $footerPath = $request->hasFile('tanda_tangan_image')
                ? $request->file('tanda_tangan_image')->store('undangan/footer', 'public')
                : null;

            $rapat = Rapat::create([
                'judul' => $data['judul'],
                'agenda' => $data['agenda'] ?? null,
                'lokasi' => $data['lokasi'] ?? null,
                'urgensi' => $data['urgensi'],
                'tanggal_terpilih' => $tanggalTerpilih,
                'jam' => $data['jam'] ?? null,
                'status' => $status,
                'created_by' => auth('admin')->id(),
                'nomor' => $data['nomor'] ?? null,
                'lampiran' => $data['lampiran'] ?? null,
                'hal' => $data['hal'] ?? null,
                'header_image' => $headerPath,
                'tanda_tangan_image' => $footerPath,
                'topik' => $data['topik'] ?? [],
                'pengiriman_dijadwalkan_pada' => $pengirimanDijadwalkan,
            ]);

            // Simpan peserta
            foreach ($data['peserta'] as $peserta) {
                $user = User::find($peserta['user_id']);
                if (!$user) continue;

                $rapat->pesertaRapats()->create([
                    'user_id' => $user->id,
                    'jabatan' => $user->jabatan ?? null,
                ]);
            }

            // Generate PDF undangan
            $rapat->load('pesertaRapats.user');
            $pdf = \PDF::loadView('pdf.undangan', ['rapat' => $rapat]);
            $filename = "undangan/rapat-{$rapat->id}.pdf";
            \Storage::disk('public')->put($filename, $pdf->output());
            $downloadUrl = \Storage::disk('public')->url($filename);

            // Simpan ke kolom file_undangan_pdf
            $rapat->update([
                'file_undangan_pdf' => $filename,
            ]);

            // Kirim email jika finalisasi dan waktu belum lewat
            if ($status === 'finalisasi' && !$isRapatSelesai) {
                foreach ($rapat->pesertaRapats as $pesertaRapat) {
                    $user = $pesertaRapat->user;
                    if ($user && $user->email) {
                        \Mail::to($user->email)->send(new \App\Mail\UndanganRapatMail($rapat));
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rapat berhasil disimpan.',
                'rapat' => $rapat,
                'catatan' => $isRapatSelesai
                    ? 'Rapat telah selesai karena waktunya sudah lewat.'
                    : ($status === 'finalisasi' ? 'Undangan langsung dikirim.' : 'Menunggu pengiriman otomatis.'),
                'download_url' => $downloadUrl,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan rapat.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Tampilkan detail 1 rapat
    public function show($id)
    {
        $rapat = Rapat::with([
            'pesertaRapats.user', 
            'pollingTanggals.votes', 
            'usulanTanggals',
            'hasilSpkTanggal'
        ])->findOrFail($id);

        return response()->json($rapat, 200);
    }

    // Update rapat
    public function update(Request $request, $id)
    {
        $rapat = Rapat::findOrFail($id);

        // Cegah update jika rapat sudah selesai
        if ($rapat->status === 'selesai') {
            return response()->json(['message' => 'Rapat sudah selesai dan tidak dapat diubah.'], 403);
        }

        $data = $request->validate([
            'judul' => 'sometimes|required|string|max:255',
            'agenda' => 'nullable|string',
            'lokasi' => 'nullable|string',
            'urgensi' => 'nullable|in:rutin,mendesak,kritis',
            'tanggal_terpilih' => 'nullable|date',
            'jam' => 'nullable|date_format:H:i',
            'nomor' => 'nullable|string|max:255',
            'lampiran' => 'nullable|string|max:255',
            'hal' => 'nullable|string|max:255',
            'topik' => 'nullable|array',
            'topik.*' => 'nullable|string|max:255',
            'peserta' => 'nullable|array|min:1',
            'peserta.*.user_id' => 'required_with:peserta|integer|exists:users,id',
        ]);

        // Ambil tanggal & jam baru (kalau diubah), jika tidak ambil dari data lama
        $tanggalBaru = isset($data['tanggal_terpilih']) 
            ? Carbon::parse($data['tanggal_terpilih']) 
            : Carbon::parse($rapat->tanggal_terpilih);

        $jamBaru = $data['jam'] ?? $rapat->jam;

        // Cek tabrakan jadwal jika tanggal atau jam diubah
        if (isset($data['tanggal_terpilih']) || isset($data['jam'])) {
            $tabrakan = Rapat::whereDate('tanggal_terpilih', $tanggalBaru->toDateString())
                ->where('jam', $jamBaru)
                ->where('id', '!=', $rapat->id)
                ->exists();

            if ($tabrakan) {
                return response()->json([
                    'message' => 'Sudah ada rapat lain yang dijadwalkan pada waktu yang sama.'
                ], 409);
            }
        }

        // =======================
        // Logika Update Status
        // =======================
        $statusBaru = $rapat->status; // default: tetap
        $pengirimanDijadwalkan = $rapat->pengiriman_dijadwalkan_pada;

        if (isset($data['urgensi'])) {
            if ($data['urgensi'] === 'rutin') {
                $pengirimanDijadwalkan = $tanggalBaru->copy()->subDays(3);
            } elseif ($data['urgensi'] === 'mendesak') {
                $pengirimanDijadwalkan = $tanggalBaru->copy()->subDays(2);
            } elseif ($data['urgensi'] === 'kritis') {
                return response()->json([
                    'message' => 'Urgensi tidak dapat diubah menjadi kritis melalui update. Gunakan fitur finalisasi.'
                ], 403);
            }
        }

        // Jika waktu pengiriman sudah lewat dan status belum finalisasi → ubah status
        if ($pengirimanDijadwalkan && Carbon::now()->gte($pengirimanDijadwalkan)) {
            $statusBaru = 'finalisasi';
            $pengirimanDijadwalkan = null; // tidak diperlukan lagi
        }

        $data['pengiriman_dijadwalkan_pada'] = $pengirimanDijadwalkan;
        $data['status'] = $statusBaru;

        // Update rapat
        $rapat->update($data);

        // Update peserta jika dikirim
        if (isset($data['peserta'])) {
            $rapat->pesertaRapats()->delete();
            foreach ($data['peserta'] as $peserta) {
                $user = User::find($peserta['user_id']);
                if ($user) {
                    $rapat->pesertaRapats()->create([
                        'user_id' => $user->id,
                        'jabatan' => $user->jabatan ?? null,
                    ]);
                }
            }
        }

        // ===== Kirim Email jika urgensi mendesak dan pengiriman sudah lewat =====
        if ($statusBaru === 'finalisasi') {
            $rapat->load('pesertaRapats.user');
            $pdf = \PDF::loadView('pdf.undangan', ['rapat' => $rapat]);
            $filename = "undangan/rapat-{$rapat->id}.pdf";
            \Storage::disk('public')->put($filename, $pdf->output());

            $rapat->update([
                'file_undangan_pdf' => $filename
            ]);

            foreach ($rapat->pesertaRapats as $pesertaRapat) {
                $user = $pesertaRapat->user;
                if ($user && $user->email) {
                    \Mail::to($user->email)->send(new \App\Mail\UndanganRapatMail($rapat));
                }
            }
        }

        return response()->json([
            'message' => 'Rapat berhasil diperbarui.',
            'rapat' => $rapat->load('pesertaRapats.user')
        ]);
    }

    // Hapus rapat
    public function destroy($id)
    {
        $rapat = Rapat::findOrFail($id);

        // Tidak bisa menghapus rapat yang sudah difinalisasi
        if ($rapat->status === 'selesai') {
            return response()->json(['message' => 'Rapat sudah difinalisasi dan tidak dapat dihapus.'], 403);
        }

        // Hapus file undangan PDF
        if ($rapat->file_undangan_pdf && Storage::disk('public')->exists($rapat->file_undangan_pdf)) {
            Storage::disk('public')->delete($rapat->file_undangan_pdf);
        }

        // Hapus header image
        if ($rapat->header_image && Storage::disk('public')->exists($rapat->header_image)) {
            Storage::disk('public')->delete($rapat->header_image);
        }

        // Hapus tanda tangan image
        if ($rapat->tanda_tangan_image && Storage::disk('public')->exists($rapat->tanda_tangan_image)) {
            Storage::disk('public')->delete($rapat->tanda_tangan_image);
        }

        $rapat->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rapat dan semua file terkait berhasil dihapus.'], 
            200);
    }

    // Lihat Rapat
    public function index()
    {
        $rapat = Rapat::with([
            'pesertaRapats.user:id,nama_direktur,nama_perusahaan',
            'creator:id,name,username',
        ])->get();

        return response()->json([
            'success' => true,
            'data' => $rapat
        ]);
    }

}
