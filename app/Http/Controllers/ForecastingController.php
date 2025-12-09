<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;

class ForecastingController extends Controller
{
    public function index()
    {
        // 1. Definisikan path script Python
        $scriptPath = base_path('app/Python/forecasting_engine.py');

        // 2. Siapkan Data Input (Contoh data dari Database Laravel)
        // Dalam implementasi nyata, ini diambil dari Model (e.g., Commodity::all())
        $inputData = json_encode([
            "commodity" => "Cabai Merah",
            "region" => "Kab. Kerinci",
            "history" => [
                ["week" => "2025-W30", "price" => 42000],
                ["week" => "2025-W31", "price" => 43500],
                // ... data lainnya
            ]
        ]);

        // 3. Jalankan Python Script
        // Pastikan python3 sudah terinstall di server/laptop Anda
        $result = Process::run("python3 \"{$scriptPath}\" '{$inputData}'");

        if ($result->failed()) {
            // Jika error (misal library python belum install), kembali ke view dengan error
            return view('admin.prediction', ['error' => $result->errorOutput()]);
        }

        // 4. Ambil Output JSON dari Python
        $forecastData = json_decode($result->output(), true);

        // 5. Kirim ke View
        return view('admin.prediction', compact('forecastData'));
    }
}