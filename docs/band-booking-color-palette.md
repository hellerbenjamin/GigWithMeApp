# Roadie — Color Palette

A stage-and-lighting inspired palette for a band booking management app. Deep indigo anchors the interface, brighter violet handles interaction, coral drives key actions, and teal signals availability. Warm off-white neutrals keep the system feeling creative rather than corporate.

## Primary brand

| Name | Hex | Usage |
|------|-----|-------|
| Stage Indigo | `#2A1A4A` | Headers, nav, primary surfaces |
| Amp Violet | `#6C4FD8` | Buttons, links, active states |

## Accent

| Name | Hex | Usage |
|------|-----|-------|
| Encore Coral | `#FF6B5C` | CTAs, highlights, "book now" |
| Soundcheck Teal | `#2DD4A8` | Confirmed, availability tags |

## Neutrals

| Name | Hex | Usage |
|------|-----|-------|
| Canvas | `#FAF8F5` | Page background |
| Surface | `#EDEAE3` | Cards, raised surfaces |
| Muted | `#5F5E5A` | Secondary text, borders |
| Ink | `#1C1B23` | Primary text |

## Status

| Name | Fill | Text | Meaning |
|------|------|------|---------|
| Confirmed | `#2DD4A8` | `#0F6E56` | Booking confirmed |
| Pending | `#EF9F27` | `#854F0B` | Awaiting response |
| Cancelled | `#E24B4A` | `#A32D2D` | Booking cancelled |

## Notes

- Status colors (green, amber, red) are kept separate from the accent palette so a coral CTA is never confused with an error state.
- Stage Indigo is dark enough to serve as a dark-mode foundation — invert the neutrals rather than rethinking the system.
- Coral is reserved for the highest-priority actions; teal does double duty as an accent and an "available/confirmed" signal.

## CSS variables

```css
:root {
  /* Primary brand */
  --stage-indigo: #2A1A4A;
  --amp-violet: #6C4FD8;

  /* Accent */
  --encore-coral: #FF6B5C;
  --soundcheck-teal: #2DD4A8;

  /* Neutrals */
  --canvas: #FAF8F5;
  --surface: #EDEAE3;
  --muted: #5F5E5A;
  --ink: #1C1B23;

  /* Status */
  --status-confirmed: #2DD4A8;
  --status-confirmed-text: #0F6E56;
  --status-pending: #EF9F27;
  --status-pending-text: #854F0B;
  --status-cancelled: #E24B4A;
  --status-cancelled-text: #A32D2D;
}
```
