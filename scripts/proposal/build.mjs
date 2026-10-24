// Builds the client proposal (letter-size PDF) from the captured screenshots, in Woodlands' own
// palette and fonts. Usage: npm run proposal  (capture first; this step only needs Chrome)
import puppeteer from 'puppeteer-core';
import fs from 'node:fs';
import path from 'node:path';

const CHROME = process.env.CHROME_PATH ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const SHOTS = path.resolve('dist/proposal/shots');
const chunks = JSON.parse(fs.readFileSync(path.join(SHOTS, 'chunks.json'), 'utf8'));
const OUT_HTML = path.resolve('dist/proposal/proposal.html');
const OUT_PDF = path.resolve('dist/proposal/Woodlands-Hospital-Website-Proposal.pdf');
const DATE = new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
const TITLE = 'Woodlands Hospital – Website & Results Portal Proposal';
const img = (f) => 'file://' + f;
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');

// Self-hosted site fonts (built by Vite into public/build/assets).
const ASSETS = path.resolve('public/build/assets');
const font = (prefix) => {
  const f = fs.readdirSync(ASSETS).find((n) => n.startsWith(prefix) && n.endsWith('.woff2'));
  if (!f) throw new Error(`Font ${prefix} not found: run npm run build first`);
  return 'file://' + path.join(ASSETS, f);
};
const FONTS = [
  ['Newsreader', 500, 'normal', 'newsreader-500-normal'], ['Newsreader', 600, 'normal', 'newsreader-600-normal'],
  ['Newsreader', 500, 'italic', 'newsreader-500-italic'],
  ['Atkinson', 400, 'normal', 'atkinson-hyperlegible-next-400-normal'], ['Atkinson', 600, 'normal', 'atkinson-hyperlegible-next-600-normal'],
  ['Atkinson', 700, 'normal', 'atkinson-hyperlegible-next-700-normal'],
].map(([fam, w, st, p]) => `@font-face { font-family: '${fam}'; font-weight: ${w}; font-style: ${st}; src: url('${font(p)}') format('woff2'); }`).join('\n');

// The site's mark (resources/views/components/logo.blade.php): double-V "W" with the red cross.
const MARK = (ink) => `<svg viewBox="0 0 48 40" width="54" height="45" aria-hidden="true"><path d="M7 8 15.5 31 24 8l8.5 23L41 8" fill="none" stroke="${ink}" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/><path d="M38.2 2.6h4.4v4.6h4.6v4.4h-4.6v4.6h-4.4v-4.6h-4.6V7.2h4.6z" fill="#e3192d"/></svg>`;
const WORDMARK = (ink, sub) => `<div class="brand">${MARK(ink)}<div><span class="bn" style="color:${ink}">Woodlands</span><span class="bs" style="color:${sub}">Hospital · Est. 1969</span></div></div>`;
// The canopy line that runs along every forest band on the site.
const CANOPY = `url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='220' height='22' viewBox='0 0 220 22'%3E%3Cpath d='M0 21 C6 21 8 10 18 10 C26 10 27 4 36 4 C46 4 47 12 54 12 C60 12 62 7 70 7 C79 7 80 15 88 15 C94 15 96 2 108 2 C120 2 121 13 130 13 C137 13 139 8 147 8 C156 8 157 16 165 16 C171 16 173 6 182 6 C192 6 193 14 201 14 C208 14 210 21 220 21' fill='none' stroke='%239fd3b0' stroke-opacity='.55' stroke-width='1.5'/%3E%3C/svg%3E")`;

