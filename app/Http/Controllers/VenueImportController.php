<?php

namespace App\Http\Controllers;

use App\Facades\ActiveBand;
use App\Http\Requests\Venues\ImportVenuesRequest;
use App\Http\Requests\Venues\ParseVenueImportRequest;
use App\Services\VenueImportService;
use App\Services\VenueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Bulk-import venues into the active band from a CSV.
 *
 * It's a two-step wizard: upload the file ({@see self::create()} /
 * {@see self::parse()}), then map its columns onto venue fields and confirm
 * ({@see self::map()} / {@see self::import()}). Between the steps the upload
 * lives in a per-session temp file keyed by a token in the URL, so the parsed
 * data never has to round-trip through the browser. As with the rest of the
 * venue area, everything is scoped to ActiveBand.
 */
class VenueImportController extends Controller
{
    /**
     * Where uploaded CSVs wait between the upload and import steps, on the
     * private local disk.
     */
    private const DISK = 'local';

    private const DIR = 'venue-imports';

    /**
     * Step 1: show the upload form.
     */
    public function create(): Response
    {
        return Inertia::render('Venues/Import');
    }

    /**
     * Step 1 (submit): stash the upload in a temp file and hand off to the
     * mapping step. The token is remembered in the session so another user
     * can't reach a file by guessing its token.
     */
    public function parse(ParseVenueImportRequest $request): RedirectResponse
    {
        $token = (string) Str::uuid();

        $request->file('file')->storeAs(self::DIR, "{$token}.csv", self::DISK);

        $request->session()->put(
            "venue_imports.{$token}",
            $request->file('file')->getClientOriginalName(),
        );

        return to_route('venues.import.map', ['token' => $token]);
    }

    /**
     * Step 2: read the stashed file and render the column-mapping screen.
     */
    public function map(string $token, VenueImportService $importer): Response
    {
        $filename = $this->guardToken($token);

        $parsed = $importer->parse(Storage::disk(self::DISK)->path($this->path($token)));

        return Inertia::render('Venues/ImportMap', [
            'token' => $token,
            'filename' => $filename,
            'fields' => $importer->fields(),
            ...$parsed,
        ]);
    }

    /**
     * Step 2 (submit): apply the confirmed mapping, then bin the temp file.
     * Valid rows are imported; invalid ones come back as a warning flash so
     * the user can fix and re-upload.
     */
    public function import(
        string $token,
        ImportVenuesRequest $request,
        VenueImportService $importer,
        VenueService $venues,
    ): RedirectResponse {
        $this->guardToken($token);

        $fieldKeys = array_column($importer->fields(), 'key');

        // Keep only real venue fields with a column actually chosen.
        $mapping = collect($request->validated('mapping'))
            ->only($fieldKeys)
            ->reject(fn ($column) => $column === null)
            ->all();

        $summary = $importer->import(
            ActiveBand::band(),
            Storage::disk(self::DISK)->path($this->path($token)),
            $mapping,
            $venues,
        );

        $this->discard($token, $request);

        return to_route('venues.index')
            ->with('success', $this->successMessage($summary))
            ->with('warning', $this->failureMessage($summary));
    }

    /**
     * Resolve a token to its original filename, 404ing if this session never
     * uploaded it or the temp file has since vanished.
     */
    private function guardToken(string $token): string
    {
        $filename = session("venue_imports.{$token}");

        abort_unless(
            $filename !== null && Storage::disk(self::DISK)->exists($this->path($token)),
            404,
        );

        return $filename;
    }

    private function path(string $token): string
    {
        return self::DIR."/{$token}.csv";
    }

    private function discard(string $token, $request): void
    {
        Storage::disk(self::DISK)->delete($this->path($token));
        $request->session()->forget("venue_imports.{$token}");
    }

    /**
     * @param  array{created: int, updated: int, failures: array<int, mixed>}  $summary
     */
    private function successMessage(array $summary): string
    {
        $parts = [];

        if ($summary['created']) {
            $parts[] = $summary['created'].' added';
        }

        if ($summary['updated']) {
            $parts[] = $summary['updated'].' updated';
        }

        return $parts === []
            ? 'No venues were imported.'
            : 'Venue import complete: '.implode(', ', $parts).'.';
    }

    /**
     * A compact, line-by-line account of rows we couldn't import, capped so a
     * messy file can't produce a wall of text. Null when everything imported.
     *
     * @param  array{failures: array<int, array{line: int, errors: array<int, string>}>}  $summary
     */
    private function failureMessage(array $summary): ?string
    {
        $failures = $summary['failures'];

        if ($failures === []) {
            return null;
        }

        $count = count($failures);
        $lines = collect($failures)
            ->take(10)
            ->map(fn ($failure) => "Row {$failure['line']}: ".implode(' ', $failure['errors']))
            ->all();

        if ($count > 10) {
            $lines[] = '…and '.($count - 10).' more.';
        }

        $heading = $count === 1 ? "1 row couldn't be imported:" : "{$count} rows couldn't be imported:";

        return $heading."\n".implode("\n", $lines);
    }
}
