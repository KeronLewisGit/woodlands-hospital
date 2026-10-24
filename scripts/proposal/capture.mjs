// Captures the screens for the client proposal: full-page desktop shots sliced on section
// boundaries, phone shots, and page 1 of a SAMPLE lab report.
// Usage: npm run proposal   (env: BASE_URL, CHROME_PATH; demo accounts from DemoSeeder)
// Needs the dev server running (php artisan serve --port=8101) on the DemoSeeder data.
import puppeteer from 'puppeteer-core';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8101';
const OUT = path.resolve('dist/proposal/shots');
const CHROME = process.env.CHROME_PATH ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const PASSWORD = process.env.DEMO_PASSWORD ?? 'password';
const ACCOUNTS = {
  patient: ['/results/sign-in', 'patient@woodlands.test'],
  desk: ['/login', 'frontdesk@woodlands.test'],
  lab: ['/login', 'lab@woodlands.test'],
  admin: ['/login', 'admin@woodlands.test'],
};
const PATIENT_ID = process.env.PATIENT_ID ?? '1';
const APPOINTMENT_ID = process.env.APPOINTMENT_ID ?? '1';
const REPORT_ID = process.env.REPORT_ID ?? '1';

fs.rmSync(OUT, { recursive: true, force: true });
fs.mkdirSync(OUT, { recursive: true });

// [name, url, account, options]
const SCREENS = [
  ['home', '/'], ['services', '/services'], ['emergency', '/emergency'], ['appointments', '/appointments'],
  ['clinics', '/clinics'], ['doctors', '/doctors'], ['pharmacy', '/pharmacy'], ['patients', '/patients-and-visitors'],
  ['about', '/about'], ['careers', '/careers'], ['job', '/careers/radiographer'], ['contact', '/contact'],
  ['portal-login', '/results/sign-in'],
  ['portal-dashboard', '/results', 'patient'],
  ['desk-dashboard', '/admin', 'desk'],
  ['desk-appointments', '/admin/appointments?status=requested', 'desk'],
  ['desk-appointment', `/admin/appointments/${APPOINTMENT_ID}`, 'desk'],
  ['desk-patient', `/admin/patients/${PATIENT_ID}`, 'desk'],
  ['desk-refills', '/admin/refills', 'desk'],
  ['lab-dashboard', '/admin', 'lab'],
  ['lab-patient', `/admin/patients/${PATIENT_ID}`, 'lab'],
  ['admin-jobs', '/admin/jobs', 'admin'],
  ['admin-applications', '/admin/applications', 'admin'],
  ['admin-settings', '/admin/settings', 'admin'],
  ['admin-staff', '/admin/staff', 'admin'],
];
const MOBILE = [
  ['m-home', '/'], ['m-emergency', '/emergency'], ['m-appointments', '/appointments'],
  ['m-portal', '/results', 'patient'], ['m-desk-dashboard', '/admin', 'desk'], ['m-desk-appointment', `/admin/appointments/${APPOINTMENT_ID}`, 'desk'],
];

const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', protocolTimeout: 90000, args: ['--hide-scrollbars', '--no-sandbox'] });

/** One isolated browser context per account, signed in once. */
const pages = {};
async function pageFor(account) {
  const key = account ?? 'guest';
  if (pages[key]) return pages[key];
  const ctx = await browser.createBrowserContext();
  const page = await ctx.newPage();
  await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }, { name: 'prefers-color-scheme', value: 'light' }]);
  if (account) {
    const [url, email] = ACCOUNTS[account];
    await page.setViewport({ width: 1440, height: 900 });
    await page.goto(BASE + url, { waitUntil: 'networkidle0' });
    await page.type('input[name=email]', email);
    await page.type('input[name=password]', PASSWORD);
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.$eval('input[name=password]', (i) => i.form.requestSubmit())]);
    if (new URL(page.url()).pathname.endsWith('sign-in') || new URL(page.url()).pathname === '/login') throw new Error(`Sign-in failed for ${email}`);
  }
  return (pages[key] = page);
}

async function settle(page) {
  await page.evaluate(async () => {
    document.querySelectorAll('[data-reveal]').forEach((e) => e.classList.add('is-visible'));
    document.querySelectorAll('img[loading="lazy"]').forEach((i) => { i.loading = 'eager'; });
    window.scrollTo(0, document.body.scrollHeight); await new Promise((r) => setTimeout(r, 400)); window.scrollTo(0, 0);
    await Promise.race([
      Promise.all([...document.images].filter((i) => !i.complete).map((i) => new Promise((r) => { i.onload = i.onerror = r; }))),
      new Promise((r) => setTimeout(r, 1500)),
    ]);
  });
  await new Promise((r) => setTimeout(r, 400));
}

