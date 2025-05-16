<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\PollOption;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    // Admin: Buat rapat + polling tanggal
    public function store(Request $request)
    {
        $request->validate([
            'admin_id' => 'required|exists:admins,id',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'poll_dates' => 'required|array|min:1',
            'poll_dates.*' => 'date'
        ]);

        $meeting = Meeting::create([
            'admin_id' => $request->admin_id,
            'title' => $request->title,
            'description' => $request->description,
            'status' => 'polling'
        ]);

        foreach ($request->poll_dates as $date) {
            PollOption::create([
                'meeting_id' => $meeting->id,
                'option_date' => $date
            ]);
        }

        return response()->json(['message' => 'Meeting created', 'meeting' => $meeting], 201);
    }

    // Admin: Lihat detail rapat + polling
    public function show($id)
    {
        $meeting = Meeting::with(['pollOptions.responses', 'users'])->findOrFail($id);
        return response()->json($meeting);
    }
}
