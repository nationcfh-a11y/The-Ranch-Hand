// Star rating display (hay-colored), with optional numeric value + review count.
export default function Stars({ value = 0, count, size = 'text-base', showNumber = true }) {
  const rounded = Math.round((value || 0) * 2) / 2; // nearest half
  return (
    <span className="inline-flex items-center gap-1" aria-label={`${value || 0} out of 5 stars`}>
      <span className={`${size} leading-none tracking-tight`} aria-hidden="true">
        {[1, 2, 3, 4, 5].map((i) => {
          const fill = rounded >= i ? 'full' : rounded >= i - 0.5 ? 'half' : 'empty';
          return (
            <span key={i} className="relative inline-block">
              <span className="text-line">★</span>
              {fill !== 'empty' && (
                <span
                  className="absolute inset-0 overflow-hidden text-hay"
                  style={{ width: fill === 'half' ? '50%' : '100%' }}
                >
                  ★
                </span>
              )}
            </span>
          );
        })}
      </span>
      {showNumber && value != null && (
        <span className="text-sm font-bold text-charcoal">{Number(value).toFixed(1)}</span>
      )}
      {count != null && <span className="text-sm text-charcoal-muted">({count})</span>}
    </span>
  );
}
