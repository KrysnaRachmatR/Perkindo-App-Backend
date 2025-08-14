<?php

namespace App\Http\Controllers\Api;

use App\Models\Notulensi;
use App\Models\NotulensiFile;
use App\Models\Rapat;
use App\Models\User;
use App\Models\PollingTanggal;
use App\Models\PollingTanggalOption;
use App\Models\PesertaRapat;
use App\Models\KesediaanHadir;
use App\Models\Absensi;
use App\Models\ApprovalKetua;
use App\Mail\UndanganRapatMail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use ZipArchive;
use Barryvdh\DomPDF\Facade\Pdf;

class RapatController extends Controller
{

    public function hitungKuorumKehadiran($rapatId)
    {
        $rapat = Rapat::with('pesertaRapats')->find($rapatId);

        if (! $rapat) {
            return response()->json([
                'message' => 'Rapat tidak ditemukan.'
            ], 404);
        }

        $totalPeserta = $rapat->pesertaRapats->count();
        $jumlahHadir = KesediaanHadir::where('id_rapat', $rapatId)
            ->where('status', 'hadir')
            ->count();

        $persentase = $totalPeserta > 0
            ? round(($jumlahHadir / $totalPeserta) * 100, 2)
            : 0;

        return response()->json([
            'rapat_id'        => $rapatId,
            'total_peserta'   => $totalPeserta,
            'jumlah_hadir'    => $jumlahHadir,
            'persentase'      => $persentase,
            'kuorum_terpenuhi' => $persentase >= 50
        ]);
    }

   public function downloadNotulensiZip($rapatId)
{
    // Cari notulensi berdasarkan rapat_id
    $notulensi = Notulensi::with('files', 'rapat')
        ->where('id_rapat', $rapatId)
        ->first();

    if (!$notulensi) {
        return response()->json([
            'success' => false,
            'message' => 'Notulensi untuk rapat ini tidak ditemukan.'
        ], 404);
    }

    // ✅ Ambil semua absensi peserta rapat
    $absensiList = Absensi::with('user') // pastikan relasi user sudah ada di model Absensi
        ->where('id_rapat', $rapatId)
        ->orderBy('waktu_absen', 'asc')
        ->get();

    $zipFileName = 'notulensi_rapat_' . $rapatId . '.zip';
    $zipPath = storage_path('app/public/' . $zipFileName);

    $zip = new \ZipArchive;

    if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {

        // 1. Tambahkan file PDF dari isi notulensi + absensi peserta
        $pdf = \PDF::loadView('notulensis.notulensi_pdf', [
            'notulensi' => $notulensi,
            'absensiList' => $absensiList // ✅ kirim data absensi ke view
        ]);

        $pdfPath = storage_path('app/temp_notulensi_' . $rapatId . '.pdf');
        $pdf->save($pdfPath);
        $zip->addFile($pdfPath, 'notulensi.pdf');

        // 2. Tambahkan semua file terkait dari notulensi_files
        foreach ($notulensi->files as $file) {
            $relativePath = str_starts_with($file->file_path, 'public/')
                ? substr($file->file_path, 7)
                : $file->file_path;

            $localPath = storage_path('app/public/' . $relativePath);

            if (\File::exists($localPath)) {
                $zip->addFile($localPath, 'notulensi/' . $file->original_name);
            } else {
                \Log::warning("File not found: " . $localPath);
            }
        }

        $zip->close();

        // Hapus file PDF sementara setelah dibuat ZIP
        \File::delete($pdfPath);

        // Kembalikan file zip sebagai response download
        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    return response()->json([
        'success' => false,
        'message' => 'Gagal membuat file ZIP.'
    ], 500);
}


    public function undanganMasuk()
    {
        $user = auth()->user();
        $rapats = Rapat::whereHas('pesertaRapats', function ($query) use ($user) {
                $query->where('id_user', $user->id);
            })
            ->whereIn('status', ['menunggu_approval_ketua'])
            ->with(['pesertaRapats.user']) // jika butuh detail peserta
            ->orderByDesc('tanggal_terpilih')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar undangan rapat yang masuk.',
            'rapats' => $rapats
        ]);
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
        $isPeserta = $rapat->pesertaRapats()->where('id_user', $user->id)->exists();

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

    public function getAllNotulensi()
    {
        $user = auth()->user();

        if (!$user->is_pengurus) {
            $notulensi = Notulensi::whereHas('rapat.pesertaRapats', function ($query) use ($user) {
                $query->where('id_user', $user->id);
            })
            ->with(['rapat', 'files', 'pembuat:id,name,email']) // 'name' untuk user biasa
            ->latest()
            ->get();
        } else {
            $notulensi = Notulensi::with(['rapat', 'files', 'pembuat:id,nama_direktur,email']) // 'nama_direktur' untuk admin
                ->latest()
                ->get();
        }

        // Mapping nama_pembuat agar konsisten
        $notulensi = $notulensi->map(function ($n) {
            $n->nama_pembuat = $n->pembuat->nama_direktur ?? $n->pembuat->name ?? '-';
            return $n;
        });

        return response()->json([
            'success' => true,
            'data' => $notulensi,
        ]);
    }
    
