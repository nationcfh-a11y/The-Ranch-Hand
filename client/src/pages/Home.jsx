// Homepage: hero + search, value props, how-it-works, featured caretakers,
// animal categories, testimonials. Mirrors Rover's marketing structure.
import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import SearchBar from '../components/SearchBar';
import CaretakerCard from '../components/CaretakerCard';
import Stars from '../components/Stars';
import { api } from '../lib/api';
import { ANIMAL_TYPES, ANIMAL_ICONS, ANIMAL_BLURBS, SERVICES } from '../lib/constants';

const VALUE_PROPS = [
  { icon: '🛡️', title: 'Vetted & experienced', text: 'Every horse sitter and farm sitter lists real ranch experience, the animals they handle, and verified reviews.' },
  { icon: '📸', title: 'Daily updates', text: 'Get photos and notes so you know your herd is fed, watered, and happy while you’re away.' },
  { icon: '💵', title: 'Clear, simple pricing', text: 'Sitters set their own rates. You see the full fee breakdown before you ever confirm.' },
];

const STEPS = [
  { n: 1, title: 'Search', text: 'Tell us your animals, location, and dates. Browse sitters who fit.' },
  { n: 2, title: 'Book', text: 'Pick a service, share feeding & care instructions, and request your dates.' },
  { n: 3, title: 'Relax', text: 'Your sitter takes over the chores and keeps you posted with updates.' },
];

const TESTIMONIALS = [
  { name: 'Karen M.', loc: 'Bozeman, MT', img: 32, rating: 5, text: 'Dale sent photos every day and my horses were calmer when I got back than when I left. The Ranch Hand is a lifesaver.' },
  { name: 'Frank D.', loc: 'Ocala, FL', img: 53, rating: 5, text: 'Marisol handled my gelding’s medication schedule perfectly. Finally a sitter who actually knows horses.' },
  { name: 'Aisha B.', loc: 'Lexington, KY', img: 44, rating: 5, text: 'Booked turnout for my show horse and got detailed reports each day. Worth every penny.' },
];

