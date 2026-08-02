# 🐴 The Ranch Hand

A full-stack, two-sided **marketplace** connecting horse & farm-animal owners with experienced
sitters/caretakers, structurally modeled on Rover.com, adapted for the equine & livestock niche.
Runs entirely on your machine with **zero external services or API keys**. Bookings and payments
are **simulated** (no real money changes hands).

> **Design:** The visual system ("Rustic Barn") is derived from `brand_assets/DESIGN.md`
> (an authoring blueprint) and written out concretely in
> [`brand_assets/ranch-hand-design-system.md`](brand_assets/ranch-hand-design-system.md), then
> wired into `client/tailwind.config.js` and `client/src/index.css`.

> **🚀 Going live on WordPress first?** A WordPress theme port of this site,
> a branded marketing + caretaker-directory site with lead capture, ready to
> deploy to **WordPress.com Business** via GitHub Deployments, lives in
> [`wordpress/`](wordpress/README.md). That's the "site now, full app later"
> path. This Node/React app is **phase 2**.

---

## Tech stack
| Layer     | Tech |
|-----------|------|
| Frontend  | React 18 + Vite, React Router, Tailwind CSS |
| Backend   | Node.js + Express REST API |
| Database  | SQLite via `better-sqlite3` (file-based, zero setup) |
| Auth      | Email + password (bcrypt-hashed) with JWT; roles `owner` / `caretaker` |

- Frontend dev server: **http://localhost:5173**
- Backend API: **http://localhost:3001**  (Vite proxies `/api` → `:3001`)

---

## Quick start

You need **Node.js 18+** (for SQLite/`node --watch`). Then, from the project root:

```bash
# 1. Install all dependencies (root + server + client)
npm run install:all

# 2. Seed the database with demo data (~12 caretakers, owners, bookings, reviews)
npm run seed

# 3. Start BOTH servers with one command
npm run dev
```

Then open **http://localhost:5173**.

> First time only: `npm run install:all && npm run seed` (or the shortcut `npm run setup`).
> After that, just `npm run dev`.

### Demo logins
Every seeded account uses the password **`password123`**:

| Role      | Email |
|-----------|-------|
| Owner     | `karen.mitchell@ranchhand.test` |
| Caretaker | `dale.whitaker@ranchhand.test`  |

(The login page has one-click buttons that fill these in for you.)

---

## What you can do
- **Browse & search** caretakers with filters (animal type, service, price, rating) + sorting.
- **View a caretaker profile**: bio, experience, animals, services & pricing, reviews, availability calendar.
- **Book** (as an owner): pick a service, dates, animals, and special care instructions, then review a
  **clear fee breakdown** before confirming a simulated booking.
- **Become a caretaker**: multi-step onboarding (account → profile → experience/animals → services & rates).
- **Dashboards**: owners track their requests; caretakers accept/decline/complete incoming bookings; both see messages.

### Simulated fee model (mirrors Rover)
On the booking review screen the math is shown explicitly:

```
Base          = caretaker's rate × nights/visits
Owner pays     = Base + 7% owner service fee
Caretaker gets = Base − 15% platform commission
```

---

## Project structure
```
The Ranch Hand/
├── package.json            # root scripts: install:all, seed, dev (runs both via concurrently)
├── brand_assets/
│   ├── DESIGN.md                      # original authoring blueprint (source of truth)
│   └── ranch-hand-design-system.md    # concrete "Rustic Barn" tokens derived from it
├── server/                 # Express + SQLite API
│   └── src/
│       ├── index.js        # app entry + routes mount
│       ├── db.js           # SQLite connection + schema
│       ├── auth.js         # bcrypt + JWT helpers/middleware
│       ├── constants.js    # services, animals, fee model
│       ├── serializers.js  # row → JSON shaping
│       ├── seed.js         # demo data
│       └── routes/         # auth, caretakers, bookings, reviews, messages
└── client/                 # React + Vite + Tailwind
    └── src/
        ├── pages/          # Home, Search, CaretakerProfile, Booking, BecomeCaretaker, Dashboard, Login, Signup
        ├── components/     # Navbar, Footer, Logo, SearchBar, CaretakerCard, Stars
        ├── context/        # AuthContext
        └── lib/            # api client + shared constants/helpers
```

---

## Useful commands
| Command | What it does |
|---------|--------------|
| `npm run dev` | Run server (:3001) + client (:5173) together |
| `npm run dev:server` / `npm run dev:client` | Run just one side |
| `npm run seed` | Wipe & reseed the SQLite DB |
| `npm run build` | Production build of the client |

## API overview
```
GET  /api/meta                       services, animal types, fee rates
POST /api/auth/signup | login        create / authenticate (returns JWT)
GET  /api/auth/me                    current user
GET  /api/caretakers                 list + filter (animal, service, price, rating, q, sort)
GET  /api/caretakers/:id             profile + reviews
PUT  /api/caretakers/me/profile      create/update caretaker profile (auth)
POST /api/bookings/quote             fee preview (no save)
POST /api/bookings                   create booking (auth, owner)
GET  /api/bookings/mine              my bookings (auth)
PATCH /api/bookings/:id/status       confirm / complete / cancel (auth)
POST /api/reviews                    leave a review (auth, owner)
GET  /api/messages/threads           conversation list (auth)
POST /api/messages                   send message (auth)
```

---

## Google Sheets CRM mirror (optional)

Until there's a real CRM, new signups can be **mirrored** into a Google Sheet for easy
eyeballing. **SQLite remains the source of truth**. The sheet is a disposable view.

- Only **new signups** are mirrored (seeded demo accounts are not).
- The sync is **fail-soft**: it runs *after* the HTTP response and swallows its own errors,
  so a bad key or no internet can never slow down or break signup.
- **Password hashes are never written to the sheet.**
- With no credentials set, it's a silent no-op. The app still runs fully offline.

Setup: copy `server/.env.example` → `server/.env` and follow the steps in that file
(enable the Sheets API, create a service account, share the sheet with its email as **Editor**).

Columns written: `id, name, email, role, location, search_radius, signed_up_at`.

> Migrating later: export from SQLite (the real record), not from the sheet.

---

## Push to GitHub

This repo is hosted at
[`nationcfh-a11y/The-Ranch-Hand`](https://github.com/nationcfh-a11y/The-Ranch-Hand).
From the project root:

```bash
git add .
git commit -m "Your message"
git push            # first push: git push -u origin main
```

The WordPress theme lives in [`wordpress/themes/the-ranch-hand`](wordpress/themes/the-ranch-hand);
WordPress.com's GitHub Deployments pulls from there. See
[`wordpress/README.md`](wordpress/README.md) for the deploy steps.

## Notes
- The SQLite file lives at `server/ranchhand.db` and is git-ignored. Delete it and re-run
  `npm run seed` to reset.
- Secrets live in `server/.env` (git-ignored). Never commit the service-account JSON key.
- Caretaker/owner avatars and the hero photo load from free placeholder services
  (pravatar / Unsplash). No keys required, but you'll need an internet connection for images.
  Everything else works fully offline.
