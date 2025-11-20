<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    public function index()
    {
        try {
            $agendas = Agenda::orderBy('date', 'asc')->get();

            return response()->json([
                'status'  => true,
                'message' => 'Daftar agenda berhasil diambil.',
                'data'    => $agendas
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Gagal mengambil daftar agenda.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'date'    => 'required|date',
                'title'   => 'required|string|max:255',
                'caption' => 'required|string',
            ]);

            $agenda = Agenda::create($validated);

            return response()->json([
                'status'  => true,
                'message' => 'Agenda berhasil dibuat.',
                'data'    => $agenda
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Validation error.',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Gagal membuat agenda.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $agenda = Agenda::findOrFail($id);

            $validated = $request->validate([
                'date'    => 'sometimes|date',
                'title'   => 'sometimes|string|max:255',
                'caption' => 'sometimes|string',
            ]);

            $agenda->update($validated);

            return response()->json([
                'status'  => true,
                'message' => 'Agenda berhasil diperbarui.',
                'data'    => $agenda
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Validation error.',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Agenda tidak ditemukan.'
            ], 404);

        } catch (\Exception $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Gagal memperbarui agenda.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $agenda = Agenda::findOrFail($id);
            $agenda->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Agenda berhasil dihapus.'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Agenda tidak ditemukan.'
            ], 404);

        } catch (\Exception $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Gagal menghapus agenda.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