/** Hand-written captions per slice (key = screen name, index = slice number). */
const CAPTIONS = {
  home: [
    'Hero on the hospital’s own CT-suite photograph, the “Open now” board (Emergency Room, ambulance, laboratory, radiology, pharmacy, each with its extension) and four quick actions: appointment, results, refill, clinic.',
    'Departments directory in three groups – Open 24/7, Inpatient & surgical care, Specialist clinics – using only the services the hospital itself lists.',
    'Results-portal introduction: reports are private, emails say a report is ready but never contain it, and an illustration of the portal with example reports.',
    'About strip with the nurses’ station photograph and the figures the hospital publishes (since 1969, 55 beds, 2 theatres, 70+ consultants), then the “Call the right desk” board: each row dials the extension on a phone.',
    'Careers and location cards, then the footer with patient links, the ambulance button, address, main line and email.',
  ],
  services: [
    'Services header with the hospital’s ultrasound photograph and jump links to the three groups.',
    'Open 24/7 departments with photographs and direct actions: call the ambulance, request a scan or test, request a refill, message the pharmacy on WhatsApp.',
    'Inpatient & surgical care: Intensive Care Unit, surgery and theatres, and the wards.',
    'All 14 specialist clinics, each with its extension and an appointment request that pre-selects the clinic.',
    'Closing note pointing visitors to the switchboard for anything not listed.',
  ],
  emergency: ['One-tap ambulance call (red is reserved for emergency actions across the site), when to call, what to do while waiting and what to bring to the Emergency Room.'],
  appointments: [
    'Steps 1 and 2: doctor’s clinic, specialist clinic, radiology or laboratory; preferred day, an alternative and time of day. A side panel keeps the ambulance number and direct extensions in view.',
    'Step 3: patient details, returning-patient and referral boxes, a short note and consent. The patient gets a reference number on screen and by email; nothing is booked until the front desk confirms.',
  ],
  clinics: ['The timetable in its empty state. Clinic sessions appear day by day as soon as staff enter them; until then visitors are sent to extension 251. Clinic times are placeholders for the hospital to supply.'],
  doctors: ['The directory in its empty state. Names, specialties and qualifications are entered by staff; no doctor is listed until the hospital supplies the list.'],
  pharmacy: [
    'Pharmacy header with the opening hours from Settings, the refill button and the WhatsApp line.',
    'Refill form: medicines one per line, optional prescriber and collection guidance. The pharmacy is alerted, and the patient is emailed when it is ready.',
  ],
  patients: ['Patients & visitors header.', 'A practical guide with a side index: appointments, what to bring, results, prescriptions, visiting, feedback and medical records.'],
  about: ['The hospital in its own words, its 1969 beginnings, services, and a dated milestone timeline where every entry links to its public source (Stabroek News, Kaieteur News, the hospital’s About Us page).'],
  careers: ['Vacancies with department filters. The three postings are placeholders written for the demo; the hospital publishes its real vacancies from the back office.'],
  job: ['A posting with requirements and an apply button; each one carries Google for Jobs structured data. Placeholder posting.'],
  contact: ['Main line, ambulance, both published email addresses, the extension list, the address with a map link, and a message form that lands with the front desk.'],
  'portal-login': ['Portal sign-in. There is no public sign-up; staff create the record and the patient receives a one-time invitation link. Messages never reveal whether an email is registered, and sign-in is rate-limited.'],
  'portal-dashboard': ['The patient’s reports, newest first, with department, dates and size. “Open” views the PDF, “Save PDF” downloads it. Demo patient and SAMPLE reports.'],
  'sample-report': ['Page 1 of a SAMPLE report as the patient opens it. In the live portal this is the signed PDF that the laboratory or radiology team uploads.'],
  'desk-dashboard': ['The front desk’s overview leads with the one number to act on: appointment requests waiting to be confirmed, and how many want today or tomorrow. Then weekly requests, refills, messages, pending portal invitations, a 30-day chart, today’s bookings and requests by service. Demo data.'],
  'desk-appointments': ['Requests filtered to “Waiting to confirm”, soonest preferred day first, with search by name, phone or reference. Demo requests.'],
  'desk-appointment': ['One request: details, the patient’s note and private staff notes. Confirm, offer another time or decline; the patient is emailed and the next waiting request opens. Demo request.'],
  'desk-patient': ['The front desk’s view of a portal record: contact details and invitation status. The front desk sees how many reports exist, never their titles or files.'],
  'desk-refills': ['The refill queue: New, Preparing, Ready to collect and Closed, with WhatsApp links and staff notes. Demo requests.'],
  'lab-dashboard': ['The laboratory’s overview leads with reports not opened after three days, then publishing and activation figures and the latest reports. Demo data.'],
  'lab-patient': ['Publishing a report: department, sample date, title and the PDF (checked by its content, PDF only). The patient is emailed that a report is ready, without the test name, and the table shows whether each report has been opened.'],
  'admin-jobs': ['Job postings with applicant counts and status. Placeholder postings.'],
  'admin-applications': ['Applications with status tabs, filters and search. CVs are stored privately and downloaded only by administrators. Demo applicants.'],
  'admin-settings': ['Phone numbers, the ambulance line, extensions, published emails and the notification address – changed without a developer.', 'Pharmacy and visiting hours, a clinic note and a site-wide announcement banner.'],
  'admin-staff': ['Staff accounts with three roles: Administrator, Front desk, and Lab & radiology.'],
};

const SECTIONS = [
  { id: 'public', eyebrow: 'Part 1', title: 'The public website', intro: 'A modern, phone-first replacement for the woodlandshospital.com holding page. Every fact on it comes from the hospital’s own published pages or a cited news report, and the phone numbers, extensions and hours are edited by staff.', screens: [
    ['home', 'Home page', ['Emergency, ambulance and the 24-hour departments are the first thing a visitor sees, with their extensions.', 'Quick actions for the four things patients come to do: book, see results, refill, find a clinic.', 'The hospital’s own photographs where they exist; credited Wikimedia Commons images elsewhere, to be replaced with hospital photography.']],
    ['services', 'Services & departments', ['22 departments, exactly as the hospital lists them, grouped the way a visitor thinks about them.', 'Every department has its extension and a direct action.']],
    ['emergency', 'Emergency Room & ambulance', ['A calm, plain-language page for the most urgent visit to the site, with one-tap calls.']],
    ['appointments', 'Appointment requests', ['Replaces the phone-only booking: a three-step request for clinics, specialists, radiology or the laboratory.', 'The front desk confirms or offers another time from the back office, and the patient is emailed.']],
    ['clinics', 'Specialist clinic timetable', ['Managed by the front desk; it fills in as sessions are entered.']],
    ['doctors', 'Find a doctor', ['Managed by administrators; searchable by name and specialty once the list is supplied.']],
    ['pharmacy', 'Pharmacy & refills', ['Online refill requests alongside the existing WhatsApp line.']],
    ['patients', 'Patients & visitors', ['What to bring, how results work, visiting and records in one place.']],
    ['about', 'About Woodlands', ['History and milestones, each with its source.']],
    ['careers', 'Careers', ['Online applications with CV upload, feeding the back office.']],
    ['job', 'Job posting', ['Search-engine ready vacancy pages.']],
    ['contact', 'Contact', ['Every published number and address, plus a message form.']],
  ]},
  { id: 'portal', eyebrow: 'Part 2', title: 'The secure results portal', intro: 'The centrepiece. Today the “view results” link on the holding page goes to a raw IP address over plain HTTP. The portal replaces it with private accounts on the hospital’s own domain: the laboratory or radiology team publishes a PDF, the patient is emailed that it is ready, and only that patient can open it after signing in.', screens: [
    ['portal-login', 'Patient sign-in', ['Accounts are created by staff and activated from an emailed, time-limited link; when a date of birth is on file, the patient confirms it.', 'Forgotten passwords are reset by the patient from this page.']],
    ['portal-dashboard', 'The patient’s reports', ['Lab and radiology reports with dates, open on any phone or save as PDF.', 'Pages are never cached, and signing out clears the session for shared computers.']],
    ['sample-report', 'A report (SAMPLE)', ['Reports are stored outside the public web folder and streamed only to the patient they belong to; any other ID simply returns “not found”.']],
  ]},
  { id: 'admin', eyebrow: 'Part 3', title: 'The staff back office', intro: 'One private dashboard with three roles. The front desk works appointment requests, refills, messages, the clinic timetable and portal invitations. The laboratory and radiology publish reports. Administrators manage everything, including doctors, jobs, settings and staff accounts.', screens: [
    ['desk-dashboard', 'Front desk: overview', ['Leads with requests waiting to be confirmed, so nothing is missed.']],
    ['desk-appointments', 'Front desk: appointment requests', ['Filter by status and service; search by name, phone or reference.']],
    ['desk-appointment', 'Front desk: confirm a request', ['Confirm, reschedule or decline in one step, with an optional message to the patient.']],
    ['desk-patient', 'Front desk: portal record', ['Creates portal accounts and sends invitations without seeing report contents.']],
    ['desk-refills', 'Front desk: pharmacy refills', ['Marking a refill ready emails the patient.']],
    ['lab-dashboard', 'Laboratory: overview', ['Shows which patients have not opened a report, so the lab can follow up.']],
    ['lab-patient', 'Laboratory: publish a report', ['Upload a PDF and the patient is told it is ready.']],
    ['admin-jobs', 'Administrator: jobs', ['Publish, close or edit vacancies.']],
    ['admin-applications', 'Administrator: applications', ['Review applicants and download CVs.']],
    ['admin-settings', 'Administrator: settings', ['Numbers, extensions, hours and notices.']],
    ['admin-staff', 'Administrator: staff accounts', ['Add staff and assign roles.']],
  ]},
];

