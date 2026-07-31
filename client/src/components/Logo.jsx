// Brand marks. The denim "RH" artwork lives in /public.
// Sizes are height-driven (h-*) since the artwork is wider than it is tall —
// width stays auto so the glyph never squashes.

// Icon-only "RH" mark.
export function Mark({ className = 'h-9' }) {
  return (
    <img
      src="/rh-mark.png"
      alt="The Ranch Hand"
      className={`w-auto object-contain ${className}`}
    />
  );
}

// Full "RH / RANCH HAND" lockup (mark + wordmark, single image).
export function FullLogo({ className = 'h-20' }) {
  return (
    <img
      src="/rh-logo.png"
      alt="The Ranch Hand"
      className={`w-auto object-contain ${className}`}
    />
  );
}

export default function Logo({ inverse = false }) {
  return (
    <span className="inline-flex items-center gap-2.5">
      <Mark className="h-9" />
      <span className="flex flex-col leading-none">
        <span className={`font-display text-xl font-700 ${inverse ? 'text-cream-100' : 'text-saddle-dark'}`}>
          The Ranch Hand
        </span>
        <span className={`text-[11px] font-bold tracking-wide ${inverse ? 'text-cream/80' : 'text-charcoal-muted'}`}>
          EQUINE &amp; FARM CARE
        </span>
      </span>
    </span>
  );
}