/**
 * Measure natural content blocks (header, each section, footer). Blocks taller than the
 * page budget are replaced by their children, recursively, so slices follow real section
 * boundaries instead of cutting wherever the pixel budget runs out.
 */
async function measureBlocks(page, maxH) {
  return page.evaluate((maxH) => {
    const rect = (el) => { const r = el.getBoundingClientRect(); return { top: Math.round(r.top + window.scrollY), bottom: Math.round(r.bottom + window.scrollY) }; };
    const visible = (el) => { const cs = getComputedStyle(el); const r = el.getBoundingClientRect(); return cs.display !== 'none' && cs.visibility !== 'hidden' && r.height > 24 && cs.position !== 'fixed'; };
    const skip = (el) => el.matches('script, style, template, [x-cloak], [aria-hidden="true"]:not(section)');
    const label = (el) => {
      const hs = [...el.querySelectorAll('h1, h2, h3')].filter((h) => visible(h) && h.closest('nav, footer, aside.on-dark, [aria-hidden="true"]') === null);
      const seen = new Set();
      return hs.map((h) => h.textContent.replace(/\s+/g, ' ').trim()).filter((t) => t && !seen.has(t.toLowerCase()) && seen.add(t.toLowerCase())).slice(0, 4);
    };
    const explode = (el) => {
      const r = { ...rect(el), labels: label(el) };
      if (r.bottom - r.top <= maxH) return [r];
      const kids = [...el.children].filter((k) => !skip(k) && visible(k));
      if (kids.length === 0) return [{ top: r.top, bottom: r.bottom, labels: r.labels }];
      if (kids.length === 1) return explode(kids[0]);
      return kids.flatMap(explode);
    };
    // Staff dashboard: the content column is <main id="content"> beside a fixed sidebar.
    // Public site: body-level bars, header, <main id="main"> (expanded) and the footer.
    const adminMain = document.querySelector('body.admin main#content');
    const roots = adminMain
      ? [...adminMain.children].map((el) => ({ el, trailing: false }))
      : [...document.body.children].filter((el) => ['HEADER', 'MAIN', 'FOOTER', 'DIV', 'SECTION'].includes(el.tagName) && !skip(el) && visible(el))
          .flatMap((el) => el.tagName === 'MAIN' ? [...el.children].map((k) => ({ el: k, trailing: false })) : [{ el, trailing: el.tagName === 'FOOTER' }]);
    const blocks = roots.filter((r) => visible(r.el)).flatMap((r) => explode(r.el).map((b) => ({ ...b, trailing: r.trailing }))).filter((b) => b.bottom > b.top).sort((a, b) => a.top - b.top);
    const merged = [];
    for (const b of blocks) {
      const last = merged[merged.length - 1];
      if (last && b.top < last.bottom - 2) { last.bottom = Math.max(last.bottom, b.bottom); last.trailing = last.trailing && b.trailing; last.labels = [...new Set([...last.labels, ...b.labels])]; } else merged.push({ ...b, labels: [...b.labels] });
    }
    const docH = Math.max(document.documentElement.scrollHeight, document.body.scrollHeight);
    if (merged.length) { merged[0].top = 0; merged[merged.length - 1].bottom = Math.max(merged[merged.length - 1].bottom, docH); for (let i = 1; i < merged.length; i++) merged[i].top = merged[i - 1].bottom; }
    return { blocks: merged, docH };
  }, maxH);
}

/** Greedily pack consecutive blocks into page-sized groups; a lone oversize block is hard-cut as a last resort. */
function packBlocks(blocks, maxH, docH) {
  const groups = [];
  let cur = null;
  for (const b of blocks) {
    const h = b.bottom - b.top;
    if (cur && b.bottom - cur.top <= maxH) { cur.bottom = b.bottom; cur.labels = [...new Set([...cur.labels, ...(b.labels ?? [])])]; continue; }
    if (cur) groups.push(cur);
    if (h > maxH) {
      for (let y = b.top; y < b.bottom; y += maxH) groups.push({ top: y, bottom: Math.min(y + maxH, b.bottom), labels: b.labels ?? [] });
      cur = null;
    } else cur = { top: b.top, bottom: b.bottom, labels: [...(b.labels ?? [])] };
  }
  if (cur) groups.push(cur);
  if (groups.length > 1) {
    const last = groups[groups.length - 1], prev = groups[groups.length - 2];
    if (last.bottom - last.top < maxH * 0.15 && last.bottom - prev.top <= maxH * 1.3) { prev.bottom = last.bottom; prev.labels = [...new Set([...prev.labels, ...last.labels])]; groups.pop(); }
  }
  groups[groups.length - 1].bottom = Math.min(groups[groups.length - 1].bottom, docH);
  return groups;
}