const FEATURES = [
  ['Public website', ['Phone-first, light and dark themes', 'Emergency and ambulance on every page', '22 departments with extensions', 'Appointment requests with reference numbers', 'Online pharmacy refills', 'Clinic timetable and doctor directory', 'Careers with CV upload', 'Sitemap and structured data for search']],
  ['Results portal', ['Invitation-only patient accounts', 'Date-of-birth check on activation', 'Lab and radiology PDFs, owner-only', '“Report ready” emails without results', 'Self-service password reset', 'Rate-limited sign-in, no caching']],
  ['Staff back office', ['Three roles: admin, front desk, lab', 'Confirm or reschedule with patient email', 'Refill and message queues', 'Report publishing and “not opened” follow-up', 'Jobs and applications', 'Editable numbers, hours and notices']],
  ['Platform', ['Laravel 13 on PHP 8.3', 'Runs on standard shared hosting', 'Private file storage outside the web root', 'HTTPS, security headers, spam protection', '67 automated tests', 'Packaged installer and written guide']],
];

const PHONES = [
  [['m-home', 'Home'], ['m-emergency', 'Emergency & ambulance'], ['m-appointments', 'Appointment request']],
  [['m-portal', 'Results portal (patient)'], ['m-desk-dashboard', 'Overview (front desk)'], ['m-desk-appointment', 'Confirm a request (front desk)']],
];

function caption(key, i, c) {
  const text = CAPTIONS[key]?.[i];
  if (!text) return '';
  const n = c.chunks.length > 1 ? `Part ${i + 1} of ${c.chunks.length} · ` : '';
  return `<figcaption class="cap">${esc(n)}${esc(text)}</figcaption>`;
}

// Screen pages are packed by measured height. Each section's slices are split into pages, in order, so
// that a page can hold slices from more than one screen (a slice that starts a new screen mid-page gets a
// small screen heading). A page may shrink its screenshots a little (to no less than 85% width) to take
// one more slice, such as a short tail. The split is chosen to avoid half-empty pages first, then to use
// as few pages as possible, then to shrink as little as possible.
const CONTENT_W = 7.4 * 96; // letter width minus the 0.55in side margins, in CSS px
const PAGE_H = 10 * 96; // .page height
const ITEM_GAP = 16; // .item + .item
const SAFETY = 6;
const MIN_SCALE = 0.85;
const HALF_EMPTY = 0.55; // a page whose content ends above 55% of its height reads as half empty

const headHtml = (sec, title, url, cont) => `<header class="shot-head"><div><p class="eyebrow">${esc(sec.title)}${cont ? ' · continued' : ''}</p><h2>${esc(title)}</h2></div><p class="url">${esc(url)}</p></header>`;
const subHtml = (title, url) => `<header class="sub-head"><h3>${esc(title)}</h3><p class="url">${esc(url)}</p></header>`;
const bulletsHtml = (bullets) => `<ul class="bullets">${bullets.map((b) => `<li>${esc(b)}</li>`).join('')}</ul>`;

function sectionSlices(sec) {
  return sec.screens.flatMap(([key, title, bullets]) => {
    const c = chunks[key];
    if (!c) throw new Error(`Screenshot missing: ${key}`);
    return c.chunks.map((file, i) => ({ key, title, bullets, url: c.url, i, file, cap: caption(key, i, c), report: key === 'sample-report' }));
  });
}

