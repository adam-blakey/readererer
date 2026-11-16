<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class ManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'name' => config('app.name'),
            'start_url' => route('dashboard', [], false),
            'scope' => '/',
            'display' => 'standalone',
            'theme_color' => color_name_to_hex(config('app.readererer_primary_color')),
            'background_color' => '#ffffff',
            'icons' => [['src' => favicon_url(), 'sizes' => '512x512', 'purpose' => 'any']],
        ], options: JSON_UNESCAPED_SLASHES)->header('Content-Type', 'application/manifest+json');
    }
}
