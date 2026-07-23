export type GigStatus = 'pending' | 'confirmed' | 'cancelled';
export type RsvpStatus = 'pending' | 'available' | 'unavailable';

export interface GigSummary {
    id: number;
    name: string | null;
    status: GigStatus;
    date: string;       // YYYY-MM-DD
    start_time: string | null; // HH:MM
    band: { id: number; name: string; slug: string };
    venue_name: string | null;
    // The member's own RSVP for poll-mode gigs; null when they haven't responded.
    rsvp: { status: RsvpStatus; label: string } | null;
}

export interface GigRsvp {
    status: RsvpStatus;
    note: string | null;
    responded_at: string | null;
    open: boolean;
}

// The detail endpoint returns a richer rsvp object than the list summary, so
// override rather than inherit the summary's lightweight rsvp.
export interface GigDetail extends Omit<GigSummary, 'rsvp'> {
    load_in_time: string | null;
    soundcheck_time: string | null;
    doors_time: string | null;
    end_time: string | null;
    fee: string | null;
    currency: string;
    notes: string | null;
    venue: { id: number; name: string } | null;
    rsvp: GigRsvp | null;
}