/** Renders every piece once in Chrome at the page's content width and returns their outer heights. */
async function measure(browser, sections) {
  const pieces = {};
  const add = (id, h) => { pieces[id] = h; };
  for (const sec of sections) {
    add(`foot`, foot());
    for (const sl of sectionSlices(sec)) {
      if (sl.i === 0) {
        add(`head:${sl.key}:0`, headHtml(sec, sl.title, sl.url, false));
        add(`sub:${sl.key}`, subHtml(sl.title, sl.url));
        add(`bul:${sl.key}`, bulletsHtml(sl.bullets));
      } else add(`head:${sl.key}:1`, headHtml(sec, sl.title, sl.url, true));
      add(`cap:${sl.key}:${sl.i}`, sl.cap);
    }
  }
  const files = [...new Set(sections.flatMap((sec) => sectionSlices(sec).map((sl) => sl.file)))];
  const file = path.resolve('dist/proposal/measure.html');
  fs.writeFileSync(file, `<!doctype html><html><head><meta charset="utf-8"><style>${STYLE}</style></head><body>${Object.entries(pieces).map(([id, h]) => `<div data-id="${esc(id)}" style="width:${CONTENT_W}px;display:flex;flex-direction:column">${h}</div>`).join('')}</body></html>`);
  const page = await browser.newPage();
  await page.goto('file://' + file, { waitUntil: 'networkidle0' });
  await page.evaluate(() => document.fonts.ready);
  const out = await page.evaluate(async (files) => {
    const h = {};
    for (const el of document.querySelectorAll('[data-id]')) h[el.dataset.id] = el.getBoundingClientRect().height;
    const dims = {};
    for (const f of files) { const im = new Image(); im.src = f; await im.decode(); dims[f] = [im.naturalWidth, im.naturalHeight]; }
    return { h, dims };
  }, files.map(img));
  await page.close();
  fs.rmSync(file);
  return { h: out.h, dims: Object.fromEntries(files.map((f) => [f, out.dims[img(f)]])) };
}

function packSection(sec, { h, dims }) {
  const budget = PAGE_H - h.foot - SAFETY;
  const slices = sectionSlices(sec);
  const imgBox = (sl) => {
    const [nw, nh] = dims[sl.file];
    const w = sl.report ? Math.min(CONTENT_W, 6.9 * 96 * nw / nh) : CONTENT_W;
    return { w, h: w * nh / nw };
  };
  // A page holding slices a..b-1: its items, the scale that fits them, the height used.
  const layout = (a, b) => {
    const first = slices[a];
    const headH = h[`head:${first.key}:${first.i > 0 ? 1 : 0}`];
    const items = slices.slice(a, b).map((sl, k) => {
      const sub = k > 0 && sl.i === 0;
      const fixed = (k ? ITEM_GAP : 0) + (sub ? h[`sub:${sl.key}`] : 0) + (sl.i === 0 ? h[`bul:${sl.key}`] : 0) + h[`cap:${sl.key}:${sl.i}`];
      return { sl, sub, fixed, img: imgBox(sl) };
    });
    const fixed = headH + items.reduce((t, it) => t + it.fixed, 0);
    const imgs = items.reduce((t, it) => t + it.img.h, 0);
    const scale = Math.min(1, (budget - fixed) / imgs);
    return { first, items, scale, used: fixed + imgs * scale };
  };
  // best[a] = cheapest packing of slices a..end; a lone slice always fits (shrunk if it must be).
  const best = Array(slices.length + 1).fill(null);
  best[slices.length] = { cost: 0, pages: [] };
  for (let a = slices.length - 1; a >= 0; a--) {
    for (let b = a + 1; b <= slices.length; b++) {
      const pg = layout(a, b);
      if (b - a > 1 && pg.scale < MIN_SCALE) break;
      const cost = (pg.used < HALF_EMPTY * PAGE_H ? 1000 : 0) + 100 + 200 * (1 - pg.scale) + best[b].cost;
      if (!best[a] || cost < best[a].cost) best[a] = { cost, pages: [pg, ...best[b].pages] };
    }
  }
  const pages = best[0].pages;
  return pages.map((pg) => `
    <section class="page shot">
      ${headHtml(sec, pg.first.title, pg.first.url, pg.first.i > 0)}
      ${pg.items.map(({ sl, sub, img: box }) => `<div class="item">${sub ? subHtml(sl.title, sl.url) : ''}${sl.i === 0 ? bulletsHtml(sl.bullets) : ''}<figure class="${sl.report ? 'report' : ''}"><img src="${img(sl.file)}" alt="${esc(sl.title)}" style="width:${(box.w * pg.scale).toFixed(1)}px;height:${(box.h * pg.scale).toFixed(1)}px">${sl.cap}</figure></div>`).join('')}
      ${foot()}
    </section>`).join('');
}

const foot = () => `<footer class="foot"><span>${esc(TITLE)}</span><span>${esc(DATE)}</span></footer>`;

