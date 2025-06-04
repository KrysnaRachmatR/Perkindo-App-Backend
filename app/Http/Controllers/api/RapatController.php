<?php

namespace App\Http\Controllers\Api;

use App\Models\Rapat;
use App\Models\User;
use App\Mail\UndanganRapatMail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
// use App\Notifications\UndanganRapatNotification;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;


class RapatController extends Controller
{
    // USER FUNCTON
    public function undanganRapat()
    {
        $userId = auth()->id();

        $rapats = Rapat::whereHas('pesertaRapats', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->whereHas('admin') // pastikan hanya rapat yang dibuat oleh admin
        ->with([
            'pollingTanggals',
            'pesertaRapats.user:id,nama_direktur,nama_perusahaan',
            'admin'
        ])
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json([
            'undangan_rapat' => $rapats
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
            'urgensi' => 'nullable|in:rutin,mendesak,kritis',
            'tanggal_terpilih' => 'required|date|after_or_equal:today',
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
                'urgensi' => $data['urgensi'] ?? null,
                'tanggal_terpilih' => $tanggalTerpilih,
                'status' => 'finalisasi',
                'created_by' => auth('admin')->id(),
                'nomor' => $data['nomor'] ?? null,
                'lampiran' => $data['lampiran'] ?? null,
                'hal' => $data['hal'] ?? null,
                'header_image' => $headerPath,
                'tanda_tangan_image' => $footerPath,
                'topik' => $data['topik'] ?? [],
            ]);

            // Tambah peserta rapat
            foreach ($data['peserta'] as $peserta) {
                $user = User::find($peserta['user_id']);
                if (!$user) continue;

                $rapat->pesertaRapats()->create([
                    'user_id' => $user->id,
                    'jabatan' => $user->jabatan ?? null,
                ]);
            }

            // Generate PDF
            $rapat->load('pesertaRapats.user');
            $pdf = Pdf::loadView('pdf.undangan', ['rapat' => $rapat]);

            $filename = "undangan/rapat-{$rapat->id}.pdf";
            Storage::disk('public')->put($filename, $pdf->output());

            DB::commit();

            // Kirim email undangan ke peserta (setelah commit dan peserta tersimpan)
            foreach ($rapat->pesertaRapats as $pesertaRapat) {
                $user = $pesertaRapat->user;
                if ($user && $user->email) {
                    Mail::to($user->email)->send(new UndanganRapatMail($rapat));
                }
            }

            return response()->json([
                'message' => 'Rapat dan undangan berhasil dibuat.',
                'rapat' => $rapat,
                'undangan_url' => Storage::url($filename),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terjadi kesalahan saat menyimpan rapat.',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
        $data = $request->validate([
            'judul' => 'sometimes|required|string|max:255',
            'agenda' => 'nullable|string',
            'lokasi' => 'nullable|string',
            'urgensi' => 'nullable|string',
            'tanggal_polling_terakhir' => 'nullable|date',
            'tanggal_terpilih' => 'nullable|date',
            'file_undangan_pdf' => 'nullable|string',
            'status' => 'nullable|string'
        ]);

        $rapat = Rapat::findOrFail($id);
        $rapat->update($data);

        return response()->json($rapat, 200);
    }

    // Hapus rapat
    public function destroy($id)
{
    $rapat = Rapat::findOrFail($id);

    // Hapus file undangan
    if ($rapat->file_undangan && Storage::disk('public')->exists($rapat->file_undangan)) {
        Storage::disk('public')->delete($rapat->file_undangan);
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

    return response()->json(['message' => 'Rapat dan semua file terkait berhasil dihapus.'], 200);
}

}
