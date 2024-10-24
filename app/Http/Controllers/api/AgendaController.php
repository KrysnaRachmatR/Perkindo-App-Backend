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
      return response()->json(['message' => 'Agenda not found'], 404);
    }

    $request->validate([
      'date' => 'required|date',
      'title' => 'required|string|max:255',
      'caption' => 'required|string',
    ]);

    $agenda->update($request->only(['date', 'title', 'caption']));

    return response()->json(['message' => 'Agenda updated successfully', 'agenda' => $agenda]);
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