    public function createNotulen(Request $request, $rapatId)
    {
        $user = auth()->user();

        if (!$user->is_pengurus || strtolower($user->jabatan) !== 'ketua_sekretaris') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $data = $request->validate([
            'isi' => 'required|string',
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:pdf,doc,docx,ppt,pptx,jpg,jpeg,png',
        ]);

        $rapat = Rapat::find($rapatId);
        if (!$rapat) {
            return response()->json(['success' => false, 'message' => 'Rapat tidak ditemukan.'], 404);
        }

        if ($rapat->status !== 'selesai') {
            return response()->json(['success' => false, 'message' => 'Rapat belum selesai.'], 400);
        }

        if ($rapat->notulensi) {
            return response()->json(['success' => false, 'message' => 'Notulensi sudah dibuat.'], 400);
        }

        $notulensi = Notulensi::create([
            'id_rapat' => $rapat->id,
            'dibuat_oleh' => $user->id,
            'isi' => $data['isi'],
            'status' => 'auto_disetujui',
            'approved_at' => now(),
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $filename = 'notulensi/' . Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('public', $filename);

                $notulensi->files()->create([
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Notulensi berhasil dibuat.',
            'notulensi' => $notulensi->load('files'),
        ], 201);
    }

    public function updateNotulenByKetua(Request $request, $notulensiId)
    {
        $user = auth()->user();

        // Hak akses hanya ketua sekretaris
        if (!$user->is_pengurus || strtolower($user->jabatan) !== 'ketua_sekretaris') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya ketua sekretaris yang dapat mengedit notulensi ini.'
            ], 403);
        }

        // Validasi
        $data = $request->validate([
            'isi' => 'required|string',
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:pdf,doc,docx,ppt,pptx,jpg,jpeg,png|max:2048',
        ]);

        // Ambil notulensi dan relasi rapat
        $notulensi = Notulensi::with(['rapat', 'files'])->find($notulensiId);
        if (!$notulensi) {
            return response()->json(['success' => false, 'message' => 'Notulensi tidak ditemukan.'], 404);
        }

        // Pastikan notulensi punya id_rapat yang valid
        if (!$notulensi->id_rapat) {
            return response()->json(['success' => false, 'message' => 'Notulensi tidak memiliki relasi ke rapat.'], 400);
        }

        $rapat = $notulensi->rapat;
        if (!$rapat) {
            return response()->json([
                'success' => false,
                'message' => 'Rapat terkait tidak ditemukan.'
            ], 404);
        }

        // Cek batas waktu maksimal 14 hari setelah rapat
        $tanggalRapat = Carbon::parse($rapat->tanggal_rapat);
        if (now()->gt($tanggalRapat->copy()->addDays(14))) {
            return response()->json([
                'success' => false,
                'message' => 'Notulensi tidak dapat diedit karena sudah melewati batas waktu 14 hari setelah rapat.'
            ], 403);
        }

        // Update isi dan status
        $notulensi->update([
            'isi' => $data['isi'],
            'status' => 'auto_disetujui',
            'approved_at' => now(),
            'dibuat_oleh' => $user->id,
            'catatan_penolakan' => 'Diperbarui oleh ketua sekretaris',
        ]);

