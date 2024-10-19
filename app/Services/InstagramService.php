<?php
// app/Services/InstagramService.php

namespace App\Services;

use App\Models\InstagramToken;
use Illuminate\Support\Facades\Http;

class InstagramService
{
  public function refreshAccessToken()
  {
    $baseUrl = "https://graph.instagram.com/refresh_access_token";
    $instagramToken = InstagramToken::latest()->first();

    if (!$instagramToken) {
      return null; // Atau lempar exception
    }

    $accessToken = $instagramToken->access_token;

    $params = [
      'grant_type' => 'ig_refresh_token',
      'access_token' => $accessToken
    ];

    // Menggunakan Http Client Laravel
    $response = Http::get($baseUrl, $params);

    if ($response->successful()) {
      return $response->json();
    }

    return null; // Atau lempar exception
  }

  public function getMedia($accessToken)
  {
    $url = "https://graph.instagram.com/me/media?access_token={$accessToken}";
    $response = Http::get($url);

    return $response->json();
  }
}