const STYLE = `
  ${FONTS}
  :root { --forest-950: #0b1a13; --forest-900: #123a29; --primary: #1b5a3f; --leaf: #9fd3b0; --clay: #a8491f; --clay-text: #9a4220; --clay-light: #f0a582; --clay-soft: #f7e6dc; --mint-soft: #e2efe5; --bg: #f5f3ec; --ink: #18211c; --ink-2: #3d4842; --ink-3: #5c6660; --line: #dcd8cc; }
  @page { size: Letter; margin: 0.5in 0.55in 0.5in; }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; font-family: 'Atkinson', 'Helvetica Neue', Arial, sans-serif; color: var(--ink); font-size: 10.4pt; line-height: 1.45; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  h1, h2, h3 { font-family: 'Newsreader', Georgia, serif; font-weight: 500; color: var(--forest-900); margin: 0; line-height: 1.05; letter-spacing: -0.01em; }
  b, strong { font-weight: 700; }
  .page { page-break-after: always; break-after: page; height: 10in; display: flex; flex-direction: column; overflow: hidden; }
  .page:last-child { page-break-after: auto; break-after: auto; }
  .eyebrow { font-size: 8pt; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: var(--clay-text); margin: 0 0 4px; }
  .sign { display: inline-flex; align-items: center; gap: 8px; } .sign i { font-style: normal; display: inline-grid; place-items: center; min-width: 22px; height: 22px; padding: 0 4px; border-radius: 4px; background: #fff; color: var(--forest-900); font-size: 7.5pt; font-weight: 700; letter-spacing: .02em; }
  .muted { color: var(--ink-3); }
  /* forest bands: cover and part dividers */
  .band { background: var(--forest-900); color: #fff; border-radius: 14px; padding: 0.65in 0.6in 0.75in; position: relative; }
  .band::after { content: ''; position: absolute; left: 0; right: 0; bottom: 18px; height: 22px; background-image: ${CANOPY}; background-repeat: repeat-x; background-size: 220px 22px; }
  .band h1 { color: #fff; } .band .eyebrow { color: var(--leaf); }
  .cover { justify-content: space-between; }
  .brand { display: flex; align-items: center; gap: 12px; } .bn { display: block; font-family: 'Newsreader', Georgia, serif; font-weight: 600; font-size: 24pt; line-height: 1; } .bs { display: block; margin-top: 4px; font-size: 7.5pt; font-weight: 700; letter-spacing: .2em; text-transform: uppercase; }
  .cover h1 { font-size: 40pt; line-height: 1.02; margin: 16px 0 14px; } .cover h1 em { color: var(--clay-light); font-style: italic; }
  .cover p.lead { color: rgba(255,255,255,.86); font-size: 12.5pt; max-width: 5.7in; margin: 0; }
  .cover .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; border-top: 1px solid rgba(255,255,255,.18); padding-top: 16px; font-size: 9.3pt; color: rgba(255,255,255,.82); margin-bottom: 28px; }
  .cover .meta b { display: block; color: #fff; font-size: 8pt; letter-spacing: .14em; text-transform: uppercase; margin-bottom: 4px; color: var(--leaf); }
  .cover .meta .n { color: #fff; font-weight: 700; font-size: 10.5pt; }
  .divider { justify-content: center; }
  .divider h1 { font-size: 40pt; margin: 8px 0 16px; } .divider p { color: rgba(255,255,255,.86); font-size: 12pt; max-width: 5.9in; margin: 0; }
  .divider ol { color: rgba(255,255,255,.86); columns: 2; font-size: 10pt; margin: 22px 0 0; padding-left: 18px; } .divider li { break-inside: avoid; margin: 3px 0; }
  /* text pages */
  .prose h1 { font-size: 27pt; margin-bottom: 10px; }
  .prose h2 { font-size: 15.5pt; margin: 14px 0 6px; }
  .prose p { margin: 0 0 8px; }
  .prose ul.list { margin: 0 0 8px; padding-left: 17px; } .prose ul.list li { margin: 2px 0; }
  .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .card { border: 1px solid var(--line); border-radius: 10px; padding: 11px 13px; background: #fff; break-inside: avoid; }
  .card h3 { font-size: 12.5pt; margin-bottom: 5px; }
  .card ul { margin: 0; padding-left: 15px; } .card li { margin: 2px 0; }
  .evidence { border-left: 3px solid var(--clay); background: var(--clay-soft); border-radius: 0 8px 8px 0; padding: 9px 12px; margin: 8px 0 10px; }
  .evidence li { margin: 3px 0; }
  .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 9px; margin: 12px 0; } .stat { background: var(--forest-900); color: #fff; border-radius: 10px; padding: 11px 12px; } .stat b { display: block; font-family: 'Newsreader', Georgia, serif; font-weight: 500; font-size: 25pt; line-height: 1; color: #fff; } .stat span { display: block; margin-top: 4px; font-size: 8.4pt; color: rgba(255,255,255,.82); line-height: 1.3; }
  table.feat { width: 100%; border-collapse: collapse; font-size: 9.4pt; } table.feat td { border-bottom: 1px solid var(--line); padding: 9px 6px; vertical-align: top; } table.feat td:first-child { width: 20%; font-family: 'Newsreader', Georgia, serif; font-size: 13pt; color: var(--forest-900); }
  .pill-row { display: flex; flex-wrap: wrap; gap: 5px; } .pill { border: 1px solid #c7dccd; background: var(--mint-soft); color: var(--forest-900); border-radius: 999px; padding: 2px 9px; font-size: 8.6pt; font-weight: 600; }
  table.terms { width: 100%; border-collapse: collapse; font-size: 9.4pt; margin: 4px 0 8px; } table.terms th, table.terms td { text-align: left; vertical-align: top; padding: 6px 8px; border-bottom: 1px solid var(--line); } table.terms th { width: 31%; color: var(--forest-900); font-weight: 700; }
  .price { display: grid; grid-template-columns: 1.3fr 1fr; gap: 12px; align-items: stretch; margin: 6px 0 10px; }
  .price .big { background: var(--forest-900); color: #fff; border-radius: 12px; padding: 14px 16px; } .price .big b { font-family: 'Newsreader', Georgia, serif; font-weight: 500; font-size: 28pt; line-height: 1; display: block; } .price .big span { color: rgba(255,255,255,.85); font-size: 9.6pt; }
  .price .year { border: 2px solid var(--clay); border-radius: 12px; padding: 12px 14px; background: #fff; } .price .year p { margin: 0 0 4px; } .price .year .k { font-size: 8pt; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--clay-text); } .price .year .v { font-family: 'Newsreader', Georgia, serif; font-size: 16pt; color: var(--forest-900); line-height: 1.15; }
  .two { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .two ul { margin: 0; padding-left: 16px; font-size: 9.4pt; } .two li { margin: 2.5px 0; }
  .weeks { list-style: none; margin: 6px 0 10px; padding: 0; } .weeks li { display: grid; grid-template-columns: 0.9in 1fr; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--line); } .weeks b { font-family: 'Newsreader', Georgia, serif; font-weight: 500; font-size: 13pt; color: var(--forest-900); }
  .next li { margin: 5px 0; }
  .contact { margin-top: 12px; background: var(--mint-soft); border-radius: 12px; padding: 14px 16px; } .contact p { margin: 2px 0; }
  /* screenshot pages */
  .shot-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; border-bottom: 2px solid var(--forest-900); padding-bottom: 6px; margin-bottom: 7px; }
  .shot-head h2 { font-size: 19pt; } .url { font-family: Menlo, Consolas, monospace; font-size: 8pt; color: var(--ink-3); margin: 0; white-space: nowrap; }
  .bullets { margin: 0 0 7px; padding-left: 16px; font-size: 9.3pt; } .bullets li { margin: 1px 0; }
  figure { margin: 0; display: flex; flex-direction: column; min-height: 0; flex: 1 1 auto; }
  figure img { display: block; max-width: 100%; min-height: 0; flex: 0 1 auto; object-fit: contain; object-position: left top; border: 1px solid var(--line); border-radius: 6px; align-self: flex-start; }
  figure.report img { max-height: 6.9in; box-shadow: 0 6px 20px rgba(0,0,0,.12); }
  .item { display: flex; flex-direction: column; flex: none; } .item + .item { margin-top: 16px; } .item figure { flex: none; } .item figure img { max-width: none; max-height: none; flex: none; }
  .sub-head { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; border-bottom: 1px solid var(--line); padding-bottom: 4px; margin-bottom: 6px; } .sub-head h3 { font-size: 14pt; }
  .cap { margin: 6px 0 0; font-size: 8.9pt; line-height: 1.38; color: var(--ink-2); border-left: 3px solid var(--clay); padding-left: 8px; flex: none; }
  .phones { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin: 14px 0.15in 0; } .phones figure { display: block; } .phones img { display: block; width: 100%; height: auto; border-radius: 18px; border: 6px solid var(--forest-950); } .phones figcaption { text-align: center; font-size: 9pt; color: var(--ink-3); margin-top: 6px; }
  footer.foot { margin-top: auto; padding-top: 8px; font-size: 7.8pt; color: #8b938d; display: flex; justify-content: space-between; flex: none; }
`;

