<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KotaKabupaten;

class KotaKabupatenController extends Controller
{
  public function index()
  {
    $kotaKabupaten = KotaKabupaten::all();

    return response()->json($kotaKabupaten);
  }
}
