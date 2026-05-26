# Venue list interface — search, filters & pagination

Plan for evolving `Venues/Index` from a flat "all venues, name-sorted" grid into
a searchable, filterable, paginated venue book. Decisions captured here so the
implementation stays on rails.

## Decisions

- **Layout:** keep the responsive **card grid**. The data layer does the heavy
  lifting (server-side search/filter/pagination), so cards scale fine and keep
  the "little black book" voice. No DataTable, no card/table toggle (revisit the
  toggle only if real users hit hundreds of venues and want a dense view).
- **Pagination:** classic **numbered pages**, `?page=` in the URL. Simplest with
  Laravel's paginator + Inertia, bookmarkable, accessible.
- **Controls (v1):** free-text **search**, **Sort** dropdown, **Location**
  (country → state) filter, **Has contact info** toggle.
- **Deferred:** "Has upcoming gigs" (needs a gigs join), city-level location
  filter, saved filters.

All filter + sort + page state lives in the **URL query string** so refresh,
back-button, and link-sharing all work, and the active band still scopes
everything.

## URL / query contract

```
GET /venues?search=echo&country=United%20States&state=OR&has_contact=1&sort=name&page=2
```

| Param         | Values                                          | Default  |
| ------------- | ----------------------------------------------- | -------- |
| `search`      | free text (matched case-insensitively)          | —        |
| `country`     | one of the band's distinct countries            | —        |
| `state`       | one of the band's distinct states (in country)  | —        |
| `has_contact` | `1`                                             | —        |
| `sort`        | `name` \| `recent` \| `city` (whitelisted)      | `name`   |
| `page`        | integer                                         | `1`      |

Unknown/invalid values fall back to defaults rather than erroring.

## Backend

### Controller — `VenueController@index`

Validate the query (a small `IndexVenuesRequest`, or inline `$request->validate`
with an `in:` rule on `sort`), then build one scoped query:

The paginator is passed straight to Inertia, so the prop is Laravel's flat
paginator shape — `venues.data`, `venues.links`, `venues.total`, `venues.from`,
`venues.to`, `venues.last_page` (no API-resource `meta` wrapper).

```php
$band = ActiveBand::band();

$venues = $band->venues()
    ->when($search, fn ($q) => $q->where(fn ($group) =>
        // orWhereLike per column, grouped. caseSensitive:false → ilike on
        // Postgres (what we run everywhere, tests included); portable as a bonus.
        // (whereLike takes a single column in Laravel 13, not an array.)
        collect(['name', 'city', 'state', 'contact_person'])->each(
            fn ($c) => $group->orWhereLike($c, "%{$search}%", caseSensitive: false)
        )
    ))
    ->when($country, fn ($q) => $q->where('country', $country))
    ->when($state, fn ($q) => $q->where('state', $state))
    ->when($hasContact, fn ($q) => $q->where(fn ($w) =>
        $w->whereNotNull('contact_person')->orWhereNotNull('contact_phone')
    ))
    ->orderBy(...$sortColumn)          // name asc | created_at desc | city asc
    ->paginate(12)
    ->withQueryString();               // keeps filters on the page links

return Inertia::render('Venues/Index', [
    'venues'        => $venues,        // flat paginator: { data, links, total, … }
    'filters'       => $appliedFilters // echo back so the bar reflects the URL
    'filterOptions' => [
        'countries' => $band->venues()->whereNotNull('country')
                            ->distinct()->orderBy('country')->pluck('country'),
        'states'    => /* distinct states, scoped to selected country if set */,
    ],
    'hasVenues'     => $band->venues()->exists(), // unfiltered — drives empty-state choice
]);
```

Notes:
- Search is **case-insensitive** via `orWhereLike(..., caseSensitive: false)`
  (`ilike` on Postgres), grouped so the OR can't break the `band_id` scope. The
  test suite runs on Postgres too (see `docs/testing.md`), so this path is
  exercised against the real engine.
- **`withQueryString()`** is what makes page links carry `search`/`country`/etc.
- **`hasVenues`** is separate from `venues.total`: the total reflects the
  *filtered* count, but we need to know if the band has *any* venues to choose
  between the two empty states and whether to show the toolbar.
- Sort is whitelisted to a map (`name => ['name','asc']`, etc.) — never pass raw
  input to `orderBy`.

