<?php

namespace App\Services;

use App\Http\Requests\Venues\StoreVenueRequest;
use App\Models\Band;
use Illuminate\Support\Facades\Validator;
use SplFileObject;

/**
 * Reads a venue CSV and imports its rows into a band's venue book.
 *
 * Importing is two steps for the user (upload, then map columns), so this
 * service is split the same way: {@see self::parse()} reads the raw grid for
 * the mapping screen, and {@see self::import()} re-reads the same file and
 * applies the user's column→field mapping. Re-reading from the stored file
 * (rather than trusting parsed data round-tripped through the browser) keeps
 * the source of truth on the server. Controllers stay thin over this.
 */
class VenueImportService
{
    /**
     * The venue fields a CSV column can map onto, in form order. Drives both
     * the mapping UI and the mapping guess. band_id is never importable, and
     * `name` is the one required target.
     *
     * @var array<string, array{label: string, required: bool}>
     */
    private const FIELDS = [
        'name' => ['label' => 'Venue name', 'required' => true],
        'address' => ['label' => 'Address', 'required' => false],
        'city' => ['label' => 'City', 'required' => false],
        'state' => ['label' => 'State / region', 'required' => false],
        'postal_code' => ['label' => 'Postal code', 'required' => false],
        'country' => ['label' => 'Country', 'required' => false],
        'phone' => ['label' => 'Phone', 'required' => false],
        'email' => ['label' => 'Email', 'required' => false],
        'website' => ['label' => 'Website', 'required' => false],
        'contact_person' => ['label' => 'Booking contact name', 'required' => false],
        'contact_email' => ['label' => 'Booking contact email', 'required' => false],
        'contact_phone' => ['label' => 'Booking contact phone', 'required' => false],
        'notes' => ['label' => 'Notes', 'required' => false],
    ];

    /**
     * Header substrings we'll accept for each field when guessing the mapping.
     * Compared against headers normalised to lowercase alphanumerics, so
     * "Postal Code", "postal_code" and "ZIP/Postal" all collapse sensibly.
     *
     * @var array<string, array<int, string>>
     */
    private const SYNONYMS = [
        'name' => ['venuename', 'venue', 'name', 'room', 'title'],
        'address' => ['streetaddress', 'address1', 'address', 'street', 'addr'],
        'city' => ['city', 'town'],
        'state' => ['stateregion', 'state', 'region', 'province'],
        'postal_code' => ['postalcode', 'postcode', 'zipcode', 'zip', 'postal'],
        'country' => ['country', 'nation'],
        'phone' => ['venuephone', 'phonenumber', 'phone', 'telephone', 'tel'],
        'email' => ['venueemail', 'emailaddress', 'email'],
        'website' => ['website', 'webaddress', 'homepage', 'url', 'web', 'site'],
        'contact_person' => ['bookingcontact', 'contactperson', 'contactname', 'contact', 'booker', 'promoter'],
        'contact_email' => ['contactemail', 'bookingemail'],
        'contact_phone' => ['contactphone', 'bookingphone', 'contacttel'],
        'notes' => ['notes', 'note', 'comments', 'comment', 'description', 'remarks'],
    ];

    /**
     * The importable fields, shaped for the mapping UI.
     *
     * @return array<int, array{key: string, label: string, required: bool}>
     */
    public function fields(): array
    {
        return array_map(
            fn (string $key, array $meta) => ['key' => $key, ...$meta],
            array_keys(self::FIELDS),
            array_values(self::FIELDS),
        );
    }

    /**
     * Read a CSV for the mapping screen: its header labels, a short preview of
     * data rows, and a best-guess column→field mapping to pre-fill the form.
     *
     * @return array{
     *     headers: array<int, string>,
     *     preview: array<int, array<int, string>>,
     *     rowCount: int,
     *     mapping: array<string, int|null>,
     * }
     */
    public function parse(string $absolutePath, int $previewRows = 5): array
    {
        $rows = $this->readGrid($absolutePath);

        $headers = array_shift($rows) ?? [];
        $headers = array_map(fn ($h) => trim((string) $h), $headers);

        return [
            'headers' => $headers,
            'preview' => array_slice($rows, 0, $previewRows),
            'rowCount' => count($rows),
            'mapping' => $this->guessMapping($headers),
        ];
    }

