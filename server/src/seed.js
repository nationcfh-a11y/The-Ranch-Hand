// Seed the SQLite database with realistic demo data so the site looks alive on first run.
// Run with: npm run seed   (re-running wipes and rebuilds the demo data)
const db = require('./db');
const { hashPassword } = require('./auth');
const { computeFees } = require('./constants');

console.log('🌱 Seeding The Ranch Hand database...');

// Wipe existing rows (order respects foreign keys).
db.exec(`
  DELETE FROM messages;
  DELETE FROM reviews;
  DELETE FROM bookings;
  DELETE FROM caretaker_profiles;
  DELETE FROM users;
  DELETE FROM sqlite_sequence;
`);

const DEMO_PASSWORD = 'password123'; // shared password for all seeded accounts (demo only)
const pw = hashPassword(DEMO_PASSWORD);

const insertUser = db.prepare(
  `INSERT INTO users (name, email, password_hash, role, location, bio, photo_url)
   VALUES (@name, @email, @pw, @role, @location, @bio, @photo_url)`
);
const insertProfile = db.prepare(
  `INSERT INTO caretaker_profiles
     (user_id, experience_years, animal_types, services, rates, availability, availability_notes, headline)
   VALUES (@user_id, @experience_years, @animal_types, @services, @rates, @availability, @availability_notes, @headline)`
);

// Build a weekly schedule object for the given days + hours.
const sched = (days, from, to) => Object.fromEntries(days.map((d) => [d, { from, to }]));
const WEEKDAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
const ALL_DAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
// A few varied schedules cycled across the seeded caretakers.
const SCHEDULE_PRESETS = [
  sched(WEEKDAYS, '08:00', '17:00'),
  sched(ALL_DAYS, '07:00', '19:00'),
  sched([...WEEKDAYS, 'Sat'], '06:00', '18:00'),
  sched(['Mon', 'Wed', 'Fri', 'Sat', 'Sun'], '09:00', '16:00'),
];

// Avatar helper — pravatar provides free placeholder portraits (no API key needed).
const avatar = (n) => `https://i.pravatar.cc/400?img=${n}`;