const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox', '--allow-file-access-from-files'] });
const measured = await measure(browser, SECTIONS);
const packed = Object.fromEntries(SECTIONS.map((sec) => [sec.id, packSection(sec, measured)]));

const html = `<!doctype html><html lang="en"><head><meta charset="utf-8"><title>${esc(TITLE)}</title>
<style>${STYLE}</style></head><body>

<section class="page band cover">
  ${WORDMARK('#fff', 'rgba(255,255,255,.75)')}
  <div>
    <p class="eyebrow">Website proposal · ${esc(DATE)}</p>
    <h1>A new website and a <em>secure results portal</em> for Woodlands Hospital</h1>
    <p class="lead">A complete website on woodlandshospital.com with appointment requests, pharmacy refills, careers, a private portal where patients open their laboratory and radiology reports, and a staff back office – for US$1,000 a month, with no charge for design and development.</p>
  </div>
  <div class="meta">
    <div><b>Prepared for</b><span class="n">Woodlands Hospital Limited</span><br>110–111 Carmichael Street, North Cummingsburg<br>Georgetown, Guyana</div>
    <div><b>Prepared by</b><span class="n">Keron Lewis, Code Canvas Consultants Ltd</span><br>Port of Spain · codecanvastt.com<br>+1 868 466-CODE · info@codecanvastt.com</div>
  </div>
</section>

<section class="page prose">
  <p class="eyebrow">Executive summary</p>
  <h1>Why rebuild now</h1>
  <p>Woodlands Hospital has served Georgetown since 2 June 1969 and today publishes 55 beds, an Intensive Care Unit, two operating theatres and more than 70 consultants, with its Emergency Room, laboratory, radiology and pharmacy open around the clock. Its web presence has not kept pace with that service. As of October 2026:</p>
  <ul class="list evidence">
    <li><b>woodlandshospital.com</b>, the address listed for the hospital on the UK government’s list of medical facilities in Guyana, shows only a “currently undergoing some maintenance” holding page.</li>
    <li>That page’s link for patients to view results goes to <b>http://190.108.200.84:10/</b> – an IP address on a non-standard port, over plain HTTP rather than an encrypted connection on the hospital’s own domain.</li>
    <li>Appointments for clinics, radiology and the laboratory are by phone extension only (251, 282, 319), and pharmacy requests by WhatsApp only.</li>
    <li>The second site, woodlandshospital.info (2024), has no booking form and still links the sample doctor profiles that came with its WordPress theme.</li>
  </ul>
  <p>This proposal replaces all of that with one system on woodlandshospital.com: a public website a patient can use from a phone in an emergency, a <b>secure results portal</b> that only the patient can open, and a back office where the front desk confirms appointment requests and the laboratory publishes reports. A complete working demo has been built from the hospital’s own published information, and it can be shown live.</p>
  <div class="stat-grid">
    <div class="stat"><b>45</b><span>pages and screens in the working demo</span></div>
    <div class="stat"><b>22</b><span>departments, exactly as the hospital lists them</span></div>
    <div class="stat"><b>3</b><span>staff roles: admin, front desk, lab &amp; radiology</span></div>
    <div class="stat"><b>67</b><span>automated tests, run on every change</span></div>
  </div>
  <h2>What the hospital gains</h2>
  <div class="cols">
    <div class="card"><h3>Patients</h3><ul><li>Reports on their phone, privately, on the hospital’s own domain</li><li>Appointment and refill requests at any hour, with a reference number</li><li>Emergency numbers one tap away on every page</li></ul></div>
    <div class="card"><h3>Front desk &amp; laboratory</h3><ul><li>Fewer “are my results ready?” calls</li><li>One queue of requests, soonest day first</li><li>A list of reports nobody has opened yet</li></ul></div>
  </div>
  ${foot()}
</section>

<section class="page prose">
  <p class="eyebrow">At a glance</p>
  <h1>What is included</h1>
  <table class="feat"><tbody>
    ${FEATURES.map(([a, f]) => `<tr><td>${esc(a)}</td><td><div class="pill-row">${f.map((x) => `<span class="pill">${esc(x)}</span>`).join('')}</div></td></tr>`).join('')}
  </tbody></table>
  <h2>How a result reaches a patient</h2>
  <p>At registration, the front desk adds the patient’s email address to a portal record and the patient receives an invitation link to choose a password; when a date of birth is on file they confirm it. When a report is ready, the laboratory or radiology team uploads the PDF to that record. The patient is emailed that a new report is waiting – the email never names the test or contains the result – signs in, and opens or saves it. The laboratory can see which reports have not been opened after three days and follow up.</p>
  <h2>Content and data in the demo</h2>
  <p>Hospital facts come from woodlandshospital.info (About Us and Contact, 2024), the woodlandshospital.com holding page (extensions, ambulance and WhatsApp numbers), OpenStreetMap, Stabroek News (12 January 2020) and Kaieteur News (20 May 2018); milestones on the site link to their source. Three photographs are the hospital’s own; the rest are credited Wikimedia Commons images to be replaced. <b>Doctors and clinic times are left for the hospital to supply</b>, and the job postings are placeholders. Patients, appointment requests, refills, applications and reports in the screenshots are demo records, and the reports are marked SAMPLE.</p>
  ${foot()}
</section>

${SECTIONS.map((sec) => `
<section class="page band divider">
  <p class="eyebrow">${esc(sec.eyebrow)}</p>
  <h1>${esc(sec.title)}</h1>
  <p>${esc(sec.intro)}</p>
  <ol>${sec.screens.map((s) => `<li>${esc(s[1])}</li>`).join('')}</ol>
