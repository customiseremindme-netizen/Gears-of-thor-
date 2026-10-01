/* End-to-end checks for the GOT FITNEZZ website and dashboard (run via tests/e2e/run.sh). */
const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8765';
const EMAIL = process.env.ADMIN_EMAIL;
const PASSWORD = process.env.ADMIN_PASSWORD;
const FIX = process.env.FIXTURES;
const SHOTS = process.env.SCREENSHOTS || '';

let passed = 0;
const failures = [];
function ok(condition, message) {
  if (condition) { passed++; return; }
  failures.push(message);
  console.log('  ✗ ' + message);
}
async function step(name, fn) {
  console.log('• ' + name);
  try { await fn(); } catch (e) { failures.push(name + ': ' + e.message); console.log('  ✗ ' + e.message.split('\n')[0]); }
}
const wait = ms => new Promise(r => setTimeout(r, ms));

async function shot(page, name) {
  if (!SHOTS) return;
  fs.mkdirSync(SHOTS, { recursive: true });
  await page.screenshot({ path: path.join(SHOTS, name + '.png') });
}

async function login(context, email = EMAIL, password = PASSWORD) {
  const page = await context.newPage();
  await page.goto(BASE + '/admin/login');
  await page.fill('#login-email', email);
  await page.fill('#login-password', password);
  await Promise.all([page.waitForNavigation(), page.click('button[type=submit]')]);
  return page;
}

async function fillEnquiry(page, data) {
  await page.fill('#enq-name', data.name);
  await page.fill('#enq-phone', data.phone);
  await page.selectOption('#enq-goal', { index: 1 });
  await page.selectOption('#enq-time', { index: 1 });
  if (data.message) await page.fill('#enq-message', data.message);
  await page.check('#enq-consent');
}

async function publish(page) {
  await page.goto(BASE + '/admin/preview');
  page.once('dialog', d => d.accept());
  const button = page.locator('form[action$="/admin/publish"] button').first();
  await Promise.all([page.waitForNavigation(), button.click()]);
}

