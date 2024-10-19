<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InstagramToken;
use App\Services\InstagramService;

class Instagram extends Command
{
    protected $signature = 'instagram:refresh';
    protected $description = 'Instagram access token update';
    protected $instagramService;

    public function __construct(InstagramService $instagramService)
    {
        parent::__construct();
        $this->instagramService = $instagramService;
    }

    public function handle()
    {
        info("Cron Job Instagram Running at " . now());

        $baseUrl = "https://graph.instagram.com/refresh_access_token";

        // Ambil token akses terbaru dari database
        $instagramToken = InstagramToken::select('access_token')->latest()->first();
        $accessToken = config('instagram_token');

        if ($instagramToken) {
            $accessToken = $instagramToken->access_token;
        } else {
            $this->error('No access token found in database.');
            return;
        }

        $params = [
            'grant_type' => 'ig_refresh_token',
            'access_token' => $accessToken
        ];

        // Inisialisasi cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseUrl . '?' . http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true); // Menambahkan verbose untuk debugging

        // Eksekusi permintaan cURL
        $responseText = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Log respons API
        $this->info('HTTP Code: ' . $httpCode);
        $this->info('API Response: ' . $responseText);

        // Decode respons JSON
        $result = json_decode($responseText, true);

        // Periksa apakah respons valid
        if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON response: ' . $responseText);
            return; // Keluar jika ada kesalahan JSON
        }

        // Cek apakah access_token ada dalam hasil
        if (isset($result['access_token'])) {
            $newAccessToken = $result['access_token'];

            // Simpan access_token baru ke database
            $instagramToken = new InstagramToken();
            $instagramToken->access_token = $newAccessToken;
            $instagramToken->token_type = $result['token_type'] ?? 'Bearer'; // Atur default jika tidak ada
            $instagramToken->expires_in = $result['expires_in'] ?? 3600; // Atur default jika tidak ada
            $instagramToken->save();

            $this->info('Access token updated successfully.');
        } else {
            // Jika access_token tidak ada, tampilkan error
            $this->error('Error fetching access token: ' . print_r($result, true));
        }
    }
}
