<?php



namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InstagramToken;
use App\Services\InstagramService;

class InstagramController extends Controller
{

  protected $instagramService;

  public function __construct(InstagramService $instagramService)
  {
    $this->instagramService = $instagramService;
  }
  public function getMedia()
  {
    // Ambil token akses dari database
    $instagramToken = InstagramToken::latest()->first();

    if (!$instagramToken) {
      return response()->json(['error' => 'No access token found'], 404);
    }

    $accessToken = $instagramToken->access_token;

    // URL untuk mendapatkan media
    $url = "https://graph.instagram.com/me/media?fields=id,caption,media_type,media_url,thumbnail_url,timestamp&access_token={$accessToken}";

    // Inisialisasi cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $responseText = curl_exec($ch);
    curl_close($ch);

    // Decode respons JSON
    $result = json_decode($responseText, true);

    // Periksa apakah respons valid
    if (json_last_error() !== JSON_ERROR_NONE) {
      return response()->json(['error' => 'Invalid JSON response'], 500);
    }

    // Kembalikan data media
    return response()->json($result);
  }
}