</section>
${packed[sec.id]}
`).join('')}

${PHONES.map((row, i) => `
<section class="page">
  <header class="shot-head"><div><p class="eyebrow">Responsive design${i ? ' · continued' : ''}</p><h2>${i ? 'The portal and back office on a phone' : 'Built for phones first'}</h2></div></header>
  <p style="margin:0 0 4px">${i ? 'Patients open their reports on their phone, and staff can confirm a request from anywhere in the building.' : 'Most visitors arrive on a phone, often in a hurry. On small screens a bar with Ambulance, Call and Book stays at the bottom of every public page.'}</p>
  <div class="phones">${row.map(([k, c]) => `<figure><img src="${img(chunks[k].chunks[0])}" alt="${esc(c)}"><figcaption>${esc(c)}</figcaption></figure>`).join('')}</div>
  ${foot()}
</section>`).join('')}

<section class="page prose">
  <p class="eyebrow">Technology, hosting &amp; security</p>
  <h1>Built on a standard, open platform</h1>
  <div class="cols">
    <div class="card"><h3>Platform</h3><ul><li>Laravel 13 on PHP 8.3 with a MySQL database: free, open-source and widely supported</li><li>No proprietary site builder or licence; any Laravel developer can maintain it</li><li>No background workers or extra servers required</li><li>67 automated tests cover the public forms, every staff role and the portal’s access rules</li></ul></div>
    <div class="card"><h3>The results portal</h3><ul><li>Separate patient and staff sign-ins; no public sign-up</li><li>Reports stored outside the public web folder, streamed only to their owner</li><li>Uploads must be real PDFs (checked by content, not the file name)</li><li>Invitation links are signed, expire after 72 hours and work once</li><li>Sign-in and reset are rate-limited and never reveal whether an email is registered</li></ul></div>
    <div class="card"><h3>Security</h3><ul><li>HTTPS throughout, with security headers and no caching of portal or staff pages</li><li>Hashed passwords and individual staff accounts with roles</li><li>The front desk never sees report titles or files</li><li>Spam protection and rate limits on every public form</li><li>Monthly patching and daily backups under the plan</li></ul></div>
    <div class="card"><h3>Accessibility &amp; reach</h3><ul><li>Atkinson Hyperlegible type, chosen so I/l/1 and O/0 never blur in phone numbers and reference codes</li><li>Colour contrast checked to WCAG AA in light and dark themes</li><li>44-pixel touch targets and keyboard navigation</li><li>Sitemap, structured data and fast, phone-first pages</li></ul></div>
  </div>
  <h2>Emails the system sends</h2>
  <p>Appointment received (patient) and new request (front desk); appointment confirmed, rescheduled or declined (patient); portal invitation; “a new report is ready”; portal password reset; refill ready to collect; and alerts to the hospital inbox for refills, messages and job applications. All are sent from the hospital’s own mailbox.</p>
  ${foot()}
</section>

<section class="page prose">
  <p class="eyebrow">Investment</p>
  <h1>The US$1,000-a-month plan</h1>
  <div class="price">
    <div class="big"><b>US$1,000 / month</b><span>Invoiced in US dollars. <b style="font-family:inherit;font-size:inherit;display:inline">US$0 for design and development</b>, and no setup fee or deposit.</span></div>
    <div class="year"><p class="k">Year one</p><p class="v">US$12,000<br><span style="font-size:11pt">or US$10,800 paid annually</span></p><p class="k" style="margin-top:6px">Build cost</p><p class="v">US$0</p></div>
  </div>
  <table class="terms"><tbody>
    <tr><th>When billing starts</th><td>At the end of the month in which the site launches, prorated for the days it was live – not on signing. Then one invoice at the end of each month, due within 14 days, payable at portal.codecanvastt.com, by bank transfer or by Visa/Mastercard.</td></tr>
    <tr><th>Term and notice</th><td>No minimum term and no early-termination penalty. 30 days’ notice by email, from either side; the site keeps running through the notice period.</td></tr>
    <tr><th>Price protection</th><td>Fixed for the first 24 months. Any later change is given in writing with 60 days’ notice. Paying 12 months in advance is optional and saves 10%: US$10,800 a year.</td></tr>
    <tr><th>If you leave</th><td>A one-time handover fee of <b>US$7,000</b>, only if you leave. It buys the website outright and moves it to hosting in Woodlands’ name, with all files, data and logins. It is waived if Code Canvas stops trading.</td></tr>
    <tr><th>Ownership</th><td>Your content, photographs, domain, patient records and reports belong to Woodlands Hospital at all times.</td></tr>
  </tbody></table>
  <h2>Service levels</h2>
  <table class="terms"><tbody>
    <tr><th>Site down or forms not sending</th><td>Response within 1 hour, any day; fix target 4 hours.</td></tr>
    <tr><th>Something on a page broken</th><td>Response within 4 business hours; fixed within 1 business day.</td></tr>
    <tr><th>Content changes</th><td>Acknowledged within 1 business day; done within 2.</td></tr>
    <tr><th>Support</th><td>Email, WhatsApp and phone, Monday to Friday, 9am to 5pm Guyana time, with an emergency number for outages.</td></tr>
  </tbody></table>
  ${foot()}
</section>

<section class="page prose">
  <p class="eyebrow">Investment · continued</p>
  <h1>What is included, and what costs extra</h1>
  <div class="two">
    <div class="card"><h3>Included in US$1,000 a month</h3><ul>
      <li>Complete design and build of the website, results portal and back office</li>
      <li>Managed hosting with CDN and SSL certificate</li>
      <li>Daily backups plus a weekly off-site copy</li>
      <li>Security patches and software updates</li>
      <li>24/7 uptime monitoring and outage response</li>
      <li><b>Up to 10 hours a month</b> of content changes and small improvements</li>
      <li>Staff training for the front desk, laboratory and administrators</li>
      <li>Google Analytics and Search Console in the hospital’s own account, with a monthly summary</li>
      <li>Email, WhatsApp and phone support, plus a quarterly review call</li>
      <li>A 30-day post-launch warranty: any defect fixed free</li>
    </ul></div>
    <div class="card"><h3>Quoted separately, only with your approval</h3><ul>
      <li>Work beyond 10 hours in a month: US$50 per hour, or a fixed quote in advance</li>
      <li>Online card payments, such as bills or deposits</li>
      <li>SMS appointment and report reminders</li>
      <li>Automatic report publishing from the laboratory or radiology system (LIS/PACS integration)</li>
      <li>Integration with patient-administration or billing systems</li>
      <li>WhatsApp Business API messaging</li>
      <li>Video consultations</li>
      <li>Professional photography, paid advertising and SEO campaigns</li>
      <li>The US$7,000 handover fee, only if you leave</li>
    </ul></div>
  </div>
  <p style="margin-top:12px">Nothing outside the plan starts without written approval. Entirely new features are quoted as a fixed price before any work begins.</p>
  ${foot()}
</section>

<section class="page prose">
  <p class="eyebrow">Timeline</p>
  <h1>Six weeks from approval to launch</h1>
  <ol class="weeks">
    <li><b>Week 1</b><span>Kick-off call; brand assets and content: logo files, photographs, the department list, extensions and hours.</span></li>
    <li><b>Week 2</b><span>Revision round 1. Forms, emails and staff roles configured with the hospital’s real addresses.</span></li>
    <li><b>Week 3</b><span>Data and content load: doctors, the clinic timetable, vacancies, staff accounts.</span></li>
    <li><b>Week 4</b><span>Revision round 2.</span></li>
    <li><b>Week 5</b><span>Formal testing with front-desk, laboratory and administrative staff: every form, email, role and the full report-publishing flow.</span></li>
    <li><b>Week 6</b><span>Launch on woodlandshospital.com, redirects from old addresses, Search Console, and staff training.</span></li>
  </ol>
  <h2>What we need from the hospital</h2>
  <ul class="list">
    <li>Logo files and any photographs of the hospital, wards and staff (a short photo session can be arranged)</li>
    <li>Confirmation of the departments, extensions and hours; the doctor list and clinic timetable; current vacancies</li>
    <li>Access to the woodlandshospital.com domain settings, and the mailboxes the site should send from and notify</li>
    <li>A named contact in the laboratory and at the front desk for testing, and the list of staff who need accounts</li>
    <li>How reports are produced today, so the upload step fits the laboratory’s routine</li>
  </ul>
  <p>The complete site stays on a private preview link throughout, and nothing goes live until the hospital approves it in writing. <b>Billing does not start until launch.</b></p>
  ${foot()}
</section>

<section class="page prose">
  <p class="eyebrow">Next steps</p>
  <h1>From proposal to launch</h1>
  <ol class="next">
    <li><b>See the working demo live.</b> It runs today with demo patients and SAMPLE reports, and every screen in this proposal can be walked through.</li>
    <li><b>Send any changes.</b> The first revision round is open as soon as you review it.</li>
    <li><b>Sign a one-page agreement</b> with these terms: US$1,000 a month, month to month on 30 days’ notice, with a US$7,000 handover fee only if you leave.</li>
    <li><b>Kick-off call</b> the same week, and launch six weeks later.</li>
  </ol>
  <div class="contact">
    <p class="eyebrow">Contact</p>
    <p style="font-family:'Newsreader',Georgia,serif;font-size:16pt;color:var(--forest-900)">Keron Lewis</p>
    <p>Director, Code Canvas Consultants Ltd · Port of Spain, Trinidad and Tobago</p>
    <p>codecanvastt.com · +1 868 466-CODE · info@codecanvastt.com<br>Book a call: calendly.com/keronlewis/30min</p>
  </div>
  ${foot()}
</section>
</body></html>`;

fs.writeFileSync(OUT_HTML, html);
const page = await browser.newPage();
await page.goto('file://' + OUT_HTML, { waitUntil: 'networkidle0' });
await page.evaluate(() => document.fonts.ready);
await page.pdf({ path: OUT_PDF, format: 'Letter', printBackground: true, preferCSSPageSize: true, displayHeaderFooter: false });
await browser.close();
console.log('PDF written:', OUT_PDF, Math.round(fs.statSync(OUT_PDF).size / 1024 / 1024 * 10) / 10 + ' MB');
