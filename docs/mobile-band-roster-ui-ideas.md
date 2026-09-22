 # Mobile band roster: UI ideas

Future polish for the Band tab roster (`mobile/app/(tabs)/(band)/index.tsx`).
Captured for later; none are committed yet beyond the band-name header and
member count. Roughly in impact order.

## 1. Group by role
The roster API sorts members alphabetically. Sorting **Owners, then Admins,
then Members** (alphabetical within each) puts leadership at the top and reads
more like an org chart. Optionally add subtle uppercase mini-labels
("OWNERS" / "MEMBERS") between groups. Can be done client-side, or by ordering
in `BandMemberController::rosterPayload`.

## 2. Surface the `critical` flag
The roster already fetches `critical` (whether a member's availability is
required to confirm a gig) but nothing displays it. A small key/star icon or a
"Key member" caption on the row would make it meaningful, and it explains what
the "Critical for gigs" toggle in the edit sheet actually controls.

## 3. Real avatars
Rows currently show initials. The profile system already stores avatars
(`avatar_path` / `avatar_url`). Add `avatar_url` to the roster payload in
`BandMemberController` so owners/admins can recognize people at a glance;
keep the initial as the fallback.

## 4. Band header identity
A colored circle with the band's initial (or a thin brand-tinted banner) next
to the band name makes multi-band switching more glanceable and pairs well with
the switcher pills.

## 5. Contact affordances
The email/phone line is tappable (tel: / mailto:) but that isn't obvious. Small
mail/phone icons (Ionicons, already installed) on the right of each row would
signal "tap to contact" and look intentional.

## 6. Roster search
Once bands get large, a simple filter field above the list (name/email) keeps it
usable. Low priority until rosters are big.