export default function Home() {
  const [featured, setFeatured] = useState([]);

  useEffect(() => {
    api
      .get('/caretakers?sort=rating', { auth: false })
      .then((d) => setFeatured(d.caretakers.slice(0, 6)))
      .catch(() => setFeatured([]));
  }, []);

  return (
    <>
      {/* ---- Hero ---- */}
      <section className="relative">
        <div
          className="absolute inset-0 bg-cover bg-center"
          style={{ backgroundImage: "url('https://images.unsplash.com/photo-1553284965-83fd3e82fa5a?auto=format&fit=crop&w=1600&q=70')" }}
          aria-hidden="true"
        />
        <div className="absolute inset-0 bg-hero-grain" aria-hidden="true" />
        <div className="container-rh relative py-20 sm:py-28">
          <div className="max-w-2xl">
            <span className="badge bg-hay text-charcoal">🐴 Equine, livestock &amp; ranch care, done right</span>
            <h1 className="mt-4 font-display text-4xl font-700 leading-tight text-cream-100 sm:text-5xl">
              Find a horse sitter or farm sitter you can trust.
            </h1>
            <p className="mt-4 max-w-xl text-lg leading-relaxed text-cream/90">
              The Ranch Hand connects horse and farm-animal owners with experienced, reviewed sitters. Book horse
              sitting, farm sitting, and barn sitting services with an equine, livestock, or ranch sitter for
              overnights, feeding, turnout, mucking, and meds.
            </p>
          </div>
          <div className="mt-8">
            <SearchBar />
          </div>
        </div>
      </section>

      {/* ---- Value props ---- */}
      <section className="container-rh py-16">
        <div className="grid gap-6 md:grid-cols-3">
          {VALUE_PROPS.map((v) => (
            <div key={v.title} className="card">
              <div className="text-3xl" aria-hidden="true">{v.icon}</div>
              <h3 className="mt-3 text-xl font-700">{v.title}</h3>
              <p className="mt-2 text-charcoal-muted">{v.text}</p>
            </div>
          ))}
        </div>
      </section>

      {/* ---- How it works ---- */}
      <section className="bg-cream-200/60 py-16">
        <div className="container-rh">
          <h2 className="text-center font-display text-3xl font-700">How horse &amp; farm sitting works</h2>
          <p className="mx-auto mt-2 max-w-xl text-center text-charcoal-muted">
            Three simple steps from “I need to leave town” to “my animals are in good hands.” Book a horse sitter or
            farm sitter in minutes.
          </p>
          <div className="mt-10 grid gap-6 md:grid-cols-3">
            {STEPS.map((s) => (
              <div key={s.n} className="relative card text-center">
                <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-barn font-display text-xl font-700 text-cream-100">
                  {s.n}
                </div>
                <h3 className="mt-4 text-xl font-700">{s.title}</h3>
                <p className="mt-2 text-charcoal-muted">{s.text}</p>
              </div>
            ))}
          </div>
          <div className="mt-8 text-center">
            <Link to="/search" className="btn-primary">Browse horse &amp; farm sitters</Link>
          </div>
        </div>
      </section>

      {/* ---- Featured caretakers ---- */}
      <section className="container-rh py-16">
        <div className="flex items-end justify-between">
          <div>
            <h2 className="font-display text-3xl font-700">Top-rated horse &amp; farm sitters</h2>
            <p className="mt-1 text-charcoal-muted">Hand-picked horse sitters, equine sitters, and livestock caretakers with glowing reviews.</p>
          </div>
          <Link to="/search" className="hidden text-sm font-bold text-barn hover:text-barn-dark sm:inline">
            View all →
          </Link>
        </div>
        <div className="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {featured.length === 0
            ? Array.from({ length: 3 }).map((_, i) => (
                <div key={i} className="h-80 animate-pulse rounded-lg bg-cream-200" />
              ))
            : featured.map((c) => <CaretakerCard key={c.id} c={c} />)}
        </div>
      </section>

      {/* ---- Animal categories ---- */}
      <section className="bg-saddle-dark py-16 text-cream-100">
        <div className="container-rh">
          <h2 className="text-center font-display text-3xl font-700 text-cream-100">Care for every animal</h2>
          <p className="mx-auto mt-2 max-w-xl text-center text-cream/80">
            From a single trail horse to a whole mixed farm, find a horse sitter, livestock sitter, or ranch sitter
            who specializes in your animals.
          </p>
          <div className="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            {ANIMAL_TYPES.map((a) => (
              <Link
                key={a}
                to={`/search?animal=${encodeURIComponent(a)}`}
                className="group rounded-lg border border-cream/15 bg-saddle/40 p-5 text-center transition-colors hover:bg-saddle focus-visible:ring-2 focus-visible:ring-hay"
              >
                <div className="text-4xl transition-transform group-hover:scale-110" aria-hidden="true">{ANIMAL_ICONS[a]}</div>
                <h3 className="mt-3 font-display text-base font-700 text-cream-100">{a}</h3>
                <p className="mt-1 text-xs leading-snug text-cream/70">{ANIMAL_BLURBS[a]}</p>
              </Link>
            ))}
          </div>
        </div>
      </section>

      {/* ---- Services strip ---- */}
      <section className="container-rh py-16">
        <h2 className="text-center font-display text-3xl font-700">Horse &amp; farm sitting services</h2>
        <p className="mx-auto mt-2 max-w-xl text-center text-charcoal-muted">
          From barn sitting and overnight care to feeding, turnout, and meds, pick the horse sitting or farm sitting
          service you need.
        </p>
        <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {SERVICES.map((s) => (
            <Link key={s.key} to={`/search?service=${s.key}`} className="card transition-shadow hover:shadow-pop">
              <h3 className="text-lg font-700">{s.label}</h3>
              <p className="mt-1 text-sm text-charcoal-muted">{s.blurb}</p>
              <span className="mt-3 inline-block text-sm font-bold text-barn">Find sitters →</span>
            </Link>
          ))}
        </div>
      </section>

      {/* ---- Testimonials ---- */}
      <section className="bg-cream-200/60 py-16">
        <div className="container-rh">
          <h2 className="text-center font-display text-3xl font-700">Loved by horse &amp; farm owners</h2>
          <div className="mt-10 grid gap-6 md:grid-cols-3">
            {TESTIMONIALS.map((t) => (
              <figure key={t.name} className="card">
                <Stars value={t.rating} size="text-base" showNumber={false} />
                <blockquote className="mt-3 text-charcoal">“{t.text}”</blockquote>
                <figcaption className="mt-4 flex items-center gap-3">
                  <img src={`https://i.pravatar.cc/80?img=${t.img}`} alt="" className="h-10 w-10 rounded-full object-cover" />
                  <div>
                    <div className="font-700 text-saddle-dark">{t.name}</div>
                    <div className="text-xs text-charcoal-muted">{t.loc}</div>
                  </div>
                </figcaption>
              </figure>
            ))}
          </div>
        </div>
      </section>

      {/* ---- CTA ---- */}
      <section className="container-rh py-16">
        <div className="rounded-xl bg-barn px-8 py-12 text-center text-cream-100 shadow-pop">
          <h2 className="font-display text-3xl font-700 text-cream-100">Know your way around a barn?</h2>
          <p className="mx-auto mt-2 max-w-xl text-cream/90">
            Turn your animal experience into income as a horse sitter, farm sitter, or barn sitter. Set your own rates
            and schedule as a Ranch Hand caretaker.
          </p>
          <Link to="/become-a-caretaker" className="btn-hay mt-6">Become a Caretaker</Link>
        </div>
      </section>
    </>
  );
}