    /**
     * Apply a column→field mapping to every data row and write the venues.
     *
     * Each row is validated against the same rules as the create form. Valid
     * rows update an existing venue when their name already exists in the band
     * (case-insensitive) or create a new one otherwise; invalid rows are
     * skipped and reported. Entirely blank rows are ignored silently.
     *
     * @param  array<string, int>  $mapping  field key => zero-based column index
     * @return array{
     *     created: int,
     *     updated: int,
     *     failures: array<int, array{line: int, errors: array<int, string>}>,
     * }
     */
    public function import(Band $band, string $absolutePath, array $mapping, VenueService $venues): array
    {
        $rows = $this->readGrid($absolutePath);
        array_shift($rows); // drop the header row

        // Existing venues keyed by lowercased name, so update-or-create costs
        // one query instead of one per row.
        $existing = $band->venues()
            ->get(['id', 'name'])
            ->keyBy(fn ($venue) => mb_strtolower(trim($venue->name)));

        $created = 0;
        $updated = 0;
        $failures = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2; // 1 for the header, 1 to make it 1-based

            $attributes = $this->mapRow($row, $mapping);

            // A row where every mapped cell is blank is padding, not data.
            if (collect($attributes)->every(fn ($value) => $value === null)) {
                continue;
            }

            $validator = Validator::make($attributes, StoreVenueRequest::venueRules());

            if ($validator->fails()) {
                $failures[] = ['line' => $line, 'errors' => $validator->errors()->all()];

                continue;
            }

            $data = $validator->validated();
            $match = $existing->get(mb_strtolower($data['name']));

            if ($match) {
                $venues->updateVenue($match, $data);
                $updated++;
            } else {
                $venue = $venues->createVenue($band, $data);
                // A later row with the same name should update this one, not
                // create a duplicate.
                $existing->put(mb_strtolower($venue->name), $venue);
                $created++;
            }
        }

        return ['created' => $created, 'updated' => $updated, 'failures' => $failures];
    }

    /**
     * Turn one CSV row into a venue attribute array via the mapping. Blank
     * cells become null so optional fields stay empty rather than "".
     *
     * @param  array<int, string>  $row
     * @param  array<string, int>  $mapping
     * @return array<string, string|null>
     */
    private function mapRow(array $row, array $mapping): array
    {
        $attributes = [];

        foreach ($mapping as $field => $column) {
            $value = trim((string) ($row[$column] ?? ''));
            $attributes[$field] = $value === '' ? null : $value;
        }

        return $attributes;
    }

    /**
     * Read the whole file into a grid of rows. SplFileObject's CSV reader
     * handles quoted cells, embedded commas and newlines for us.
     *
     * @return array<int, array<int, string>>
     */
    private function readGrid(string $absolutePath): array
    {
        $file = new SplFileObject($absolutePath, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        $rows = [];

        foreach ($file as $row) {
            // SplFileObject yields [null] for trailing blank lines.
            if ($row === false || $row === [null]) {
                continue;
            }

            $rows[] = $row;
        }

        if ($rows !== []) {
            // Strip a UTF-8 byte-order mark off the very first header cell.
            $rows[0][0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $rows[0][0]);
        }

        return $rows;
    }

    /**
     * Best-guess column→field mapping from the header row. Prefers an exact
     * normalised match, then a substring match, and never assigns one column
     * to two fields.
     *
     * @param  array<int, string>  $headers
     * @return array<string, int|null>
     */
    private function guessMapping(array $headers): array
    {
        $normalised = array_map(
            fn (string $h) => preg_replace('/[^a-z0-9]/', '', mb_strtolower($h)),
            $headers,
        );

        $mapping = array_fill_keys(array_keys(self::FIELDS), null);
        $taken = [];

        // Exact matches first so "phone" can't be stolen by "contact_phone"'s
        // looser substring rules before the venue phone gets a chance.
        foreach ([true, false] as $exactPass) {
            foreach (self::SYNONYMS as $field => $synonyms) {
                if ($mapping[$field] !== null) {
                    continue;
                }

                foreach ($normalised as $index => $header) {
                    if ($header === '' || in_array($index, $taken, true)) {
                        continue;
                    }

                    $hit = $exactPass
                        ? in_array($header, $synonyms, true)
                        : (bool) array_filter($synonyms, fn ($s) => str_contains($header, $s));

                    if ($hit) {
                        $mapping[$field] = $index;
                        $taken[] = $index;

                        break;
                    }
                }
            }
        }

        return $mapping;
    }
}