### Indexes (migration)

Postgres doesn't auto-index FK columns, and the default sort is by name within a
band, so add:

```php
$table->index(['band_id', 'name']);   // default listing + scope
```

`ILIKE '%term%'` can't use a btree index; if search ever gets slow at scale,
add a `pg_trgm` GIN index on `name`/`city` then. Out of scope for v1.

## Frontend

### `Venues/Index.vue`

Props change from `venues: Array` to:

```
venues:        Object   // Laravel paginator: { data, links, total, … }
filters:       Object   // { search, country, state, has_contact, sort }
filterOptions: Object   // { countries: [], states: [] }
hasVenues:     Boolean
```

State + navigation:
- Local reactive copy of `filters`, seeded from props.
- A single `applyFilters()` that calls
  `router.get('/venues', query, { preserveState: true, preserveScroll: true, replace: true, only: ['venues','filters','filterOptions'] })`.
- **Search is debounced** (~300ms) so typing doesn't fire a request per keypress.
- Changing any filter or sort **resets to page 1** (drop `page` from the query).
- Inertia's global progress bar covers loading; optionally dim the grid while a
  request is in flight.

### Components

- **`VenueFilters.vue`** — the bar above the grid:
  - search `InputText` with a leading search icon and a clear (×) button,
  - `Select` for Country, `Select` for State (disabled/empty until a country is
    chosen, options come from `filterOptions`),
  - `Select` for Sort (Name A–Z / Recently added / City),
  - a "Has contact" toggle (`ToggleButton` or a checkbox chip),
  - a **"Clear filters"** link, shown only when any filter is active.
- **`Paginator.vue`** — renders `venues.links` as `‹ 1 2 3 … ›` plus a
  "Showing X–Y of Z" line from `venues.from/to/total`. Each control is an Inertia `Link`
  (or a `router.get` preserving scroll). Hidden when there's only one page.

The existing **delete dialog and toolbar** (New venue / Import CSV) are reused
as-is. The toolbar shows whenever `hasVenues` is true (not just when the current
page has results).

### Card interaction

- **The whole card is the edit link.** Drop the pencil icon — the card itself
  navigates to `/venues/{id}/edit`. Use a "stretched link" pattern: the card is
  `relative`, an absolutely-positioned `<Link>` overlay fills it (`absolute
  inset-0`, with an `aria-label` like `Edit {venue.name}`), and the real content
  sits above it. This keeps the click target the full card without nesting
  interactive elements inside an anchor.
- **Delete stays as an overlaid button** in the corner, but must sit *above* the
  stretched link (`relative z-10`) and `@click.stop`/`.prevent` so clicking trash
  opens the confirm dialog instead of navigating to edit.
- Hover/focus affordance moves to the whole card (cursor + existing
  `hover:shadow-md`), and the card gets a visible focus ring for keyboard users
  since it's now the primary control.

### Two empty states

| Condition                              | UI                                                        |
| -------------------------------------- | --------------------------------------------------------- |
| `!hasVenues`                           | Current onboarding empty state (Add a venue / Import CSV) |
| `hasVenues && venues.data.length == 0` | "No venues match your search." + **Clear filters** button |

## Tests (`tests/Feature/Venues/ListVenuesTest.php`)

- search matches name / city / contact_person, case-insensitively;
- country + state filters narrow results;
- `has_contact` excludes venues with no contact person/phone;
- sort: `name` (default A–Z), `recent` (created_at desc), `city`;
- pagination: page size is 12, page 2 returns the next slice, and page links
  carry the active filters (`withQueryString`);
- invalid `sort` falls back to `name` (no error);
- **scoping**: another band's venues never appear regardless of filters;
- empty-filtered (`hasVenues` true, zero results) vs empty-band (`hasVenues`
  false) are distinguishable in the props.

## Rollout

1. Migration: add `['band_id','name']` index.
2. Backend: query + validation + paginator + filter options in `index()`.
3. Frontend: paginator-shaped props, `VenueFilters.vue`, `Paginator.vue`, wire
   `Index.vue`, two empty states.
4. Tests.

CSV import already feeds this list, so a band can realistically reach hundreds of
venues — server-side paging/search is what keeps that usable.