(async () => {
  const browser = await chromium.launch();
  const errors = [];
  const watch = page => {
    page.on('pageerror', e => errors.push(page.url() + ' — ' + e.message));
    page.on('console', m => {
      // Expected network statuses (404 page, aborted request test, 422 validation) are checked explicitly above.
      if (m.type() === 'error' && !m.text().startsWith('Failed to load resource')) errors.push(page.url() + ' — ' + m.text());
    });
  };

  /* Public site ------------------------------------------------------------ */
  const visitor = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const site = await visitor.newPage();
  watch(site);

  await step('Home page loads with the brief’s copy', async () => {
    const res = await site.goto(BASE + '/', { waitUntil: 'networkidle' });
    ok(res.status() === 200, 'home returns 200');
    ok((await site.title()) === 'GOT FITNEZZ Palladam | Gears Of Thor Gym', 'page title');
    const h1 = (await site.innerText('h1')).replace(/\s+/g, ' ');
    ok(/BUILD STRENGTH\. ?BUILD YOURSELF\./.test(h1), 'hero headline: ' + h1);
    ok(await site.isVisible('text=GOT FITNEZZ · PALLADAM'), 'eyebrow');
    ok(await site.isVisible('a.btn:has-text("Enquire About Membership")'), 'primary CTA');
    ok(!(await site.isVisible('a:has-text("Explore the Gym")')), 'Explore the Gym hidden until photos exist');
    ok(await site.isVisible('text=SHOW UP.'), 'brand statement');
    ok(await site.isVisible('text=Strength Training'), 'training panels');
    ok(!(await site.isVisible('text=Functional Training')), 'unconfirmed training stays hidden');
    ok(await site.locator('#gym').count() === 0, 'gym section hidden without photos');
    ok(await site.locator('#coaches, #reviews, #stories').count() === 0, 'coaches/reviews/stories hidden');
    ok(await site.isVisible('text=Ask About Membership'), 'membership CTA');
    ok(await site.locator('.hours').count() === 0, 'unconfirmed hours are not shown');
    ok((await site.content()).includes('Please call'), 'FAQ asks visitors to call for hours');
    ok(!/free trial|crossfit/i.test(await site.innerText('body')), 'no free-trial or CrossFit claims');
    ok(await site.isVisible('text=MAKE YOUR NEXT'), 'closing line');
    const ld = JSON.parse(await site.locator('script[type="application/ld+json"]').textContent());
    ok(ld['@type'] === 'ExerciseGym' && ld.telephone === '+918608611123', 'structured data');
    ok(!ld.aggregateRating && !ld.review, 'no ratings in structured data');
    ok(!ld.openingHoursSpecification, 'no unconfirmed hours in structured data');
    ok(ld.address && ld.address.postalCode === '641664', 'address in structured data');
    const csp = res.headers()['content-security-policy'] || '';
    ok(csp.includes("default-src 'self'") && csp.includes("frame-ancestors 'self'"), 'content security policy');
    ok((await visitor.cookies()).length === 0, 'visitors get no cookies');
    await shot(site, 'desktop-home');
  });

  await step('No horizontal overflow on phone, tablet and desktop', async () => {
    for (const [w, h] of [[360, 740], [390, 844], [820, 1180], [1024, 768], [1440, 900], [1920, 1080]]) {
      await site.setViewportSize({ width: w, height: h });
      await site.goto(BASE + '/', { waitUntil: 'networkidle' });
      const overflow = await site.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
      ok(overflow <= 0, `no overflow at ${w}px (got ${overflow})`);
    }
    await site.setViewportSize({ width: 1440, height: 900 });
  });

  await step('Robots, sitemap, manifest, privacy and 404', async () => {
    const robots = await (await site.request.get(BASE + '/robots.txt')).text();
    ok(robots.includes('Disallow: /admin') && robots.includes('sitemap.xml'), 'robots.txt');
    const sitemap = await (await site.request.get(BASE + '/sitemap.xml')).text();
    ok(sitemap.includes('<urlset') && sitemap.includes('/privacy'), 'sitemap.xml');
    const manifest = await (await site.request.get(BASE + '/site.webmanifest')).json();
    ok(manifest.name.startsWith('GOT FITNEZZ') && manifest.icons.length === 2, 'web manifest');
    const privacy = await site.goto(BASE + '/privacy');
    ok(privacy.status() === 200 && (await site.isVisible('h1:has-text("Privacy notice")')), 'privacy page');
    ok((await site.innerText('.prose')).includes('+91 86086 11123'), 'privacy shortcut [phone] filled in');
    const missing = await site.goto(BASE + '/no-such-page');
    ok(missing.status() === 404, '404 status');
    for (const secret of ['/storage/config.php', '/app/bootstrap.php', '/docs/OWNER-GUIDE.md']) {
      const internal = await site.request.get(BASE + secret);
      ok([403, 404].includes(internal.status()), secret + ' is not reachable (' + internal.status() + ')');
    }
  });

  await step('Dashboard and enquiry data need a login', async () => {
    const res = await site.goto(BASE + '/admin/enquiries');
    ok(site.url().endsWith('/admin/login'), 'redirects to login');
    const list = await site.request.get(BASE + '/admin/media/list', { headers: { Accept: 'application/json' } });
    ok(list.status() === 401, 'JSON endpoints answer 401');
    const csv = await site.request.get(BASE + '/admin/enquiries/export', { maxRedirects: 0 });
    ok(csv.status() === 303, 'CSV export redirects to login');
    const forged = await site.request.post(BASE + '/admin/publish', { form: { _csrf: 'x' }, maxRedirects: 0 });
    ok(forged.status() === 303 && !(forged.headers()['location'] || '').includes('/admin/preview'), 'publish without login is refused');
  });

  /* Enquiry form ----------------------------------------------------------- */
  await step('Form shows clear errors and sends nothing when empty', async () => {
    await site.goto(BASE + '/#enquire', { waitUntil: 'networkidle' });
    let posted = false;
    site.on('request', r => { if (r.url().endsWith('/enquire') && r.method() === 'POST') posted = true; });
    await site.click('[data-submit]');
    ok(await site.isVisible('text=Please enter your name.'), 'name error');
    ok(await site.isVisible('text=Please enter your mobile number.'), 'phone error');
    ok(await site.isVisible('text=Please choose your fitness goal.'), 'goal error');
    ok(await site.isVisible('text=Please tick the box'), 'consent error');
    ok((await site.getAttribute('#enq-name', 'aria-invalid')) === 'true', 'aria-invalid set');
    ok(!posted, 'nothing posted');
    await site.fill('#enq-phone', '12345');
    await site.click('[data-submit]');
    ok(await site.isVisible('text=Please enter a valid 10-digit mobile number.'), 'invalid phone message');
  });

  await step('Successful enquiry is confirmed only after saving', async () => {
    await site.goto(BASE + '/#enquire', { waitUntil: 'networkidle' });
    await fillEnquiry(site, { name: 'Arun Kumar', phone: '98430 12345', message: 'Interested in morning strength sessions.' });
    await wait(3200);
    const [response] = await Promise.all([
      site.waitForResponse(r => r.url().endsWith('/enquire') && r.request().method() === 'POST'),
      site.click('[data-submit]'),
    ]);
    const body = await response.json();
    ok(response.status() === 200 && body.ok, 'server saved the enquiry');
    await site.waitForSelector('[data-form-success]:not([hidden])');
    ok((await site.textContent('[data-form-success]')).includes('Thanks for reaching out. Your enquiry has been received.'), 'success message');
    ok(await site.isHidden('[data-enquiry-form]'), 'form hidden after success');
    await shot(site, 'desktop-form-success');
    await site.click('[data-form-again]');
    ok(await site.isVisible('[data-enquiry-form]'), 'send another enquiry');
    ok((await site.inputValue('#enq-name')) === '', 'form reset');
  });

  await step('A network failure keeps the details and offers a retry', async () => {
    await site.goto(BASE + '/#enquire', { waitUntil: 'networkidle' });
    await fillEnquiry(site, { name: 'Priya S', phone: '+91 90030 55555', message: 'Evening batch?' });
    await wait(3200);
    await site.route('**/enquire', route => route.abort());
    await site.click('[data-submit]');
    await site.waitForSelector('[data-form-alert]:not([hidden])');
    ok((await site.innerText('[data-form-alert]')).includes('couldn’t send'), 'failure message');
    ok((await site.inputValue('#enq-name')) === 'Priya S' && (await site.inputValue('#enq-message')) === 'Evening batch?', 'details kept');
    ok(await site.isVisible('[data-form-retry]'), 'retry button');
    ok(await site.isHidden('[data-form-success]'), 'no false success');
    await site.unroute('**/enquire');
    await site.click('[data-form-retry]');
    await site.waitForSelector('[data-form-success]:not([hidden])');
    ok(true, 'retry succeeded');
  });

  await step('Server-side validation and spam filters', async () => {
    const token = await site.inputValue('[data-form-token]');
    const bad = await site.request.post(BASE + '/enquire', {
      headers: { Accept: 'application/json' },
      form: { _token: token, name: 'X', phone: '1', goal: '', time: '', consent: '' },
    });
    const badBody = await bad.json();
    ok(bad.status() === 422 && badBody.errors.phone && badBody.errors.consent, 'server rejects invalid data');
    const forged = await site.request.post(BASE + '/enquire', {
      headers: { Accept: 'application/json' },
      form: { _token: '123.abc.def', name: 'Bot', phone: '9876543210', goal: 'a', time: 'b', consent: '1' },
    });
    ok(forged.status() === 400, 'forged token rejected');
    const trap = await site.request.post(BASE + '/enquire', {
      headers: { Accept: 'application/json' },
      form: { _token: token, name: 'Spam Bot', phone: '9876500000', goal: 'a', time: 'b', consent: '1', hp_note: 'http://spam.example' },
    });
    ok(trap.status() === 200, 'honeypot submission answered quietly (stored as spam)');
  });

  await step('Without JavaScript the form still works', async () => {
    const plain = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 1280, height: 900 }, reducedMotion: 'reduce' });
    const p = await plain.newPage();
    await p.goto(BASE + '/');
    await p.fill('#enq-name', 'Kavya');
    await p.fill('#enq-phone', 'not a phone');
    await p.selectOption('#enq-goal', { index: 2 });
    await p.selectOption('#enq-time', { index: 2 });
    await p.check('#enq-consent');
    await wait(3200);
    await Promise.all([p.waitForNavigation(), p.click('[data-submit]')]);
    ok((await p.inputValue('#enq-name')) === 'Kavya', 'details kept after a server error');
    ok(await p.isVisible('text=Please enter a valid 10-digit mobile number.'), 'server error shown');
    await p.fill('#enq-phone', '9944332211');
    await Promise.all([p.waitForNavigation(), p.click('[data-submit]')]);
    ok(p.url().includes('enquiry=sent'), 'redirected after saving');
    ok((await p.textContent('[data-form-success]')).includes('Thanks for reaching out. Your enquiry has been received.') && await p.isVisible('[data-form-success]'), 'success shown');
    const fake = await p.goto(BASE + '/?enquiry=sent&ref=1.2.3#enquire');
    ok(await p.isHidden('[data-form-success]'), 'success is not shown for a fake receipt');
    await plain.close();
  });

  await step('Repeated sending is limited', async () => {
    const token = await site.inputValue('[data-form-token]');
    let last = null;
    for (let i = 0; i < 4; i++) {
      last = await site.request.post(BASE + '/enquire', {
        headers: { Accept: 'application/json' },
        form: { _token: token, name: 'Load Test', phone: '98765432' + (10 + i), goal: 'a', time: 'b', consent: '1' },
      });
    }
    ok(last.status() === 429, 'too many enquiries → 429 (got ' + last.status() + ')');
    const body = await last.json();
    ok(body.message.includes('+91 86086 11123'), 'rate-limit message offers the phone number');
  });

  /* Dashboard -------------------------------------------------------------- */
  const ownerContext = await browser.newContext({ viewport: { width: 1366, height: 900 } });
  let admin;

  await step('Owner can sign in (wrong password refused)', async () => {
    const bad = await ownerContext.newPage();
    await bad.goto(BASE + '/admin/login');
    await bad.fill('#login-email', EMAIL);
    await bad.fill('#login-password', 'wrong-password-123');
    await Promise.all([bad.waitForNavigation(), bad.click('button[type=submit]')]);
    ok(await bad.isVisible('text=was not recognised'), 'wrong password message');
    await bad.close();
    admin = await login(ownerContext);
    watch(admin);
    ok(admin.url().endsWith('/admin'), 'lands on overview');
    ok(await admin.isVisible('h1:has-text("Hello")'), 'greeting');
    const cookie = (await ownerContext.cookies()).find(c => c.name === 'gotfz_admin');
    ok(cookie && cookie.httpOnly && cookie.sameSite === 'Lax', 'secure session cookie');
  });

  await step('Enquiries: list, status, notes, spam, CSV', async () => {
    await admin.goto(BASE + '/admin/enquiries');
    ok(await admin.isVisible('a:has-text("Arun Kumar")'), 'enquiry listed');
    ok(await admin.isVisible('a:has-text("Kavya")'), 'no-JS enquiry listed');
    ok(!(await admin.isVisible('a:has-text("Spam Bot")')), 'spam kept out of the main list');
    await admin.goto(BASE + '/admin/enquiries?view=spam');
    ok(await admin.isVisible('a:has-text("Spam Bot")'), 'spam tab has the trapped enquiry');
    await admin.goto(BASE + '/admin/enquiries');
    await Promise.all([admin.waitForNavigation(), admin.click('a:has-text("Arun Kumar")')]);
    ok(await admin.isVisible('text=Interested in morning strength sessions.'), 'detail shows message');
    await admin.check('input[name=status][value=contacted]');
    await admin.fill('#notes', 'Called — visiting on Saturday.');
    await Promise.all([admin.waitForNavigation(), admin.click('button:has-text("Save")')]);
    ok(await admin.isVisible('text=Enquiry updated.'), 'saved');
    ok(await admin.isVisible('text=Status changed from New to Contacted'), 'history recorded');
    await admin.goto(BASE + '/admin/enquiries?status=contacted');
    ok(await admin.isVisible('a:has-text("Arun Kumar")'), 'filter by status');
    // quick status change from the list (auto-submits)
    await admin.goto(BASE + '/admin/enquiries?status=new');
    const select = admin.locator('select.status-select').first();
    await Promise.all([admin.waitForNavigation(), select.selectOption('follow_up')]);
    ok(await admin.isVisible('text=Status updated to Follow-up.'), 'quick status change');
    const csv = await admin.request.get(BASE + '/admin/enquiries/export');
    const text = await csv.text();
    ok(csv.headers()['content-type'].startsWith('text/csv'), 'CSV content type');
    ok(text.includes('Received (IST)') && text.includes('Arun Kumar') && text.includes('98430 12345'), 'CSV rows');
    ok(!text.includes('Spam Bot'), 'CSV excludes spam');
  });

  await step('Edits stay in the draft until published', async () => {
    await admin.goto(BASE + '/admin/content/hero');
    await admin.fill('[name="f[hero][headline]"]', "TRAIN HARDER.\nGET *STRONGER.*");
    await Promise.all([admin.waitForNavigation(), admin.click('button:has-text("Save draft")')]);
    ok(await admin.isVisible('text=Saved as a draft'), 'draft saved');
    ok(await admin.isVisible('.publish-state.has-changes'), 'unpublished changes indicator');
    const live = await visitor.newPage();
    await live.goto(BASE + '/');
    ok((await live.innerText('h1')).includes('BUILD STRENGTH.'), 'live site unchanged before publishing');
    const preview = await ownerContext.newPage();
    await preview.goto(BASE + '/?preview=1', { waitUntil: 'networkidle' });
    ok((await preview.innerText('h1')).includes('TRAIN HARDER.'), 'preview shows the draft');
    ok(await preview.isVisible('.preview-badge'), 'preview badge');
    const anonPreview = await live.goto(BASE + '/?preview=1');
    ok((await live.innerText('h1')).includes('BUILD STRENGTH.'), 'visitors cannot see drafts');
    await publish(admin);
    ok(await admin.isVisible('text=Published!'), 'published');
    await live.goto(BASE + '/');
    ok((await live.innerText('h1')).includes('TRAIN HARDER.'), 'live site updated after publishing');
    await live.close();
    await preview.close();
  });

  await step('Validation errors in the editor keep what was typed', async () => {
    await admin.goto(BASE + '/admin/content/business');
    await admin.fill('[name="f[business][instagram]"]', 'javascript:alert(1)');
    await admin.fill('[name="f[business][phone]"]', '123');
    await admin.click('button:has-text("Save draft")');
    await admin.waitForLoadState('networkidle');
    ok(await admin.isVisible('text=Nothing was saved yet'), 'error summary');
    ok(await admin.isVisible('text=Please enter a full web address'), 'url error');
    ok((await admin.inputValue('[name="f[business][phone]"]')) === '123', 'typed value kept');
  });

  await step('Confirming opening hours shows them everywhere', async () => {
    await admin.goto(BASE + '/admin/content/business');
    await admin.check('[name="f[hours][confirmed]"]', { force: true });
    await Promise.all([admin.waitForNavigation(), admin.click('button:has-text("Save draft")')]);
    await publish(admin);
    const p = await visitor.newPage();
    await p.goto(BASE + '/', { waitUntil: 'networkidle' });
    ok(await p.locator('.hours').count() === 1, 'hours table shown');
    ok((await p.innerText('.hours')).includes('Monday–Saturday'), 'hours grouped');
    ok(await p.isVisible('[data-open-status]'), 'open-now status shown');
    const ld = JSON.parse(await p.locator('script[type="application/ld+json"]').textContent());
    ok(Array.isArray(ld.openingHoursSpecification), 'hours in structured data');
    ok((await p.content()).includes('Monday–Saturday: 5:30–9:30 AM and 5:30–9:30 PM'), 'FAQ [hours] filled in');
    await p.close();
  });

  await step('Photo upload: library, gallery and hero', async () => {
    await admin.goto(BASE + '/admin/media');
    await admin.setInputFiles('[data-dropzone-input]', [path.join(FIX, 'test-landscape.jpg')]);
    await admin.waitForLoadState('networkidle');
    await admin.waitForTimeout(2500);
    await admin.goto(BASE + '/admin/media');
    ok(await admin.locator('.media-tile').count() >= 4, 'uploaded photo in the library');
    // a file that is not an image is refused
    const csrf = await admin.getAttribute('meta[name="csrf-token"]', 'content');
    const fake = await admin.request.post(BASE + '/admin/media/upload', {
      headers: { Accept: 'application/json', 'X-CSRF-Token': csrf },
      multipart: { _csrf: csrf, file: { name: 'fake.jpg', mimeType: 'image/jpeg', buffer: fs.readFileSync(path.join(FIX, 'fake.jpg')) } },
    });
    const fakeBody = await fake.json();
    ok(!fakeBody.ok && fakeBody.errors.length === 1, 'non-image refused');
    const noCsrf = await admin.request.post(BASE + '/admin/media/upload', {
      headers: { Accept: 'application/json' },
      multipart: { file: { name: 'a.jpg', mimeType: 'image/jpeg', buffer: fs.readFileSync(path.join(FIX, 'test-square.jpg')) } },
    });
    ok(noCsrf.status() === 403, 'upload without CSRF token refused');

    // gallery: upload several photos at once
    await admin.goto(BASE + '/admin/content/gym');
    await admin.setInputFiles('[data-bulk-upload]', [path.join(FIX, 'test-landscape.jpg'), path.join(FIX, 'test-portrait.jpg'), path.join(FIX, 'test-square.jpg')]);
    await admin.waitForFunction(() => Array.from(document.querySelectorAll('input[name^="f[gym][photos]"][name$="[image]"]')).filter(i => i.value).length >= 3, null, { timeout: 30000 });
    await Promise.all([admin.waitForNavigation(), admin.click('button:has-text("Save draft")')]);
    ok(await admin.isVisible('text=Saved as a draft'), 'gallery saved');

    // hero: choose from the library
    await admin.goto(BASE + '/admin/content/hero');
    await admin.click('[data-media-field] [data-media-choose] >> nth=0');
    await admin.waitForSelector('[data-picker-grid] button');
    await admin.click('[data-picker-grid] button >> nth=0');
    ok((await admin.inputValue('[name="f[hero][image]"]')) !== '', 'hero photo chosen');
    await Promise.all([admin.waitForNavigation(), admin.click('button:has-text("Save draft")')]);
    await publish(admin);

    const p = await visitor.newPage();
    watch(p);
    await p.goto(BASE + '/', { waitUntil: 'networkidle' });
    ok(await p.locator('.hero--media').count() === 1, 'hero uses the photo');
    ok(await p.isVisible('a.btn:has-text("Explore the Gym")'), 'Explore the Gym appears');
    ok(await p.isVisible('.site-nav a:has-text("The Gym")'), 'menu link appears');
    ok(await p.locator('.gallery__item').count() === 3, 'three gallery photos');
    const srcset = await p.getAttribute('.gallery__item source', 'srcset');
    ok(srcset && srcset.includes('.webp 480w'), 'responsive WebP images');
    await p.locator('#gym').scrollIntoViewIfNeeded();
    await p.waitForTimeout(800);
    await p.click('.gallery__open >> nth=0');
    await p.waitForSelector('dialog[open]');
    ok((await p.innerText('[data-lightbox-count]')) === '1 / 3', 'lightbox opens');
    await p.keyboard.press('ArrowRight');
    ok((await p.innerText('[data-lightbox-count]')) === '2 / 3', 'arrow key moves to next photo');
    await p.keyboard.press('Escape');
    ok(await p.locator('dialog[open]').count() === 0, 'Escape closes the lightbox');
    await shot(p, 'desktop-with-photos');
    await p.close();
  });

  await step('Sections can be hidden and reordered', async () => {
    await admin.goto(BASE + '/admin/content');
    await admin.uncheck('[name="on[strip]"]', { force: true });
    // move FAQ above contact using the arrow button
    await admin.click('button[aria-label="Move FAQ up"]');
    await Promise.all([admin.waitForNavigation(), admin.click('button:has-text("Save order")')]);
    await publish(admin);
    const p = await visitor.newPage();
    await p.goto(BASE + '/');
    ok(await p.locator('.strip').count() === 0, 'strip hidden');
    const order = await p.evaluate(() => Array.from(document.querySelectorAll('main > section')).map(s => s.id || s.className.split(' ')[0]));
    ok(order.indexOf('faq') < order.indexOf('contact'), 'FAQ moved above contact: ' + order.join(','));
    await p.close();
  });

  await step('Publish history can restore an earlier version', async () => {
    await admin.goto(BASE + '/admin/history');
    const rows = await admin.locator('table tbody tr').count();
    ok(rows >= 4, 'history has entries');
    admin.once('dialog', d => d.accept());
    await Promise.all([admin.waitForNavigation(), admin.locator('form[action*="/restore"] button').last().click()]);
    ok(await admin.isVisible('text=That version is now in your draft'), 'restored into draft');
    await admin.goto(BASE + '/admin/preview');
    admin.once('dialog', d => d.accept());
    await Promise.all([admin.waitForNavigation(), admin.click('form[action$="/admin/discard"] button')]);
    ok(await admin.isVisible('text=Unpublished changes were discarded'), 'discard works');
  });

  await step('Roles: staff only see enquiries', async () => {
    await admin.goto(BASE + '/admin/users');
    await admin.fill('#new-name', 'Front Desk');
    await admin.fill('#new-email', 'desk@example.com');
    await admin.selectOption('#new-role', 'staff');
    await admin.fill('#new-password', 'Desk-password-2026');
    await Promise.all([admin.waitForNavigation(), admin.click('button:has-text("Add person")')]);
    ok(await admin.isVisible('text=Account created for Front Desk'), 'staff account created');
    const staffContext = await browser.newContext();
    const staff = await login(staffContext, 'desk@example.com', 'Desk-password-2026');
    ok(staff.url().endsWith('/admin'), 'staff signed in');
    const content = await staff.goto(BASE + '/admin/content/hero');
    ok(content.status() === 403, 'staff cannot edit content');
    const settings = await staff.goto(BASE + '/admin/settings');
    ok(settings.status() === 403, 'staff cannot open settings');
    const list = await staff.goto(BASE + '/admin/enquiries');
    ok(list.status() === 200, 'staff can see enquiries');
    const exportRes = await staff.request.get(BASE + '/admin/enquiries/export');
    ok(exportRes.status() === 403, 'staff cannot export');
    await staffContext.close();
  });

  await step('Phone layout: menu, quick actions, reduced motion', async () => {
    const phone = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, reducedMotion: 'reduce' });
    const p = await phone.newPage();
    watch(p);
    await p.goto(BASE + '/', { waitUntil: 'networkidle' });
    await shot(p, 'phone-home');
    ok(await p.isVisible('[data-menu-toggle]'), 'menu button on phones');
    await p.click('[data-menu-toggle]');
    await p.waitForTimeout(400);
    ok((await p.getAttribute('[data-menu-toggle]', 'aria-expanded')) === 'true', 'menu opens');
    ok(await p.isVisible('.mobile-menu__info'), 'address and hours in the menu');
    await p.keyboard.press('Escape');
    ok((await p.getAttribute('[data-menu-toggle]', 'aria-expanded')) === 'false', 'Escape closes the menu');
    await p.evaluate(() => window.scrollTo(0, 1200));
    await p.waitForTimeout(500);
    ok(await p.locator('.mobile-bar.is-visible').count() === 1, 'Call / Enquire bar appears after scrolling');
    const tapTargets = await p.evaluate(() => Array.from(document.querySelectorAll('.mobile-bar a, .btn, .menu-toggle')).filter(el => el.offsetParent).every(el => el.getBoundingClientRect().height >= 44));
    ok(tapTargets, 'tap targets at least 44px tall');
    const revealed = await p.evaluate(() => Array.from(document.querySelectorAll('.reveal')).every(el => getComputedStyle(el).opacity === '1'));
    ok(revealed, 'reduced motion: content visible without animation');
    await phone.close();
  });

  await step('Sign out', async () => {
    await admin.goto(BASE + '/admin');
    await Promise.all([admin.waitForNavigation(), admin.click('button:has-text("Sign out")')]);
    ok(admin.url().endsWith('/admin/login'), 'signed out');
    const after = await admin.goto(BASE + '/admin/enquiries');
    ok(admin.url().endsWith('/admin/login'), 'session ended');
  });

  ok(errors.length === 0, 'no browser errors: ' + errors.join(' | '));
  await browser.close();
  console.log(`\n${passed} checks passed, ${failures.length} failed`);
  if (failures.length) {
    console.log(failures.map(f => ' - ' + f).join('\n'));
    process.exit(1);
  }
})();
