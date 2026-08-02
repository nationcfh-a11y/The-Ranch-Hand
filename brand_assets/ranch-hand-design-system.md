---
name: design-system-ranch-hand
description: Implementation-ready design system for The Ranch Hand equine & livestock care marketplace. Concrete "Rustic Barn" tokens, component behavior, and accessibility standards derived from brand_assets/DESIGN.md.
---

<!-- TYPEUI_SH_MANAGED_START -->

# The Ranch Hand: Design System ("Rustic Barn")

> This file fills in the concrete brand values that `DESIGN.md` (the authoring blueprint)
> intentionally left as "Repo-Specific Variables To Replace." It is the practical source of
> truth wired into `client/tailwind.config.js` and `client/src/index.css`.

## Mission
A warm, trustworthy two-sided marketplace connecting horse and farm-animal owners with
experienced sitters. The experience should feel like a well-kept country barn: warm, sturdy,
honest, and easy to navigate, never slick or corporate.

## Brand
- Product/brand: The Ranch Hand
- Audience: Horse & livestock owners; experienced rural caretakers/sitters
- Product surface: Responsive web app (marketing home + marketplace + dashboards)

## Style Foundations

### Visual style
Rustic, warm, grounded, friendly, dependable. Generous warmth via cream backgrounds, barn-red
calls to action, hay-gold highlights, and saddle-brown structure.

### Color palette (semantic tokens → values)
| Token            | Hex      | Use |
|------------------|----------|-----|
| `barn` (primary) | #A8352A  | Primary actions, brand marks, active states |
| `barn-dark`      | #842318  | Primary hover/active |
| `hay`            | #D9A441  | Accents, ratings, highlights, secondary CTAs |
| `hay-dark`       | #B9831F  | Hay hover |
| `saddle`         | #6B4226  | Structural brown, headings on light, borders |
| `saddle-dark`    | #4A2D18  | Footer, deep surfaces |
| `cream`          | #F7F1E3  | App background |
| `cream-100`      | #FBF7EE  | Card / raised surface |
| `charcoal`       | #2E2A26  | Primary text |
| `charcoal-muted` | #6B645C  | Secondary text |
| `sage`           | #6F8A5E  | Success / availability / positive |
| `clay`           | #C2603F  | Warning / pending |
| `line`           | #E4D9C3  | Hairline borders on cream |

Contrast: barn (#A8352A) on cream and on white passes AA for large text and UI; white text on
barn passes AA. charcoal on cream ≥ 7:1. Never place hay text on cream (fails). Hay is for
fills, icons ≥24px, and large display only.

### Typography
- Display / headings: **"Bitter"** (slab serif), weights 600/700. Warm, sturdy, editorial.
- Body / UI: **"Nunito Sans"**, weights 400/600/700. Friendly, highly legible.
- Scale (rem): xs .75 / sm .875 / base 1 / lg 1.125 / xl 1.25 / 2xl 1.5 / 3xl 1.875 / 4xl 2.5 / 5xl 3.25
- Headings use `font-display`; body uses `font-sans`. Line-height: 1.2 headings, 1.6 body.

### Spacing scale
4px base grid: 1=4 2=8 3=12 4=16 5=20 6=24 8=32 10=40 12=48 16=64 20=80 24=96 (Tailwind default).

### Radius / shadow / motion
- Radius: `sm` 6px, `md` 10px, `lg` 16px, `xl` 24px, `full` 9999. Cards use `lg`; buttons `md`.
- Shadow: `card` = soft warm (0 4px 16px rgba(74,45,24,.10)); `pop` = 0 12px 32px rgba(74,45,24,.16).
- Motion: 150ms ease for hover/focus; 200ms for surface transitions. Respect reduced-motion.

## Accessibility (must)
- Target WCAG 2.2 AA. Keyboard-first; every interactive element reachable and operable by keyboard.
- `:focus-visible` must show a 2px barn ring with 2px offset (`focus-visible:ring-2 ring-barn`).
- Text contrast ≥ 4.5:1 (normal) / 3:1 (large). UI/icon contrast ≥ 3:1.
- All images need alt text; decorative imagery `alt=""`. Form fields need associated `<label>`.

## Writing tone
Concise, confident, neighborly. "Find a sitter who knows horses." Avoid jargon and hype.

## Rules: Do
- Use semantic tokens (`bg-barn`, `text-saddle`), never raw hex in components.
- Define every interactive state: default, hover, focus-visible, active, disabled, loading, error.
- Design mobile-first; verify at 360 / 768 / 1024 / 1280.

## Rules: Don't
- No low-contrast text (no hay text on cream/white).
- No one-off spacing/type exceptions outside the scale.
- No hidden focus indicators; no non-descriptive button labels ("Click here").

## Component rules (anatomy · states · responsive)
- **Button**: `md` radius, 44px min touch height. Primary = barn/white; Secondary = saddle outline;
  Ghost = transparent. Disabled = 50% opacity + `cursor-not-allowed`. Loading shows spinner + keeps label.
- **Card**: cream-100 surface, `line` border, `card` shadow, `lg` radius, 24px padding. Hover lifts to `pop`.
- **Input**: white surface, `line` border, `md` radius; focus = barn ring. Error = clay border + helper text.
- **Badge**: rounded-full, sm text; service badges hay-tinted, status badges colored by state.
- **Rating**: hay stars; numeric value in charcoal; review count in charcoal-muted.
- Long content truncates with ellipsis + title attr; empty states show a friendly line + action.

## QA checklist
- [ ] All colors via tokens; contrast verified.
- [ ] Every control has hover + focus-visible + disabled.
- [ ] Keyboard path through search → results → profile → booking works.
- [ ] Layout holds at 360/768/1024/1280 with no overflow.
- [ ] Fonts load (Bitter + Nunito Sans) with sane fallbacks.

<!-- TYPEUI_SH_MANAGED_END -->
