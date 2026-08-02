# The Ranch Hand: WordPress theme

This folder holds the **WordPress version** of The Ranch Hand: a custom theme
(`themes/the-ranch-hand`) that recreates the "Rustic Barn" design as a real
WordPress site you can run on your **WordPress.com Business** plan.

It is a marketing + **caretaker directory** site with lead capture, the
faithful front end of the marketplace. The live booking/login engine (the
Node/React app in `../client` and `../server`) is **phase 2**.

> **Why a theme and not the Node app?** WordPress.com runs PHP + MySQL, not
> Node.js/SQLite, so the original Express app can't run there. This theme
> rebuilds the experience natively in WordPress so it works on the plan you
> bought. See the repo root `README.md` for the full app.

---

## What you get on day one

- **Homepage:** hero + search, value props, how-it-works, featured sitters,
  animal categories, services, testimonials, CTA (ports `Home.jsx`).
- **Find a Sitter:** a browsable, filterable caretaker **directory**
  (`/sitters/`) with animal / service / search / sort filters.
- **Caretaker profiles:** bio, experience, animals, services & rates,
  availability, reviews summary, and a **booking-request form**.
- **Become a Caretaker:** recruiting landing + **application form**.
- **12 sample caretakers** auto-loaded on activation (edit/replace them under
  **Caretakers** in wp-admin).
- **Leads:** every booking request and application is saved under
  **wp-admin → Leads** *and* emailed to your admin address.
- Exact "Rustic Barn" look: denim-blue `#2E4B7C`, hay gold, saddle brown,
  cream, Bitter + Nunito Sans fonts. No build step, plain CSS.

---

## Deploy to WordPress.com with GitHub Deployments

WordPress.com Business can pull a theme straight from this GitHub repo.

### 1. Push this repo to GitHub (one time)

From the repo root, see the root `README.md` "Push to GitHub" section. The
theme lives at `wordpress/themes/the-ranch-hand`.

### 2. Connect the repo in WordPress.com

1. Log in to **WordPress.com** and open your site's dashboard.
2. Go to **Settings → GitHub Deployments** (also reachable under **Hosting →
   Deployments** / *Deploy from GitHub*).
3. Click **Connect GitHub account** and authorize WordPress.com for the
   `nationcfh-a11y/The-Ranch-Hand` repository.
4. Click **Create deployment / Add repository** and choose:
   - **Repository:** `nationcfh-a11y/The-Ranch-Hand`
   - **Branch:** `main`
   - **Deployment type:** *Simple* (no build step, this theme needs none)
   - **Source / repository directory:** `wordpress/themes/the-ranch-hand`
   - **Destination directory:** `wp-content/themes/the-ranch-hand`
5. Save and **Deploy**. WordPress.com copies the theme onto your site. Every
   future `git push` to `main` re-deploys automatically.

### 3. Activate + finish setup (in wp-admin, ~3 minutes)

1. **Appearance → Themes → The Ranch Hand → Activate.**
   Activation auto-creates the *Become a Caretaker* page and loads the 12
   sample caretakers.
2. **Settings → Permalinks → Save Changes** (once) so `/sitters/` and profile
   URLs resolve. *(If "Find a Sitter" 404s, this is always the fix.)*
3. *(Optional)* **Appearance → Customize → Ranch Hand: Homepage Hero** to
   edit the hero headline, subheading, and background image.
4. *(Optional)* **Appearance → Menus**: create a menu, assign it to
   *Primary Navigation*. Without one, the theme shows sensible default links.
5. Replace the sample sitters under **Caretakers**, and swap the pravatar demo
   photos for real featured images.

---

## Editing content

| Thing | Where in wp-admin |
|-------|-------------------|
| Add / edit a sitter | **Caretakers → Add New** (title = name; the editor = bio; fields for location, rates, rating live in the post meta) |
| Animal / service tags | **Caretakers → Animals / Services** taxonomies |
| Hero text & image | **Appearance → Customize → Ranch Hand: Homepage Hero** |
| Homepage sections | `front-page.php` (arrays near the top) |
| Colors / fonts | `assets/css/theme.css` (`:root` tokens at the top) |
| Booking / application leads | **Leads** |

> **Caretaker meta fields** (location, years, rates, rating) are stored as post
> meta. The sample data sets them; to edit in the admin UI you can add a
> Custom Fields panel (Screen Options → Custom Fields) or wire an ACF/meta-box
> later. Field keys: `trh_location`, `trh_experience_years`, `trh_headline`,
> `trh_availability_notes`, `trh_photo_url`, `trh_rating`, `trh_review_count`,
> `trh_rates` (array of service_key → price).

---

## Local preview (optional)

You need a local WordPress (e.g. [Local](https://localwp.com) or
`wp-env`). Symlink or copy `themes/the-ranch-hand` into your local
`wp-content/themes/`, then activate it.

---

## Phase 2: the live app

The real marketplace (accounts, live booking, messaging, payments) is the
Node/React app in `../client` + `../server`. When you're ready, host it on a
Node platform (Render, Railway, Fly.io) and either link to it from this
WordPress site or migrate the directory into it. The caretaker data here maps
directly onto that app's `caretaker_profiles` model.