// --- Caretakers -------------------------------------------------------------
const caretakers = [
  {
    name: 'Dale Whitaker', location: 'Bozeman, MT', img: 12, exp: 18,
    headline: 'Lifelong horseman — 18 years on working ranches.',
    bio: 'Grew up on a cattle and quarter-horse outfit outside Bozeman. I treat every animal like my own and send daily photo updates. Comfortable with green horses, seniors, and everything in between.',
    animals: ['Horses', 'Cattle', 'Mixed Farm'],
    services: { barn_sitting: 45, overnight: 110, turnout: 30, mucking: 18, medication: 22 },
    availability: 'Weekdays & weekends; book 3+ days ahead.',
  },
  {
    name: 'Marisol Reyes', location: 'Ocala, FL', img: 5, exp: 12,
    headline: 'Equine vet tech who loves med-heavy cases.',
    bio: 'Certified vet tech specializing in horses. If your horse needs careful medication schedules, wound care, or special feeding, I am your sitter. Calm, detail-oriented, fully insured.',
    animals: ['Horses', 'Goats/Sheep'],
    services: { drop_in: 35, barn_sitting: 50, medication: 28, turnout: 32 },
    availability: 'Available most days, including early mornings.',
  },
  {
    name: 'Tucker Boone', location: 'Stephenville, TX', img: 13, exp: 9,
    headline: 'Rodeo background — confident with big herds.',
    bio: 'Team roper and ranch hand. I can manage large cattle operations, handle feeding rotations, and keep stalls spotless. No herd too big.',
    animals: ['Cattle', 'Horses', 'Mixed Farm'],
    services: { barn_sitting: 40, overnight: 95, mucking: 15, turnout: 28 },
    availability: 'Flexible; overnights preferred.',
  },
  {
    name: 'Hannah Pelletier', location: 'Burlington, VT', img: 9, exp: 7,
    headline: 'Goats, sheep & poultry specialist.',
    bio: 'I run a small homestead and adore small ruminants and birds. Kidding season, milking, coop care — I have done it all. Gentle with skittish animals.',
    animals: ['Goats/Sheep', 'Poultry', 'Pigs'],
    services: { drop_in: 28, barn_sitting: 38, mucking: 14, medication: 20 },
    availability: 'Mornings and evenings daily.',
  },
  {
    name: 'Cole Anderson', location: 'Bend, OR', img: 8, exp: 14,
    headline: 'Overnight specialist for nervous owners.',
    bio: 'I stay on-site so your animals are never alone. Experienced with horses and mixed farms. Light maintenance, security checks, and lots of TLC included.',
    animals: ['Horses', 'Mixed Farm', 'Cattle'],
    services: { overnight: 120, barn_sitting: 48, turnout: 34, medication: 25 },
    availability: 'Overnights any night; 2-night minimum.',
  },
  {
    name: 'Priya Nair', location: 'Lexington, KY', img: 16, exp: 11,
    headline: 'Thoroughbred barn manager, Bluegrass born.',
    bio: 'Managed a Lexington racing barn for a decade. Expert turnout, feeding programs, and conditioning. Your performance horses are in expert hands.',
    animals: ['Horses'],
    services: { barn_sitting: 60, turnout: 40, mucking: 20, medication: 30, overnight: 140 },
    availability: 'Weekdays; limited weekend slots.',
  },
  {
    name: 'Jesse Hartman', location: 'Boise, ID', img: 11, exp: 6,
    headline: 'Reliable drop-ins and feedings, rain or shine.',
    bio: 'Dependable, punctual, and great with routines. I handle drop-in feeding, watering, and welfare checks for horses, pigs, and poultry across the Treasure Valley.',
    animals: ['Pigs', 'Poultry', 'Horses'],
    services: { drop_in: 25, barn_sitting: 36, mucking: 13 },
    availability: 'Available every day, twice daily.',
  },
  {
    name: 'Savannah Brooks', location: 'Asheville, NC', img: 20, exp: 8,
    headline: 'Holistic care for horses & mixed farms.',
    bio: 'Natural-horsemanship approach with a soft, patient hand. Comfortable with barefoot trims coordination, herbal feeds, and rescue animals that need extra patience.',
    animals: ['Horses', 'Goats/Sheep', 'Mixed Farm'],
    services: { barn_sitting: 44, overnight: 100, turnout: 30, medication: 24, drop_in: 30 },
    availability: 'Most days; flexible scheduling.',
  },
  {
    name: 'Garrett Maddox', location: 'Cheyenne, WY', img: 33, exp: 22,
    headline: 'Old-school cowboy, 22 years with cattle & horses.',
    bio: 'If it has hooves, I have cared for it. Two decades on Wyoming ranches. Winter-hardy, early riser, and unflappable in a storm. Big operations welcome.',
    animals: ['Cattle', 'Horses', 'Mixed Farm'],
    services: { barn_sitting: 42, overnight: 105, mucking: 16, turnout: 29, medication: 23 },
    availability: 'Year-round; overnights a specialty.',
  },
  {
    name: 'Emily Sato', location: 'Petaluma, CA', img: 25, exp: 5,
    headline: 'Poultry & pig pro on the dairy coast.',
    bio: 'Sonoma County small-farm sitter. Chickens, ducks, turkeys, and pigs are my jam. Egg collection, coop cleaning, and friendly company for your flock.',
    animals: ['Poultry', 'Pigs', 'Goats/Sheep'],
    services: { drop_in: 26, barn_sitting: 34, mucking: 12 },
    availability: 'Daily morning visits; some evenings.',
  },
  {
    name: 'Wyatt Coleman', location: 'Franklin, TN', img: 51, exp: 10,
    headline: 'Show-barn turnout & exercise specialist.',
    bio: 'I keep show horses fit and happy. Structured turnout, lunging, hand-walking, and meticulous stall care. Great with hot-blooded horses that need a steady routine.',
    animals: ['Horses', 'Cattle'],
    services: { turnout: 38, barn_sitting: 50, mucking: 19, overnight: 115, medication: 26 },
    availability: 'Weekdays & weekends by request.',
  },
  {
    name: 'Nora Kelly', location: 'Missoula, MT', img: 47, exp: 13,
    headline: 'Whole-farm sitter — horses to honeybees.',
    bio: 'Run a diversified family farm and love the variety. Horses, cattle, goats, pigs, poultry — I keep the whole place humming while you are away. Daily detailed reports.',
    animals: ['Mixed Farm', 'Horses', 'Cattle', 'Goats/Sheep', 'Poultry'],
    services: { barn_sitting: 46, overnight: 108, drop_in: 30, turnout: 31, mucking: 17, medication: 24 },
    availability: 'Available most days; loves long stays.',
  },
];

