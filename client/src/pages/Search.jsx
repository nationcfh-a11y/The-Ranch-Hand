// Search results: filters sidebar (animal, service, price, rating) + caretaker cards.
// Filters sync to the URL query string so results are shareable/back-button friendly.
import { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import SearchBar from '../components/SearchBar';
import CaretakerCard from '../components/CaretakerCard';
import { api } from '../lib/api';
import { SERVICES, ANIMAL_TYPES, ANIMAL_ICONS } from '../lib/constants';

export default function Search() {
  const [params, setParams] = useSearchParams();
  const [caretakers, setCaretakers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filtersOpen, setFiltersOpen] = useState(false);

  // Current filter values pulled from the URL.
  const filters = useMemo(
    () => ({
      animal: params.get('animal') || '',
      service: params.get('service') || '',
      maxPrice: params.get('maxPrice') || '',
      minRating: params.get('minRating') || '',
      q: params.get('q') || '',
      sort: params.get('sort') || 'rating',
    }),
    [params]
  );

  // Update one filter key in the URL (clearing empties).
  function setFilter(key, value) {
    const next = new URLSearchParams(params);
    if (value) next.set(key, value);
    else next.delete(key);
    setParams(next, { replace: true });
  }

  useEffect(() => {
    setLoading(true);
    const qs = new URLSearchParams();
    Object.entries(filters).forEach(([k, v]) => v && qs.set(k, v));
    api
      .get(`/caretakers?${qs.toString()}`, { auth: false })
      .then((d) => setCaretakers(d.caretakers))
      .catch(() => setCaretakers([]))
      .finally(() => setLoading(false));
  }, [filters]);

  const activeCount =
    (filters.animal ? 1 : 0) + (filters.service ? 1 : 0) + (filters.maxPrice ? 1 : 0) + (filters.minRating ? 1 : 0);

  const FilterPanel = (
    <div className="space-y-6">
      <div>
        <h3 className="mb-2 font-display text-base font-700">Animal type</h3>
        <div className="flex flex-col gap-1.5">
          <FilterRadio name="animal" label="Any animal" checked={!filters.animal} onChange={() => setFilter('animal', '')} />
          {ANIMAL_TYPES.map((a) => (
            <FilterRadio
              key={a}
              name="animal"
              label={`${ANIMAL_ICONS[a]} ${a}`}
              checked={filters.animal === a}
              onChange={() => setFilter('animal', a)}
            />
          ))}
        </div>
      </div>

      <div>
        <h3 className="mb-2 font-display text-base font-700">Service</h3>
        <div className="flex flex-col gap-1.5">
          <FilterRadio name="service" label="Any service" checked={!filters.service} onChange={() => setFilter('service', '')} />
          {SERVICES.map((s) => (
            <FilterRadio
              key={s.key}
              name="service"
              label={s.label}
              checked={filters.service === s.key}
              onChange={() => setFilter('service', s.key)}
            />
          ))}
        </div>
      </div>

      <div>
        <h3 className="mb-2 font-display text-base font-700">Max price (lowest rate)</h3>
        <input
          type="range"
          min="20" max="150" step="5"
          value={filters.maxPrice || 150}
          onChange={(e) => setFilter('maxPrice', e.target.value === '150' ? '' : e.target.value)}
          className="w-full accent-barn"
          aria-label="Maximum price"
        />
        <div className="mt-1 text-sm text-charcoal-muted">
          {filters.maxPrice ? `Up to $${filters.maxPrice}` : 'Any price'}
        </div>
      </div>

      <div>
        <h3 className="mb-2 font-display text-base font-700">Minimum rating</h3>
        <div className="flex flex-col gap-1.5">
          {['', '4', '4.5'].map((r) => (
            <FilterRadio
              key={r || 'any'}
              name="minRating"
              label={r ? `${r}★ & up` : 'Any rating'}
              checked={filters.minRating === r}
              onChange={() => setFilter('minRating', r)}
            />
          ))}
        </div>
      </div>

      {activeCount > 0 && (
        <button onClick={() => setParams(filters.q ? { q: filters.q } : {})} className="btn-ghost w-full text-sm">
          Clear filters ({activeCount})
        </button>
      )}
    </div>
  );

  return (
    <div className="bg-cream-200/40">
      {/* Search bar header */}
      <div className="border-b border-line bg-cream-100">
        <div className="container-rh py-5">
          <h1 className="mb-3 font-display text-2xl font-700">
            Find a horse sitter, farm sitter, or livestock caretaker
          </h1>
          <SearchBar compact initial={filters} />
        </div>
      </div>

      <div className="container-rh grid gap-8 py-8 lg:grid-cols-[260px_1fr]">
        {/* Sidebar (desktop) */}
        <aside className="hidden lg:block">
          <div className="sticky top-24 card">{FilterPanel}</div>
        </aside>

        {/* Mobile filter toggle */}
        <div className="lg:hidden">
          <button onClick={() => setFiltersOpen((v) => !v)} className="btn-secondary w-full">
            {filtersOpen ? 'Hide filters' : `Filters${activeCount ? ` (${activeCount})` : ''}`}
          </button>
          {filtersOpen && <div className="mt-4 card">{FilterPanel}</div>}
        </div>

        {/* Results */}
        <section>
          <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 className="font-display text-2xl font-700">
              {loading ? 'Searching…' : `${caretakers.length} horse & farm sitter${caretakers.length === 1 ? '' : 's'} available`}
            </h2>
            <label className="flex items-center gap-2 text-sm">
              <span className="text-charcoal-muted">Sort by</span>
              <select value={filters.sort} onChange={(e) => setFilter('sort', e.target.value)} className="input w-auto py-1.5">
                <option value="rating">Top rated</option>
                <option value="price_asc">Price: low to high</option>
                <option value="price_desc">Price: high to low</option>
                <option value="experience">Most experienced</option>
              </select>
            </label>
          </div>

          {loading ? (
            <div className="grid gap-5 sm:grid-cols-2">
              {Array.from({ length: 4 }).map((_, i) => (
                <div key={i} className="h-80 animate-pulse rounded-lg bg-cream-200" />
              ))}
            </div>
          ) : caretakers.length === 0 ? (
            <div className="card text-center">
              <div className="text-4xl" aria-hidden="true">🤠</div>
              <h2 className="mt-2 text-xl font-700">No sitters match those filters</h2>
              <p className="mt-1 text-charcoal-muted">Try widening your price range or clearing a filter.</p>
            </div>
          ) : (
            <div className="grid gap-5 sm:grid-cols-2">
              {caretakers.map((c) => (
                <CaretakerCard key={c.id} c={c} />
              ))}
            </div>
          )}
        </section>
      </div>
    </div>
  );
}

function FilterRadio({ name, label, checked, onChange }) {
  return (
    <label className="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1 text-sm hover:bg-cream-200">
      <input type="radio" name={name} checked={checked} onChange={onChange} className="accent-barn" />
      <span className={checked ? 'font-bold text-saddle-dark' : 'text-charcoal'}>{label}</span>
    </label>
  );
}
