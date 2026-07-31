// Caretaker summary card used on the homepage and search results.
import { Link } from 'react-router-dom';
import Stars from './Stars';
import { SERVICE_LABELS, ANIMAL_ICONS, money } from '../lib/constants';

export default function CaretakerCard({ c }) {
  return (
    <Link
      to={`/caretakers/${c.id}`}
      className="group flex flex-col overflow-hidden rounded-lg border border-line bg-cream-100 shadow-card
                 transition-shadow duration-200 hover:shadow-pop focus-visible:ring-2 focus-visible:ring-barn"
    >
      <div className="relative h-44 overflow-hidden bg-cream-200">
        <img
          src={c.photo_url}
          alt={`${c.name}, caretaker in ${c.location}`}
          loading="lazy"
          className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
        />
        {c.min_price != null && (
          <span className="absolute right-3 top-3 rounded-md bg-cream-100/95 px-2.5 py-1 text-sm font-700 text-saddle-dark shadow-card">
            from {money(c.min_price)}
          </span>
        )}
      </div>

      <div className="flex flex-1 flex-col p-4">
        <div className="flex items-start justify-between gap-2">
          <div>
            <h3 className="font-display text-lg font-700 leading-tight text-saddle-dark">{c.name}</h3>
            <p className="text-sm text-charcoal-muted">📍 {c.location}</p>
          </div>
          <span className="whitespace-nowrap text-sm text-charcoal-muted">{c.experience_years} yrs</span>
        </div>

        <div className="mt-1.5">
          {c.review_count > 0 ? (
            <Stars value={c.rating} count={c.review_count} size="text-sm" />
          ) : (
            <span className="text-sm text-charcoal-muted">New caretaker</span>
          )}
        </div>

        <p className="mt-2 line-clamp-2 text-sm leading-relaxed text-charcoal" title={c.headline || c.bio}>
          {c.headline || c.bio}
        </p>

        <div className="mt-3 flex flex-wrap gap-1.5">
          {c.animal_types.slice(0, 4).map((a) => (
            <span key={a} className="chip py-1 text-xs" title={a}>
              <span aria-hidden="true">{ANIMAL_ICONS[a] || '🐾'}</span> {a}
            </span>
          ))}
        </div>

        <div className="mt-auto pt-3 text-xs text-charcoal-muted">
          {c.services.slice(0, 3).map((s) => SERVICE_LABELS[s] || s).join(' · ')}
          {c.services.length > 3 && ` +${c.services.length - 3} more`}
        </div>
      </div>
    </Link>
  );
}