const caretakerIds = [];
caretakers.forEach((c, i) => {
  const email = c.name.toLowerCase().replace(/[^a-z]+/g, '.') + '@ranchhand.test';
  const info = insertUser.run({
    name: c.name, email, pw, role: 'caretaker',
    location: c.location, bio: c.bio, photo_url: avatar(c.img),
  });
  insertProfile.run({
    user_id: info.lastInsertRowid,
    experience_years: c.exp,
    animal_types: JSON.stringify(c.animals),
    services: JSON.stringify(Object.keys(c.services)),
    rates: JSON.stringify(c.services),
    // Structured weekly schedule; the old descriptive line becomes the additional note.
    availability: JSON.stringify(SCHEDULE_PRESETS[i % SCHEDULE_PRESETS.length]),
    availability_notes: c.availability,
    headline: c.headline,
  });
  caretakerIds.push(info.lastInsertRowid);
});

// --- Owners -----------------------------------------------------------------
const owners = [
  { name: 'Karen Mitchell', location: 'Bozeman, MT', img: 32, bio: 'Owner of two quarter horses and a small goat herd.' },
  { name: 'Frank DeLuca', location: 'Ocala, FL', img: 53, bio: 'Retired and traveling often; three horses at home.' },
  { name: 'Aisha Bryant', location: 'Lexington, KY', img: 44, bio: 'Hobby farm with horses, chickens, and a pot-belly pig.' },
  { name: 'Tom Reynolds', location: 'Bend, OR', img: 60, bio: 'Cattle and a couple of mules; busy work schedule.' },
];
const ownerIds = [];
for (const o of owners) {
  const email = o.name.toLowerCase().replace(/[^a-z]+/g, '.') + '@ranchhand.test';
  const info = insertUser.run({
    name: o.name, email, pw, role: 'owner',
    location: o.location, bio: o.bio, photo_url: avatar(o.img),
  });
  ownerIds.push(info.lastInsertRowid);
}

// --- Reviews ----------------------------------------------------------------
// Spread realistic reviews across caretakers so ratings vary.
const reviewComments = [
  [5, 'Sent photos every single day. My horses were calmer when I got back than when I left!'],
  [5, 'Absolutely meticulous with the medication schedule. Total peace of mind.'],
  [4, 'Great care and communication. Showed up a touch late once but kept me posted.'],
  [5, 'Handled my whole mixed farm without a hitch. Will book every time.'],
  [5, 'My nervous mare adored them. Gentle, patient, and knowledgeable.'],
  [4, 'Stalls were spotless and the animals well-fed. Highly recommend.'],
  [5, 'Stayed overnight through a storm and kept everyone safe. A true pro.'],
  [3, 'Solid care overall; wished for a few more updates during the stay.'],
  [5, 'Goats and chickens were happy and healthy. Five stars.'],
  [5, 'Knew exactly how to handle my hot show horse. Fantastic turnout work.'],
];
const insertReview = db.prepare(
  `INSERT INTO reviews (booking_id, owner_id, caretaker_id, rating, comment, created_at)
   VALUES (NULL, @owner_id, @caretaker_id, @rating, @comment, datetime('now', @ago))`
);
caretakerIds.forEach((cid, i) => {
  // Each caretaker gets 2–4 reviews.
  const count = 2 + (i % 3);
  for (let k = 0; k < count; k++) {
    const [rating, comment] = reviewComments[(i + k) % reviewComments.length];
    insertReview.run({
      owner_id: ownerIds[(i + k) % ownerIds.length],
      caretaker_id: cid,
      rating,
      comment,
      ago: `-${10 + i * 3 + k * 5} days`,
    });
  }
});

// --- Bookings ---------------------------------------------------------------
const insertBooking = db.prepare(
  `INSERT INTO bookings
     (owner_id, caretaker_id, service, animals, start_date, end_date, instructions, status,
      base_price, owner_fee, caretaker_fee, total_price, created_at)
   VALUES
     (@owner_id, @caretaker_id, @service, @animals, @start_date, @end_date, @instructions, @status,
      @base_price, @owner_fee, @caretaker_fee, @total_price, datetime('now', @ago))`
);

