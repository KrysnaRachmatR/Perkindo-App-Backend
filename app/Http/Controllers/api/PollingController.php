<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PollOption;
use App\Models\PollResponse;
use Illuminate\Http\Request;

class PollingController extends Controller
{
    // User: Kirim respon polling
    public function respond(Request $request)
    {
        $request->validate([
            'poll_option_id' => 'required|exists:poll_options,id',
            'user_id' => 'required|exists:users,id',
            'response' => 'required|in:bisa,tidak',
        ]);

        // Cek apakah user sudah mengisi polling ini
        $exists = PollResponse::where('poll_option_id', $request->poll_option_id)
            ->where('user_id', $request->user_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Sudah voting untuk tanggal ini'], 409);
        }

        $response = PollResponse::create($request->only('poll_option_id', 'user_id', 'response'));

        return response()->json(['message' => 'Voting berhasil', 'data' => $response]);
    }
}
