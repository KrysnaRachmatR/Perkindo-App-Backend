<?php
namespace App\Http\Controllers;

use App\Models\MeetingNote;
use Illuminate\Http\Request;

class MeetingNoteController extends Controller
{
    // Notulen: Simpan notulen rapat
    public function store(Request $request)
    {
        $request->validate([
            'meeting_id' => 'required|exists:meetings,id',
            'notulen_id' => 'required|exists:notulens,id',
            'summary' => 'required|string',
            'decisions' => 'nullable|string'
        ]);

        $note = MeetingNote::create($request->only([
            'meeting_id',
            'notulen_id',
            'summary',
            'decisions'
        ]));

        return response()->json(['message' => 'Notulen disimpan', 'data' => $note]);
    }
}
