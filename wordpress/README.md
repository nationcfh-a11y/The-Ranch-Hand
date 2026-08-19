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
- **Become a Hand:** recruiting landing page that explains the Trust Score and
  sends people into the signup wizard.
- **Hand signup (3 steps):** a real sign-up flow at `/hand-signup/` that creates
  an account and a profile. Step 1 name/phone/email/location (with a
  29,000-town city autocomplete that confirms which state you mean) plus the
  **username and password** they pick, step 2 resume, profile picture, social
  links and references, step 3 a 54-item experience checklist. See
  [Hand signup & Trust Score](#hand-signup--trust-score).
- **Hand dashboard:** `/dashboard/` with front-end sign-in, the Trust Score and its
  breakdown, review status, and one-click links back to whatever is still
  unclaimed.
- **12 sample caretakers** auto-loaded on activation (edit/replace them under
  **Caretakers** in wp-admin).
- **Leads:** every booking request and contact message is saved under
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

## Hand signup & Trust Score

A "Hand" (sitter/caretaker) signs up at `/hand-signup/`, reached from every call
to action on **Become a Hand**. Signing up creates two linked records:

1. a **WordPress user** with the `trh_hand` role (`read` only, no wp-admin
   powers), using the username and password they chose, so they can sign back in
   and keep editing, and
2. a **`caretaker` post** authored by that user, held at status **Pending
   review** so nothing unvetted ever appears in the public directory.

Step 1 creates both and signs the person in, so a half-finished signup is never
lost: they can come back, sign in at `/dashboard/`, and carry on where they
stopped. Steps 2 and 3 double as the edit screens the dashboard links to.

Usernames are validated more strictly than WordPress core does (3-30 characters;
letters, numbers, `.`, `-`, `_`; must start with a letter or number) because a
login name is permanent and gets read out over the phone. Sign-in accepts the
username **or** the email address. The dashboard shows the Hand their own
username, in case they forget it.

### Trust Score

Points are **derived from the profile**, never incremented, so the total can be
recalculated at any time and can't drift or be double-awarded
(`trh_recalculate_trust_score()`).

| Earned by | Points | Step |
|-----------|-------:|------|
| Contact details & location | 40 | 1 |
| Resume on file | 20 | 2 |
| References added (1+) | 20 | 2 |
| Experience checklist (3+ ticks) | 20 | 3 |
| Profile picture *(bonus)* | 15 | 2 |
| Social accounts connected *(bonus)* | 15 | 2 |

Finishing the required path lands on **100**; with both bonuses, **130**. Change
the amounts, labels, or add new ways to earn in **one place**:
`trh_trust_components()` in `themes/the-ranch-hand/inc/hands.php`. The wizard,
the dashboard, the landing page, and the admin column all read from it.

### Reviewing an application

Open **wp-admin → Caretakers**. Hand-created profiles show their score in the
**Trust Score** column, and the *Hand signup details* panel on the edit screen
lists phone, email, location, resume link, references, and every checklist item
they ticked. Set the post to **Published** to put them live in `/sitters/`; add
their rates and availability in the same screen.

### Where things live

| Thing | File |
|-------|------|
| Role, data model, Trust Score, experience checklist | `inc/hands.php` |
| Step handlers, validation, uploads, front-end login | `inc/hand-signup.php` |
| The wizard (all 3 steps) | `page-hand-signup.php` |
| The dashboard + sign-in | `page-dashboard.php` |
| City autocomplete, checklist, previews | `assets/js/hand-signup.js` |
| 29,738 US towns, by state | `assets/data/us-cities.json` (fetched lazily, on first use of the location field only) |

> **Not built yet:** self-service password reset. A Hand who forgets their
> password has to contact you, and you reset it in **wp-admin → Users**. The
> sign-in form links to the contact page for exactly this.

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
