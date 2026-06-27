<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * Serves the PWA web app manifest. Two flavours share one body:
 *
 * - generic — linked from ordinary pages, lands the installed app on /login;
 * - member — linked from a member's personal home, with start_url baked to
 *   /m/{push_token}. On iOS the installed PWA gets a storage sandbox separate
 *   from Safari, so start_url is the *only* way the member's identity follows
 *   them into the installed app — letting them enable push without logging in.
 */
class ManifestController extends Controller
{
    public function generic(): JsonResponse
    {
        return $this->manifest('/login');
    }

    public function member(string $pushToken): JsonResponse
    {
        // 404 on an unknown token rather than minting an app for nobody.
        User::where('push_token', $pushToken)->firstOrFail();

        return $this->manifest("/m/{$pushToken}");
    }

    private function manifest(string $startUrl): JsonResponse
    {
        return response()->json([
            'name' => 'GigWithMe',
            'short_name' => 'GigWithMe',
            'description' => 'Keep your band\'s dates on the books.',
            'start_url' => $startUrl,
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#F7F6F5',
            'theme_color' => '#1296B8',
            'icons' => [
                [
                    'src' => '/icons/icon-192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => '/icons/icon-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => '/icons/icon-maskable-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
                [
                    'src' => '/icons/app-icon.svg',
                    'sizes' => 'any',
                    'type' => 'image/svg+xml',
                    'purpose' => 'any',
                ],
            ],
        ], 200, [
            'Content-Type' => 'application/manifest+json',
        ]);
    }
}
