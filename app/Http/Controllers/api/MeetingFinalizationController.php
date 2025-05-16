<?php
namespace App\Http\Controllers;

use App\Models\Meeting;
use Illuminate\Http\Request;

class MeetingFinalizationController extends Controller
{
    // Admin: Finalisasi tanggal manual (hasil polling/SPK)
    public function finalize(Request $request, $id)
    {
        $request->validate([
            'final_date' => 'required|date',
        ]);

        $meeting = Meeting::findOrFail($id);
        $meeting->final_date = $request->final_date;
        $meeting->status = 'final';
        $meeting->save();

        return response()->json(['message' => 'Tanggal rapat difinalisasi', 'data' => $meeting]);
    }
}
