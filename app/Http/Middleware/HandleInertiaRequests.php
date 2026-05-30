<?php

namespace App\Http\Middleware;

use App\Models\Band;
use App\Services\BandSessionService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
            // VAPID public key for the browser's PushManager.subscribe(). Public
            // by design — the private key stays server-side. null disables the
            // push opt-in UI gracefully when keys aren't configured.
            'webpushKey' => config('webpush.vapid.public_key'),
            // The active band plus the user's full band list power the
            // BandSwitcher and every band-scoped screen. Resolved by the
            // HasActiveBand middleware before this runs at response time.
            'activeBand' => fn () => $this->presentBand(
                app(BandSessionService::class)->band()
            ),
            'bands' => fn () => $this->userBands($request),
        ];
    }

    /**
     * The authenticated user's bands, shaped for the switcher.
     *
     * @return array<int, array<string, mixed>>
     */
    private function userBands(Request $request): array
    {
        $user = $request->user();

        if (! $user) {
            return [];
        }

        return $user->bands()
            ->with('genres:id,name')
            ->orderBy('bands.name')
            ->get()
            ->map(fn (Band $band) => $this->presentBand($band))
            ->all();
    }

    /**
     * Flatten a band to the switcher's shape: { id, name, genre, role }. Genre
     * is a many-to-many here, so the names are joined into one label; role
     * comes from the band_user pivot.
     *
     * @return array<string, mixed>|null
     */
    private function presentBand(?Band $band): ?array
    {
        if (! $band) {
            return null;
        }

        return [
            'id' => $band->id,
            'name' => $band->name,
            'genre' => $band->genres->pluck('name')->join(', ') ?: null,
            'role' => $band->pivot?->role,
        ];
    }
}
