<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
  // Menampilkan semua agenda
  public function index()
  {
    $agendas = Agenda::all();
    return response()->json($agendas);
  }

  // Menambahkan agenda baru
  public function store(Request $request)
  {
    $request->validate([
      'date' => 'required|date',
      'title' => 'required|string|max:255',
      'caption' => 'required|string',
    ]);

    $agenda = Agenda::create([
      'date' => $request->date,
      'title' => $request->title,
      'caption' => $request->caption,
    ]);

    return response()->json(['message' => 'Agenda created successfully', 'agenda' => $agenda], 201);
  }

  // Menampilkan agenda berdasarkan ID
  public function show($id)
  {
    $agenda = Agenda::find($id);

    if (!$agenda) {
      return response()->json(['message' => 'Agenda not found'], 404);
    }

    return response()->json($agenda);
  }

  // Memperbarui agenda
  public function update(Request $request, $id)
  {
    $agenda = Agenda::find($id);

    if (!$agenda) {
      return response()->json([
        'success' => false,
        'message' => 'Agenda not found'
      ], 404);
    }

    $validated = $request->validate([
      'date' => 'sometimes|date',
      'title' => 'sometimes|string|max:255',
      'caption' => 'sometimes|string',
    ]);

    if ($agenda->update($validated)) {
      return response()->json([
        'success' => true,
        'message' => 'Agenda updated successfully',
        'data' => $agenda
      ]);
    } else {
      return response()->json([
        'success' => false,
        'message' => 'Failed to update agenda'
      ], 500);
    }
  }



  // Menghapus agenda
  public function destroy($id)
  {
    $agenda = Agenda::find($id);

    if (!$agenda) {
      return response()->json(['message' => 'Agenda not found'], 404);
    }

    $agenda->delete();

    return response()->json(['message' => 'Agenda deleted successfully']);
  }
}