const RATIO = 1.08; // slice height ÷ width that fits a letter page beneath the heading and bullets

async function shoot(name, url, account, { width = 1440, height = 900, mobile = false } = {}) {
  const page = await pageFor(account);
  await page.setViewport({ width, height, deviceScaleFactor: mobile ? 2 : 1.25, isMobile: mobile, hasTouch: mobile });
  await page.goto(BASE + url, { waitUntil: 'networkidle0' });
  await settle(page);
  if (mobile) {
    const file = path.join(OUT, `${name}.jpg`);
    await page.screenshot({ path: file, type: 'jpeg', quality: 80 });
    console.log(`✓ ${name} (phone)`);
    return { name, url, chunks: [file], labels: [[]], mobile };
  }
  const maxH = Math.round(width * RATIO);
  let { blocks, docH } = await measureBlocks(page, maxH);
  if (name !== 'home') { blocks = blocks.filter((b) => !b.trailing); docH = blocks[blocks.length - 1].bottom; } // the footer is shown once (home)
  const groups = packBlocks(blocks, maxH, docH);
  // The staff sidebar is position: fixed, so it only covers one viewport: stretch the viewport to the page.
  if (await page.$('body.admin')) {
    await page.setViewport({ width, height: Math.max(height, Math.ceil(docH)), deviceScaleFactor: 1.25 });
    await new Promise((r) => setTimeout(r, 300));
  }
  const files = []; const labels = [];
  for (const [i, g] of groups.entries()) {
    const file = path.join(OUT, groups.length > 1 ? `${name}-${i}.jpg` : `${name}.jpg`);
    await page.screenshot({ path: file, type: 'jpeg', quality: 80, captureBeyondViewport: true, clip: { x: 0, y: g.top, width, height: g.bottom - g.top } });
    files.push(file); labels.push(g.labels ?? []);
  }
  console.log(`✓ ${name} → ${groups.length} slice(s): ${groups.map((g) => (g.labels ?? []).join(' | ') || '(no heading)').join('  ·  ')}`);
  return { name, url, chunks: files, labels };
}

/** Page 1 of a SAMPLE report, fetched through the lab's staff copy (it does not mark the report as opened). */
async function sampleReport() {
  const page = await pageFor('lab');
  const b64 = await page.evaluate(async (u) => {
    const r = await fetch(u); if (!r.ok) throw new Error('report ' + r.status);
    const bytes = new Uint8Array(await r.arrayBuffer()); let s = ''; for (const b of bytes) s += String.fromCharCode(b); return btoa(s);
  }, `/admin/results/${REPORT_ID}`);
  const pdf = path.join(OUT, 'sample-report.pdf');
  fs.writeFileSync(pdf, Buffer.from(b64, 'base64'));
  const base = path.join(OUT, 'sample-report');
  try {
    execFileSync('pdftoppm', ['-jpeg', '-jpegopt', 'quality=82', '-r', '110', '-f', '1', '-l', '1', '-singlefile', pdf, base]);
  } catch {
    execFileSync('sips', ['-s', 'format', 'jpeg', pdf, '--out', base + '.jpg']);
  }
  fs.rmSync(pdf);
  console.log('✓ sample report');
  return { name: 'sample-report', url: `/results/${REPORT_ID}/download`, chunks: [base + '.jpg'], labels: [[]] };
}

const shots = [];
for (const [n, u, a] of SCREENS) shots.push(await shoot(n, u, a));
shots.push(await sampleReport());
for (const [n, u, a] of MOBILE) shots.push(await shoot(n, u, a, { width: 390, height: 844, mobile: true }));
fs.writeFileSync(path.join(OUT, 'chunks.json'), JSON.stringify(Object.fromEntries(shots.map((s) => [s.name, s])), null, 1));
await browser.close();
console.log('done');