        // Jika ada file baru, hapus file lama dan simpan file baru
        if ($request->hasFile('files')) {
            // Hapus file lama
            foreach ($notulensi->files as $file) {
                if (Storage::disk('public')->exists($file->file_path)) {
                    Storage::disk('public')->delete($file->file_path);
                }
                $file->delete();
            }

            // Simpan file baru
            foreach ($request->file('files') as $file) {
                $filename = 'notulensi/' . Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('public', $filename);

                $notulensi->files()->create([
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Notulensi berhasil diperbarui.',
            'notulensi' => $notulensi->fresh('files'),
        ], 201);
    }

    public function rapatDone()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User belum login atau token tidak valid.',
            ], 401);
        }

        $rapats = Rapat::with([
                'pesertaRapats',
                'pollingTanggalOptions',
                'pollingTanggal' => function ($query) use ($user) {
                    $query->where('id_user', $user->id);
                },
                'kesediaanHadir' => function ($query) use ($user) {
                    $query->where('id_user', $user->id);
                },
            ])
            ->whereIn('status', ['menunggu_polling', 'menunggu_polling_tanggal', 'selesai'])
            ->whereHas('pesertaRapats', function ($query) use ($user) {
                $query->where('id_user', $user->id);
            })
            ->orderByDesc('tanggal_terpilih')
            ->get();

        $rapatsFormatted = $rapats->map(function ($rapat) {
            // Kumpulan data polling tanggal user
            $pollingTanggalUser = $rapat->pollingTanggal ?? collect();
            $kesediaanUser = $rapat->kesediaanHadir->first();

            $data = [
                'id' => $rapat->id,
                'judul' => $rapat->judul,
                'agenda' => $rapat->agenda,
                'jam' => $rapat->jam,
                'hal' => $rapat->hal,
                'lokasi' => $rapat->lokasi,
                'urgensi' => $rapat->urgensi,
                'status' => $rapat->status,
                'tanggal_terpilih' => optional($rapat->tanggal_terpilih)->format('Y-m-d'),
            ];

            // Jika statusnya menunggu polling tanggal
            if ($rapat->status === 'menunggu_polling_tanggal') {
                $data['user_sudah_memilih_polling_tanggal'] = $pollingTanggalUser->isNotEmpty();
                $data['tanggal_yang_dipilih'] = $pollingTanggalUser->pluck('tanggal_yang_dipilih');

                $data['opsi_tanggal_polling'] = $rapat->pollingTanggalOptions->map(function ($option) {
                    return [
                        'id' => $option->id,
                        'tanggal' => $option->tanggal,
                    ];
                });
            }

            // Kesediaan hadir (selalu tampilkan)
            $data['user_sudah_menjawab_kesediaan_hadir'] = $kesediaanUser !== null;
            $data['status_kesediaan'] = $kesediaanUser->status ?? null;
            $data['alasan'] = $kesediaanUser->alasan ?? null;

            return $data;
        });

        return response()->json([
            'success' => true,
            'data' => $rapatsFormatted,
        ]);
    }

    public function getRapatMenungguPolling(Request $request)
    {
        $user = auth()->user();

        $rapats = Rapat::with([
            'pesertaRapats.user:id,nama_direktur,nama_perusahaan',
            'creator:id,name,username',
            'kesediaanHadir.user:id,nama_direktur,nama_perusahaan',
        ])
        ->where('status', 'menunggu_polling')
        ->get();

        $rapatsDetail = $rapats->map(function ($rapat) {
            // Gunakan array biasa untuk menghindari error
            $hadirList = [];
            $tidakHadirList = [];

            foreach ($rapat->kesediaanHadir as $item) {
                $userData = [
                    'id_user' => $item->id_user,
                    'nama_direktur' => $item->user->nama_direktur ?? null,
                    'nama_perusahaan' => $item->user->nama_perusahaan ?? null,
                ];

                if ($item->status === 'hadir') {
                    $hadirList[] = $userData;
                } elseif ($item->status === 'tidak_hadir') {
                    $tidakHadirList[] = $userData;
                }
            }

            return [
                'id' => $rapat->id,
                'judul' => $rapat->judul,
                'agenda' => $rapat->agenda,
                'lokasi' => $rapat->lokasi,
                'urgensi' => $rapat->urgensi,
                'status' => $rapat->status,
                'created_by' => $rapat->created_by,
                'creator' => $rapat->creator,

                // Peserta rapat
                'peserta' => $rapat->pesertaRapats->map(function ($peserta) {
                    return [
                        'id_user' => $peserta->id_user,
                        'nama_direktur' => $peserta->user->nama_direktur ?? null,
                        'nama_perusahaan' => $peserta->user->nama_perusahaan ?? null,
                        'jabatan' => $peserta->jabatan,
                    ];
                }),

                // Polling kesediaan hadir
                'polling_kesediaan' => [
                    'jumlah_hadir' => count($hadirList),
                    'jumlah_tidak_hadir' => count($tidakHadirList),
                    'hadir' => $hadirList,
                    'tidak_hadir' => $tidakHadirList,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $rapatsDetail,
        ]);
    }

    public function getRapatMenungguPollingTanggal(Request $request)
    {
        $rapats = Rapat::with([
            'creator:id,name,username',
            'pesertaRapats.user:id,nama_direktur,nama_perusahaan',
            'pollingTanggal.user:id,nama_direktur,nama_perusahaan',
            'pollingTanggalOptions',
        ])
        ->where('status', 'menunggu_polling_tanggal')
        ->get();

        $rapatsDetail = $rapats->map(function ($rapat) {
            // Kelompokkan polling tanggal
            $polling = $rapat->pollingTanggal
                ->groupBy('tanggal_yang_dipilih')
                ->map(function ($items, $tanggal) {
                    return [
                        'tanggal' => $tanggal,
                        'jumlah_pemilih' => $items->count(),
                        'pemilih' => $items->map(function ($item) {
                            return [
                                'id_user' => $item->id_user,
                                'nama_direktur' => $item->user->nama_direktur ?? null,
                                'nama_perusahaan' => $item->user->nama_perusahaan ?? null,
                            ];
                        })->values(),
                    ];
                })->values();

            // Ambil semua opsi tanggal dari tabel tanggal_options
            $tanggalOptions = $rapat->pollingTanggalOptions
                ->map(function ($opt) {
                    return $opt->tanggal; // Format string 'Y-m-d'
                })
                ->values();

            return [
                'id' => $rapat->id,
                'judul' => $rapat->judul,
                'agenda' => $rapat->agenda,
                'lokasi' => $rapat->lokasi,
                'urgensi' => $rapat->urgensi,
                'status' => $rapat->status,
                'created_by' => $rapat->created_by,
                'creator' => $rapat->creator,

                // Daftar peserta
                'peserta' => $rapat->pesertaRapats->map(function ($peserta) {
                    return [
                        'id_user' => $peserta->id_user,
                        'nama_direktur' => $peserta->user->nama_direktur ?? null,
                        'nama_perusahaan' => $peserta->user->nama_perusahaan ?? null,
                        'jabatan' => $peserta->jabatan,
                    ];
                }),

                // Semua opsi tanggal
                'opsi_tanggal' => $tanggalOptions,

                // Hasil polling tanggal
                'polling_tanggal' => $polling,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $rapatsDetail,
        ]);
    }

    public function konfirmasiKehadiran(Request $request, $rapatId)
    {
        $user = auth()->user();

        $data = $request->validate([
            'status'  => 'required|in:hadir,tidak_hadir',
            'alasan' => 'nullable|string|max:255',
        ]);

        // Cek apakah user adalah peserta rapat
        $isPeserta = PesertaRapat::where('rapat_id', $rapatId)
            ->where('id_user', $user->id)
            ->exists();

        if (! $isPeserta) {
            return response()->json([
                'message' => 'Anda tidak terdaftar sebagai peserta rapat ini.'
            ], 403);
        }

        // Cek apakah user sudah pernah mengisi status
        $sudahKonfirmasi = KesediaanHadir::where('id_rapat', $rapatId)
            ->where('id_user', $user->id)
            ->exists();

        if ($sudahKonfirmasi) {
            return response()->json([
                'message' => 'Anda sudah memberikan status kehadiran sebelumnya dan tidak dapat mengubahnya.'
            ], 409);
        }

        // Simpan data kehadiran
        $kehadiran = KesediaanHadir::create([
            'id_user'   => $user->id,
            'id_rapat'  => $rapatId,
            'status'    => $data['status'],
            'alasan'   => $data['alasan'] ?? null,
        ]);

        // Hitung total kehadiran
        $rapat = Rapat::with(['pesertaRapats.user'])->findOrFail($rapatId);
        $totalPeserta = $rapat->pesertaRapats->count();
        $jumlahHadir = KesediaanHadir::where('id_rapat', $rapatId)
            ->where('status', 'hadir')->count();
        $jumlahTidakHadir = KesediaanHadir::where('id_rapat', $rapatId)
            ->where('status', 'tidak_hadir')->count();

        $persentaseHadir = $totalPeserta > 0 ? $jumlahHadir / $totalPeserta : 0;
        $persentaseTidakHadir = $totalPeserta > 0 ? $jumlahTidakHadir / $totalPeserta : 0;

        // Jika >= 50% hadir dan status belum finalisasi, finalisasi rapat + buat PDF + kirim email
        if ($persentaseHadir >= 0.5 && $rapat->status !== 'finalisasi') {
            if (! $rapat->file_undangan_pdf || ! \Storage::disk('public')->exists($rapat->file_undangan_pdf)) {
                // Generate file PDF undangan
                $pdf = \PDF::loadView('pdf.undangan', ['rapat' => $rapat]);
                $filename = "undangan/rapat-{$rapat->id}.pdf";
                \Storage::disk('public')->put($filename, $pdf->output());

                // Update status, file, dan waktu pengiriman
                $rapat->update([
                    'status' => 'finalisasi',
                    'file_undangan_pdf' => $filename,
                    'pengiriman_dijadwalkan_pada' => now(), // <<== PENGISIAN WAKTU OTOMATIS
                ]);

                // Kirim email ke seluruh peserta
                foreach ($rapat->pesertaRapats as $peserta) {
                    $targetUser = $peserta->user;
                    if ($targetUser && $targetUser->email) {
                        \Mail::to($targetUser->email)->send(new \App\Mail\UndanganRapatMail($rapat));
                    }
                }
            }
        }
        // Jika >= 50% tidak hadir, ubah ke menunggu polling
        elseif ($persentaseTidakHadir >= 0.5 && $rapat->status !== 'menunggu_polling_tanggal') {
            $rapat->update([
                'status' => 'menunggu_polling_tanggal'
            ]);
        }

        return response()->json([
            'message' => 'Status kehadiran berhasil disimpan.',
            'kehadiran' => $kehadiran
        ]);
    }

    public function pollingTanggal(Request $request, $rapatId)
    {
        $user = auth()->user();

        $data = $request->validate([
            'tanggal_yang_dipilih' => 'required|date',
        ]);

        $rapat = Rapat::with('pesertaRapats.user')->findOrFail($rapatId);

        // ❌ Tidak boleh polling kalau bukan tahap polling
        if ($rapat->status !== 'menunggu_polling_tanggal') {
            return response()->json([
                'message' => 'Polling tanggal hanya diizinkan jika status rapat adalah menunggu_polling_tanggal.'
            ], 403);
        }

        // ❌ Tidak boleh polling jika waktu sudah berakhir
        if ($rapat->tanggal_polling_berakhir && now()->gt($rapat->tanggal_polling_berakhir)) {
            return response()->json([
                'message' => 'Waktu untuk mengisi polling tanggal sudah berakhir.'
            ], 403);
        }

        // ❌ Hanya peserta rapat yang boleh polling
        $isPeserta = $rapat->pesertaRapats->contains(fn($peserta) => $peserta->id_user === $user->id);
        if (! $isPeserta) {
            return response()->json([
                'message' => 'Anda tidak terdaftar sebagai peserta rapat ini.'
            ], 403);
        }

        // ❌ Cek jika sudah pernah polling
        $sudahPolling = PollingTanggal::where('id_user', $user->id)
            ->where('id_rapat', $rapatId)
            ->exists();
        if ($sudahPolling) {
            return response()->json([
                'message' => 'Anda sudah melakukan polling tanggal untuk rapat ini.'
            ], 409);
        }

        // ❌ Cek tanggal valid
        $validOptions = PollingTanggalOption::where('id_rapat', $rapatId)->pluck('tanggal')->toArray();
        if (!in_array($data['tanggal_yang_dipilih'], $validOptions)) {
            return response()->json([
                'message' => 'Tanggal yang Anda pilih tidak termasuk dalam opsi yang tersedia.'
            ], 422);
        }

        // ✅ Simpan polling
        $polling = PollingTanggal::create([
            'id_user' => $user->id,
            'id_rapat' => $rapatId,
            'tanggal_yang_dipilih' => $data['tanggal_yang_dipilih'],
        ]);

        // ===============================
        // ✅ CEK apakah sudah kuorum
        // ===============================
        $totalPeserta = $rapat->pesertaRapats->count();
        $hasilPolling = PollingTanggal::where('id_rapat', $rapatId)
            ->selectRaw('tanggal_yang_dipilih, COUNT(*) as total')
            ->groupBy('tanggal_yang_dipilih')
            ->orderByDesc('total')
            ->get();

        $tanggalTerpilih = null;
        foreach ($hasilPolling as $item) {
            if ($item->total >= ceil($totalPeserta / 2)) {
                $tanggalTerpilih = $item->tanggal_yang_dipilih;
                break;
            }
        }

        // ✅ Jika sudah kuorum dan belum finalisasi, update rapat dan kirim undangan
        if ($tanggalTerpilih && $rapat->status !== 'finalisasi') {
            // Hindari pengiriman ulang jika sudah pernah dibuat dan dikirim
            if (! $rapat->file_undangan_pdf || ! \Storage::disk('public')->exists($rapat->file_undangan_pdf)) {

                // ✅ Set tanggal terpilih, status, dan waktu pengiriman sebelum membuat PDF
                $rapat->tanggal_terpilih = $tanggalTerpilih;
                $rapat->status = 'finalisasi';
                $rapat->pengiriman_dijadwalkan_pada = now();
                $rapat->save();

                // ✅ Refresh agar data baru terbaca saat buat PDF
                $rapat->refresh();

                // ✅ Generate file PDF
                $pdf = \PDF::loadView('pdf.undangan', ['rapat' => $rapat]);
                $filename = "undangan/rapat-{$rapat->id}.pdf";
                \Storage::disk('public')->put($filename, $pdf->output());

                // ✅ Simpan path file PDF
                $rapat->file_undangan_pdf = $filename;
                $rapat->save();

                // ✅ Kirim undangan ke semua peserta
                foreach ($rapat->pesertaRapats as $peserta) {
                    $targetUser = $peserta->user;
                    if ($targetUser && $targetUser->email) {
                        \Mail::to($targetUser->email)->send(new \App\Mail\UndanganRapatMail($rapat));
                    }
                }

            } else {
                // Kalau file sudah ada tapi status belum diupdate
                $rapat->update([
                    'tanggal_terpilih' => $tanggalTerpilih,
                    'status' => 'finalisasi',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Polling tanggal berhasil disimpan.',
            'polling' => $polling,
            'tanggal_terpilih' => $tanggalTerpilih
        ]);
    }

    public function getPollingSummary($rapatId)
    {
        $user = auth()->user();

        // Cek apakah user adalah peserta rapat
        $isPeserta = PesertaRapat::where('rapat_id', $rapatId)
            ->where('user_id', $user->id)
            ->exists();

        if (! $isPeserta) {
            return response()->json([
                'message' => 'Anda tidak terdaftar sebagai peserta rapat ini.'
            ], 403);
        }

        // Ambil rekap polling
        $rekapPolling = PollingTanggal::where('rapat_id', $rapatId)
            ->selectRaw('tanggal, COUNT(*) as jumlah_suara')
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        // Optional: Detail siapa saja yang memilih per tanggal
        $detailPolling = PollingTanggal::where('rapat_id', $rapatId)
            ->with('user:id,name,email')
            ->orderBy('tanggal')
            ->get()
            ->groupBy('tanggal')
            ->map(function ($group) {
                return $group->map(function ($item) {
                    return [
                        'user_id' => $item->user->id,
                        'name' => $item->user->name,
                        'email' => $item->user->email,
                    ];
                });
            });

        return response()->json([
            'success' => true,
            'rekapitulasi' => $rekapPolling,
            'detail_per_tanggal' => $detailPolling,
        ]);
    }

    public function getPollingOptions($rapatId)
    {
        $rapat = Rapat::findOrFail($rapatId);

        $options = PollingTanggalOption::where('id_rapat', $rapatId)->pluck('tanggal');

        return response()->json([
            'id_rapat' => $rapatId,
            'judul_rapat' => $rapat->judul,
            'opsi_tanggal' => $options,
        ]);
    }

    public function verifikasiNotulensi(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user->is_pengurus || strtolower($user->jabatan) !== 'ketua_sekretaris') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $data = $request->validate([
            'status' => 'required|in:disetujui,ditolak',
            'catatan_penolakan' => 'nullable|string|required_if:status,ditolak|min:5',
        ]);

        $notulensi = Notulensi::find($id);
        if (!$notulensi) {
            return response()->json(['success' => false, 'message' => 'Notulensi tidak ditemukan.'], 404);
        }

        if ($notulensi->status !== 'menunggu_approval') {
            return response()->json(['success' => false, 'message' => 'Notulensi tidak dalam status menunggu approval.'], 400);
        }

        $notulensi->update([
            'status' => $data['status'],
            'approved_at' => $data['status'] === 'disetujui' ? now() : null,
            'catatan_penolakan' => $data['status'] === 'ditolak' ? $data['catatan_penolakan'] : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notulensi telah ' . ($data['status'] === 'disetujui' ? 'disetujui' : 'ditolak.'),
        ]);
    }

    public function absen(Request $request, $rapatId)
    {
        $user = auth()->user();

        $request->validate([
            'signature' => 'nullable|file|mimes:png,jpg,jpeg|max:2048', // ✅ ubah jadi file
        ]);

        $rapat = Rapat::findOrFail($rapatId);

        if ($rapat->status !== 'selesai') {
            return response()->json([
                'message' => 'Absensi hanya dapat dilakukan setelah rapat berstatus selesai.'
            ], 403);
        }

        $isPeserta = PesertaRapat::where('rapat_id', $rapatId)
            ->where('id_user', $user->id)
            ->exists();

        if (!$isPeserta) {
            return response()->json([
                'message' => 'Anda bukan peserta rapat ini.'
            ], 403);
        }

        $sudahAbsen = Absensi::where('id_rapat', $rapatId)
            ->where('id_user', $user->id)
            ->exists();

        if ($sudahAbsen) {
            return response()->json([
                'message' => 'Anda sudah melakukan absensi untuk rapat ini.'
            ], 409);
        }

        $signaturePath = null;

        // ✅ Jika file dikirim, simpan
        if ($request->hasFile('signature')) {
            $signaturePath = $request->file('signature')->store('signatures', 'public');
        }

        $absensi = Absensi::create([
            'id_rapat' => $rapatId,
            'id_user' => $user->id,
            'waktu_absen' => now(),
            'signature_path' => $signaturePath,
        ]);

        return response()->json([
            'message' => 'Absensi berhasil dicatat.',
            'data' => $absensi
        ]);
    }

    public function getFinalisasi()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User belum login atau token tidak valid.',
            ], 401);
        }

        $rapats = Rapat::with([
            'pesertaRapats.user:id,nama_direktur,nama_perusahaan',
            'creator:id,name,username',
            'absensis.user:id,nama_direktur,nama_perusahaan',
        ])
        ->whereHas('pesertaRapats', function ($query) use ($user) {
            $query->where('id_user', $user->id);
        })
        ->get();

        $rapatsDetail = $rapats->map(function ($rapat) use ($user) {
            $downloadUrl = $rapat->file_undangan_pdf
                ? Storage::disk('public')->url($rapat->file_undangan_pdf)
                : null;

            $tanggal = optional($rapat->tanggal_terpilih)->format('Y-m-d');
            $jam = $rapat->jam ? Carbon::parse($rapat->jam)->format('H:i') : null;
            $waktuRapat = ($tanggal && $jam)
                ? Carbon::createFromFormat('Y-m-d H:i', "$tanggal $jam")->format('Y-m-d H:i:s')
                : null;

            $isRapatSelesai = $waktuRapat ? Carbon::parse($waktuRapat)->lt(now()) : false;
            $catatan = $isRapatSelesai
                ? 'Rapat telah selesai karena waktunya sudah lewat.'
                : ($rapat->status === 'finalisasi'
                    ? 'Undangan langsung dikirim.'
                    : 'Menunggu pengiriman otomatis.');

            // Cek apakah user sudah absen
            $sudahAbsen = $rapat->absensis->contains('id_user', $user->id);

            return [
                'id' => $rapat->id,
                'judul' => $rapat->judul,
                'agenda' => $rapat->agenda,
                'lokasi' => $rapat->lokasi,
                'urgensi' => $rapat->urgensi,
                'tanggal_terpilih' => $tanggal,
                'jam' => $jam,
                'waktu_rapat' => $waktuRapat,
                'status' => $rapat->status,
                'created_by' => $rapat->created_by,
                'creator' => $rapat->creator,
                'nomor' => $rapat->nomor,
                'lampiran' => $rapat->lampiran,
                'hal' => $rapat->hal,
                'topik' => $rapat->topik,
                'header_image' => $rapat->header_image,
                'tanda_tangan_image' => $rapat->tanda_tangan_image,
                'pengiriman_dijadwalkan_pada' => optional($rapat->pengiriman_dijadwalkan_pada)->format('Y-m-d H:i:s'),
                'file_undangan_pdf' => $rapat->file_undangan_pdf,
                'download_url' => $downloadUrl,
                'catatan' => $catatan,
                'sudah_absen' => $sudahAbsen,

                // Daftar peserta
                'peserta' => $rapat->pesertaRapats->map(function ($peserta) {
                    return [
                        'id_user' => $peserta->id_user,
                        'nama_direktur' => $peserta->user->nama_direktur ?? null,
                        'nama_perusahaan' => $peserta->user->nama_perusahaan ?? null,
                        'jabatan' => $peserta->jabatan,
                    ];
                }),

                // Daftar absensi
                'absensi' => $rapat->absensis->map(function ($absen) {
                    return [
                        'id_user' => $absen->id_user,
                        'nama_direktur' => $absen->user->nama_direktur ?? null,
                        'nama_perusahaan' => $absen->user->nama_perusahaan ?? null,
                        'waktu_absen' => optional($absen->waktu_absen)->format('Y-m-d H:i:s'),
                        'signature_path' => $absen->signature_path,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $rapatsDetail,
        ]);
    }

// -------------{}{}{}--------------//

    // ADMIN FUNCTION
    public function store(Request $request)
    {
        if (!auth('admin')->check()) {
            return response()->json(['message' => 'Akses ditolak. Hanya admin yang dapat membuat rapat.'], 403);
        }

        $data = $request->validate([
            'judul' => 'required|string|max:255',
            'agenda' => 'nullable|string',
            'lokasi' => 'nullable|string',
            'urgensi' => 'required|in:rendah,sedang,tinggi,kritis',
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
            $rapatWaktu = $tanggalTerpilih->copy();

            if (!empty($data['jam'])) {
                [$jam, $menit] = explode(':', $data['jam']);
                $rapatWaktu->setTime($jam, $menit);
            }

            // Cek bentrok jadwal
            $bentrok = Rapat::whereDate('tanggal_terpilih', $tanggalTerpilih)
                ->where('jam', $data['jam'] ?? null)->exists();
            if ($bentrok) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sudah ada rapat lain pada waktu tersebut.'
                ], 409);
            }

            $status = 'menunggu_approval_ketua';

            $headerPath = $request->hasFile('header_image') ? $request->file('header_image')->store('undangan/header', 'public') : null;
            $footerPath = $request->hasFile('tanda_tangan_image') ? $request->file('tanda_tangan_image')->store('undangan/footer', 'public') : null;

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
            ]);

            foreach ($data['peserta'] as $peserta) {
                $user = User::find($peserta['user_id']);
                if ($user) {
                    $rapat->pesertaRapats()->create([
                        'id_user' => $user->id,
                        'jabatan' => $user->jabatan ?? null,
                        'is_pengurus' => $user->is_pengurus ?? false,
                    ]);
                }
            }

            $ketua = User::where('jabatan', 'ketua')->first();
            if (!$ketua) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Ketua tidak ditemukan. Tidak dapat melanjutkan proses approval.',
                ], 404);
            }

            ApprovalKetua::create([
                'id_rapat' => $rapat->id,
                'id_ketua' => $ketua->id,
                'status' => 'menunggu',
                'catatan' => null,
            ]);

            // ❌ JANGAN generate PDF di sini

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rapat berhasil dibuat dan menunggu persetujuan ketua.',
                'rapat' => $rapat,
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

    public function storeOptions(Request $request, $rapatId)
    {
        $request->validate([
            'opsi_tanggal' => 'required|array|min:1',
            'opsi_tanggal.*' => 'required|date|after_or_equal:today',
        ]);

        $rapat = Rapat::findOrFail($rapatId);

        // Pastikan statusnya sesuai
        if ($rapat->status !== 'menunggu_polling_tanggal') {
            return response()->json([
                'success' => false,
                'message' => 'Rapat ini belum memasuki tahap polling tanggal.',
            ], 400);
        }

        // Jika belum pernah diset, isi tanggal_polling_berakhir (sekali saja)
        if (!$rapat->tanggal_polling_berakhir) {
            $rapat->tanggal_polling_berakhir = now()->addDays(8);
            $rapat->save();
        }

        // Cek apakah waktu polling sudah berakhir
        if (now()->greaterThan($rapat->tanggal_polling_berakhir)) {
            return response()->json([
                'success' => false,
                'message' => 'Batas waktu pengisian opsi polling sudah berakhir.',
            ], 403);
        }

        // Simpan opsi tanggal polling (hindari duplikat)
        $insertedDates = [];
        foreach ($request->opsi_tanggal as $tanggal) {
            $existing = PollingTanggalOption::where('id_rapat', $rapatId)
                ->whereDate('tanggal', $tanggal)
                ->exists();

            if (!$existing) {
                PollingTanggalOption::create([
                    'id_rapat' => $rapatId,
                    'tanggal' => $tanggal,
                ]);
                $insertedDates[] = $tanggal;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Opsi tanggal polling berhasil disimpan.',
            'status_rapat' => $rapat->status,
            'batas_pengisian_polling' => $rapat->tanggal_polling_berakhir->format('Y-m-d'),
            'opsi_tanggal_tersimpan' => $insertedDates,
            'opsi_tanggal_seluruhnya' => PollingTanggalOption::where('id_rapat', $rapatId)->pluck('tanggal'),
        ]);
    }

    public function createNotulenByAdmin(Request $request, $rapatId)
    {
        $admin = auth('admin')->user();
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya admin yang dapat membuat notulensi ini.'
            ], 403);
        }

        $data = $request->validate([
            'isi' => 'required|string',
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:pdf,doc,docx,ppt,pptx,jpg,jpeg,png',
        ]);

        $rapat = Rapat::find($rapatId);
        if (!$rapat) {
            return response()->json([
                'success' => false,
                'message' => 'Rapat tidak ditemukan.'
            ], 404);
        }

        if ($rapat->status !== 'selesai') {
            return response()->json([
                'success' => false,
                'message' => 'Rapat belum selesai.'
            ], 400);
        }

        if ($rapat->notulensi) {
            return response()->json([
                'success' => false,
                'message' => 'Notulensi sudah dibuat.'
            ], 400);
        }

        // Karena admin yang membuat, kolom 'dibuat_oleh' tetap diisi dengan default valid user ID,
        // atau bisa nullable jika kamu ingin ubah kolom 'dibuat_oleh' jadi nullable

        $notulensi = Notulensi::create([
            'id_rapat' => $rapat->id,
            'dibuat_oleh' => null, // jika kamu ubah kolom ini jadi nullable
            'dibuat_oleh_admin' => $admin->id,
            'isi' => $data['isi'],
            'status' => 'menunggu_approval',
            'approved_at' => null,
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $filename = 'notulensi/' . Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('public', $filename);

                $notulensi->files()->create([
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Notulensi berhasil dibuat dan menunggu approval ketua sekretaris.',
            'notulensi' => $notulensi->load('files'),
        ], 201);
    }

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

    public function update(Request $request, $id)
    {
        $rapat = Rapat::findOrFail($id);

        // Cegah update jika rapat tidak dalam status "dijadwalkan_ulang"
        if ($rapat->status !== 'dijadwalkan_ulang') {
            return response()->json([
                'message' => 'Rapat hanya bisa direvisi jika statusnya "dijadwalkan_ulang".'
            ], 403);
        }

        // Validasi input
        $data = $request->validate([
            'judul' => 'sometimes|required|string|max:255',
            'agenda' => 'nullable|string',
            'lokasi' => 'nullable|string',
            'urgensi' => 'nullable|in:rendah,sedang,tinggi',
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

        // Ambil tanggal dan jam baru, atau gunakan nilai lama jika tidak diubah
        $tanggalBaru = isset($data['tanggal_terpilih']) 
            ? Carbon::parse($data['tanggal_terpilih']) 
            : Carbon::parse($rapat->tanggal_terpilih);

        $jamBaru = $data['jam'] ?? $rapat->jam;

        // Cek tabrakan jadwal
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

        // Jadwalkan ulang pengiriman berdasarkan urgensi
        $urgensi = $data['urgensi'] ?? $rapat->urgensi;
        switch ($urgensi) {
            case 'rendah':
                $pengirimanDijadwalkan = $tanggalBaru->copy()->subDays(4);
                break;
            case 'sedang':
                $pengirimanDijadwalkan = $tanggalBaru->copy()->subDays(3);
                break;
            case 'tinggi':
                $pengirimanDijadwalkan = $tanggalBaru->copy()->subDays(2);
                break;
            default:
                $pengirimanDijadwalkan = $rapat->pengiriman_dijadwalkan_pada;
                break;
        }

        // Ubah status dan atur ulang pengiriman
        $data['pengiriman_dijadwalkan_pada'] = $pengirimanDijadwalkan;
        $data['status'] = 'menunggu_approval_ketua';

        // Reset approval ketua
        ApprovalKetua::where('id_rapat', $rapat->id)->delete();

        // Update data rapat
        $rapat->update($data);

        // Update peserta jika dikirim
        if (isset($data['peserta'])) {
            $rapat->pesertaRapats()->delete();

            foreach ($data['peserta'] as $peserta) {
                $user = User::find($peserta['user_id']);
                if ($user) {
                    $rapat->pesertaRapats()->create([
                        'id_user' => $user->id, // gunakan `user_id` jika itu nama kolom di DB
                        'jabatan' => $user->jabatan ?? null,
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Rapat berhasil direvisi dan dikirim ulang untuk approval Ketua.',
            'rapat' => $rapat->load('pesertaRapats.user')
        ]);
    }

    public function destroy($id)
    {
        $rapat = Rapat::findOrFail($id);

        // Tidak bisa menghapus rapat yang sudah difinalisasi
        if ($rapat->status === 'selesai') {
            return response()->json(['message' => 'Rapat sudah Selesai dan tidak dapat dihapus.'], 403);
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

    public function index()
    {
        $rapats = Rapat::with([
            'pesertaRapats.user:id,nama_direktur,nama_perusahaan',
            'creator:id,name,username',
            'kesediaanHadir:id,id_rapat,id_user,status',
            'absensis.user:id,nama_direktur,nama_perusahaan',
            'pollingTanggal.user:id,nama_direktur,nama_perusahaan',
        ])->get();

        $rapatsDetail = $rapats->map(function ($rapat) {
            $downloadUrl = $rapat->file_undangan_pdf
                ? Storage::disk('public')->url($rapat->file_undangan_pdf)
                : null;

            $tanggal = optional($rapat->tanggal_terpilih)->format('Y-m-d');
            $jam = $rapat->jam ? Carbon::parse($rapat->jam)->format('H:i') : null;
            $waktuRapat = ($tanggal && $jam)
                ? Carbon::createFromFormat('Y-m-d H:i', "$tanggal $jam")->format('Y-m-d H:i:s')
                : null;

            $isRapatSelesai = $waktuRapat ? Carbon::parse($waktuRapat)->lt(now()) : false;
            $catatan = $isRapatSelesai
                ? 'Rapat telah selesai karena waktunya sudah lewat.'
                : ($rapat->status === 'finalisasi'
                    ? 'Undangan langsung dikirim.'
                    : 'Menunggu pengiriman otomatis.');

            $totalPeserta = $rapat->pesertaRapats->count();
            $hadirIds = $rapat->kesediaanHadir->where('status', 'hadir')->pluck('id_user')->toArray();
            $tidakHadirIds = $rapat->kesediaanHadir->where('status', 'tidak_hadir')->pluck('id_user')->toArray();

            $jumlahHadir = count($hadirIds);
            $jumlahTidakHadir = count($tidakHadirIds);

            $persentaseHadir = $totalPeserta > 0 ? round(($jumlahHadir / $totalPeserta) * 100, 2) : 0;
            $persentaseTidakHadir = $totalPeserta > 0 ? round(($jumlahTidakHadir / $totalPeserta) * 100, 2) : 0;

            $absenIds = $rapat->absensis->pluck('id_user')->toArray();
            $hadirTapiTidakAbsen = array_diff($hadirIds, $absenIds);

            $pesertaTidakHadirHariH = $rapat->pesertaRapats
                ->whereIn('id_user', $hadirTapiTidakAbsen)
                ->map(function ($peserta) {
                    return [
                        'id_user' => $peserta->id_user,
                        'nama_direktur' => $peserta->user->nama_direktur ?? null,
                        'nama_perusahaan' => $peserta->user->nama_perusahaan ?? null,
                        'jabatan' => $peserta->jabatan,
                    ];
                })->values();

            // Ambil semua polling tanggal + info absensi
            $pollingOptions = $rapat->pollingTanggal
                ->groupBy('tanggal_yang_dipilih')
                ->map(function ($group, $tanggal) use ($rapat) {
                    return [
                        'tanggal' => $tanggal,
                        'jumlah_pemilih' => $group->count(),
                        'pemilih' => $group->map(function ($item) use ($rapat) {
                            $absen = $rapat->absensis->firstWhere('id_user', $item->id_user);
                            return [
                                'id_user' => $item->id_user,
                                'nama_direktur' => $item->user->nama_direktur ?? null,
                                'nama_perusahaan' => $item->user->nama_perusahaan ?? null,
                                'waktu_absen' => $absen ? optional($absen->waktu_absen)->format('Y-m-d H:i:s') : null,
                                'signature_path' => $absen ? $absen->signature_path : null,
                            ];
                        })->values(),
                    ];
                })->values();

            return [
                'id' => $rapat->id,
                'judul' => $rapat->judul,
                'agenda' => $rapat->agenda,
                'lokasi' => $rapat->lokasi,
                'urgensi' => $rapat->urgensi,
                'tanggal_terpilih' => $tanggal,
                'jam' => $jam,
                'waktu_rapat' => $waktuRapat,
                'status' => $rapat->status,
                'created_by' => $rapat->created_by,
                'creator' => $rapat->creator,
                'nomor' => $rapat->nomor,
                'lampiran' => $rapat->lampiran,
                'hal' => $rapat->hal,
                'topik' => $rapat->topik,
                'header_image' => $rapat->header_image,
                'tanda_tangan_image' => $rapat->tanda_tangan_image,
                'pengiriman_dijadwalkan_pada' => optional($rapat->pengiriman_dijadwalkan_pada)->format('Y-m-d H:i:s'),
                'file_undangan_pdf' => $rapat->file_undangan_pdf,
                'download_url' => $downloadUrl,
                'catatan' => $catatan,

                // Peserta
                'peserta' => $rapat->pesertaRapats->map(function ($peserta) use ($hadirIds, $tidakHadirIds) {
                    $status = null;
                    if (in_array($peserta->id_user, $hadirIds)) $status = 'hadir';
                    elseif (in_array($peserta->id_user, $tidakHadirIds)) $status = 'tidak_hadir';

                    return [
                        'id_user' => $peserta->id_user,
                        'nama_direktur' => $peserta->user->nama_direktur ?? null,
                        'nama_perusahaan' => $peserta->user->nama_perusahaan ?? null,
                        'jabatan' => $peserta->jabatan,
                        'status_kehadiran' => $status,
                    ];
                }),

                // Statistik kehadiran
                'jumlah_peserta' => $totalPeserta,
                'jumlah_hadir' => $jumlahHadir,
                'jumlah_tidak_hadir' => $jumlahTidakHadir,
                'persentase_hadir' => $persentaseHadir,
                'persentase_tidak_hadir' => $persentaseTidakHadir,

                // Absensi
                'absensi' => $rapat->absensis->map(function ($absen) {
                    return [
                        'id_user' => $absen->id_user,
                        'nama_direktur' => $absen->user->nama_direktur ?? null,
                        'nama_perusahaan' => $absen->user->nama_perusahaan ?? null,
                        'waktu_absen' => optional($absen->waktu_absen)->format('Y-m-d H:i:s'),
                        'signature_path' => $absen->signature_path,
                    ];
                }),

                // Peserta yang menyatakan hadir tapi tidak absen
                'peserta_hadir_tapi_tidak_absen' => $pesertaTidakHadirHariH,

                // Polling tanggal
                'polling_tanggal' => $pollingOptions,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $rapatsDetail,
        ]);
    }

}
