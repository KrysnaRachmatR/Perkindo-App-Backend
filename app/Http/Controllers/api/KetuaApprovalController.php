<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ApprovalKetua;
use App\Models\LogNotifikasi;
use App\Models\Rapat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KetuaApprovalController extends Controller
{
    public function decide(Request $request, int $rapatId)
    {
        $user = auth()->user();

        if (!$user || !$user->is_pengurus || strtolower($user->jabatan) !== 'ketua') {
            return response()->json([
                'message' => 'Akses ditolak. Hanya Ketua yang dapat menyetujui atau menolak rapat.'
            ], 403);
        }

        $data = $request->validate([
            'status'  => 'required|in:approved,rejected',
            'catatan' => 'nullable|string|max:2000',
        ]);

        $rapat = Rapat::with('pesertaRapats.user')->find($rapatId);
        if (!$rapat) {
            return response()->json(['message' => 'Rapat tidak ditemukan.'], 404);
        }

        if ($rapat->status !== 'menunggu_approval_ketua') {
            return response()->json([
                'message' => 'Rapat ini tidak berada pada status menunggu_approval_ketua.'
            ], 422);
        }

        $approval = ApprovalKetua::where('id_rapat', $rapat->id)
            ->where('id_ketua', $user->id)
            ->first();

        if ($approval && in_array($approval->status, ['approved', 'rejected'])) {
            return response()->json([
                'message' => 'Anda sudah memberikan keputusan pada rapat ini sebelumnya.'
            ], 409);
        }

        try {
            DB::beginTransaction();

            if ($approval) {
                $approval->update([
                    'status'      => $data['status'],
                    'catatan'     => $data['catatan'] ?? null,
                    'approved_at' => now(),
                ]);
            } else {
                $approval = ApprovalKetua::create([
                    'id_rapat'    => $rapat->id,
                    'id_ketua'    => $user->id,
                    'status'      => $data['status'],
                    'catatan'     => $data['catatan'] ?? null,
                    'approved_at' => now(),
                ]);
            }

            // Update status rapat dan tanggal polling berakhir jika rejected
            if ($data['status'] === 'approved') {
                $rapat->status = 'menunggu_polling';
                $rapat->tanggal_polling_berakhir = null; // pastikan tidak aktif
            } else {
                $rapat->status = 'dijadwalkan_ulang';
            }

            $rapat->save();

            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => 'Keputusan berhasil disimpan.',
                'rapat'    => $rapat,
                'approval' => $approval,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan keputusan.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}