function makeBooking({ owner_id, caretaker_id, service, rate, qty, animals, start, end, instructions, status, ago }) {
  const fees = computeFees(rate * qty);
  insertBooking.run({
    owner_id, caretaker_id, service,
    animals: JSON.stringify(animals),
    start_date: start, end_date: end, instructions, status,
    base_price: fees.base, owner_fee: fees.ownerFee, caretaker_fee: fees.caretakerFee, total_price: fees.total,
    ago,
  });
}

makeBooking({
  owner_id: ownerIds[0], caretaker_id: caretakerIds[0], service: 'overnight', rate: 110, qty: 3,
  animals: [{ type: 'Horses', count: 2 }, { type: 'Goats/Sheep', count: 4 }],
  start: '2026-06-20', end: '2026-06-23',
  instructions: 'Feed grain twice daily. Bandit needs his joint supplement in the AM. Goats get hay only.',
  status: 'confirmed', ago: '-2 days',
});
makeBooking({
  owner_id: ownerIds[1], caretaker_id: caretakerIds[1], service: 'medication', rate: 28, qty: 5,
  animals: [{ type: 'Horses', count: 1 }],
  start: '2026-05-10', end: '2026-05-15',
  instructions: 'Antibiotics every 8 hours, wound flush on left foreleg. Vet plan in the tack room.',
  status: 'completed', ago: '-30 days',
});
makeBooking({
  owner_id: ownerIds[2], caretaker_id: caretakerIds[5], service: 'turnout', rate: 40, qty: 4,
  animals: [{ type: 'Horses', count: 3 }],
  start: '2026-06-28', end: '2026-07-01',
  instructions: 'Turnout in the north paddock only. Bring in before 6pm. No grain for the gelding.',
  status: 'pending', ago: '-1 days',
});
makeBooking({
  owner_id: ownerIds[3], caretaker_id: caretakerIds[8], service: 'barn_sitting', rate: 42, qty: 6,
  animals: [{ type: 'Cattle', count: 12 }, { type: 'Horses', count: 2 }],
  start: '2026-04-01', end: '2026-04-07',
  instructions: 'Check water tanks twice a day. Move cattle to the east pasture mid-week.',
  status: 'completed', ago: '-65 days',
});
makeBooking({
  owner_id: ownerIds[2], caretaker_id: caretakerIds[11], service: 'drop_in', rate: 30, qty: 2,
  animals: [{ type: 'Poultry', count: 14 }, { type: 'Pigs', count: 2 }],
  start: '2026-06-15', end: '2026-06-16',
  instructions: 'Collect eggs, top off feed and water. Pigs get scraps from the blue bin.',
  status: 'confirmed', ago: '-3 days',
});

// --- Messages ---------------------------------------------------------------
const insertMessage = db.prepare(
  `INSERT INTO messages (sender_id, receiver_id, booking_id, body, created_at)
   VALUES (@sender_id, @receiver_id, NULL, @body, datetime('now', @ago))`
);
const convo = [
  { sender_id: ownerIds[0], receiver_id: caretakerIds[0], body: 'Hi Dale! Are you free to watch my two horses and goats June 20–23?', ago: '-4 days' },
  { sender_id: caretakerIds[0], receiver_id: ownerIds[0], body: 'Hi Karen — yes, those dates work. Happy to do overnights. Any special feeding I should know about?', ago: '-4 days' },
  { sender_id: ownerIds[0], receiver_id: caretakerIds[0], body: 'Bandit needs his joint supplement each morning. Everything else is straightforward!', ago: '-3 days' },
  { sender_id: caretakerIds[0], receiver_id: ownerIds[0], body: 'Got it. I will send daily photos. Looking forward to it!', ago: '-3 days' },
];
for (const m of convo) insertMessage.run(m);

console.log(`✅ Seeded ${caretakers.length} caretakers, ${owners.length} owners, 5 bookings, reviews, and messages.`);
console.log(`   Demo login password for every account: "${DEMO_PASSWORD}"`);
console.log(`   e.g. owner   -> karen.mitchell@ranchhand.test`);
console.log(`        caretaker-> dale.whitaker@ranchhand.test`);
