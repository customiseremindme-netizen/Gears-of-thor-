/**
 * Visual and functional check of the built site (run `npm run build` first).
 *
 *   npm run qa            → screenshots in qa-output/ and a pass/fail summary
 *
 * Checks: no script errors or broken files, no sideways scrolling, headings fit,
 * images load, contact links work, the menu, lightbox and reduced-motion mode.
 */
import { spawn } from 'node:child_process'
import { mkdir, rm, readFile } from 'node:fs/promises'
import { chromium } from 'playwright'

const PORT = 4329
const BASE = `http://127.0.0.1:${PORT}`
const OUT = new URL('../qa-output/', import.meta.url).pathname
const problems = []
// Apply the same Content Security Policy the host sends, so blocked scripts or styles show up here.
const netlifyToml = await readFile(new URL('../netlify.toml', import.meta.url), 'utf8')
const CSP = netlifyToml.match(/Content-Security-Policy = "([^"]+)"/)?.[1]
const note = (msg) => problems.push(msg)

await rm(OUT, { recursive: true, force: true })
await mkdir(OUT, { recursive: true })

const astroBin = new URL('../node_modules/astro/bin/astro.mjs', import.meta.url).pathname
const server = spawn(process.execPath, [astroBin, 'preview', '--port', String(PORT), '--host', '127.0.0.1', '--ignore-lock'], { stdio: 'ignore' })
for (let i = 0; i < 60; i++) {
  try {
    if ((await fetch(BASE)).ok) break
  } catch {}
  await new Promise((r) => setTimeout(r, 500))
}

const launchOptions = process.env.PW_CHROMIUM ? { executablePath: process.env.PW_CHROMIUM } : {}
const browser = await chromium.launch(launchOptions)

async function visit(name, viewport, { reducedMotion = 'no-preference', isMobile = false } = {}) {
  const context = await browser.newContext({ viewport, isMobile, hasTouch: isMobile, reducedMotion, deviceScaleFactor: 1 })
  if (CSP) {
    await context.route(/\/(\?.*)?$|\.html$/, async (route) => {
      const response = await route.fetch()
      await route.fulfill({ response, headers: { ...response.headers(), 'content-security-policy': CSP.replace(' upgrade-insecure-requests', '') } })
    })
  }
  const page = await context.newPage()
  page.on('pageerror', (e) => note(`[${name}] script error: ${e.message}`))
  page.on('console', (m) => m.type() === 'error' && note(`[${name}] console error: ${m.text()}`))
  page.on('response', (r) => r.status() >= 400 && note(`[${name}] ${r.status()} ${r.url()}`))
  await page.goto(BASE + '/', { waitUntil: 'networkidle' })
  await page.waitForTimeout(2600)
  await page.screenshot({ path: `${OUT}${name}-01-hero.png` })

  const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)
  if (overflow > 0) note(`[${name}] page scrolls sideways by ${overflow}px`)
  const tooWide = await page.evaluate(() =>
    Array.from(document.querySelectorAll('.split .line__inner, .signature__words, .closing__line, .contact__phone'))
      .filter((el) => {
        const r = el.getBoundingClientRect()
        return r.width > window.innerWidth + 1
      })
      .map((el) => el.textContent?.trim()),
  )
  if (tooWide.length) note(`[${name}] text wider than the screen: ${tooWide.join(' | ')}`)

  // Scroll through the page in steps so reveals and scroll effects run.
  const height = await page.evaluate(() => document.documentElement.scrollHeight)
  const vh = viewport.height
  let shot = 2
  for (let y = 0; y < height; y += Math.round(vh * 0.8)) {
    await page.evaluate((top) => window.scrollTo(0, top), y)
    await page.waitForTimeout(900)
    if (y > 0) await page.screenshot({ path: `${OUT}${name}-${String(shot++).padStart(2, '0')}.png` })
  }

  const brokenImages = await page.evaluate(() =>
    Array.from(document.images)
      .filter((img) => img.complete && img.naturalWidth === 0 && img.getAttribute('src'))
      .map((img) => img.currentSrc || img.src),
  )
  if (brokenImages.length) note(`[${name}] images that did not load: ${brokenImages.join(', ')}`)

  const links = await page.evaluate(() => Array.from(document.querySelectorAll('a[href]')).map((a) => a.getAttribute('href')))
  for (const want of ['tel:+918608611123', 'https://www.instagram.com/got_fitnezz/']) {
    if (!links.includes(want)) note(`[${name}] missing link ${want}`)
  }
  if (!links.some((h) => h?.startsWith('https://www.google.com/maps/dir/'))) note(`[${name}] missing directions link`)
  const deadAnchors = await page.evaluate(() =>
    Array.from(document.querySelectorAll('a[href^="#"]'))
      .map((a) => a.getAttribute('href'))
      .filter((h) => h && h !== '#' && !document.querySelector(h)),
  )
  if (deadAnchors.length) note(`[${name}] links to missing sections: ${[...new Set(deadAnchors)].join(', ')}`)

  return { context, page }
}

// Desktop
{
  const { context, page } = await visit('desktop', { width: 1440, height: 900 })
  // Lightbox: open with keyboard, move with arrows, close with Escape, focus returns.
  await page.locator('[data-lightbox-item]').first().scrollIntoViewIfNeeded()
  await page.waitForTimeout(1200)
  await page.locator('[data-lightbox-item]').first().focus()
  await page.keyboard.press('Enter')
  await page.waitForTimeout(700)
  if (!(await page.locator('dialog[open]').count())) note('[desktop] lightbox did not open')
  await page.keyboard.press('ArrowRight')
  await page.waitForTimeout(600)
  const count = await page.locator('[data-lightbox-count]').textContent()
  if (!count?.startsWith('02')) note(`[desktop] lightbox arrow key did not move to image 2 (${count})`)
  await page.screenshot({ path: `${OUT}desktop-lightbox.png` })
  await page.keyboard.press('Escape')
  await page.waitForTimeout(400)
  if (await page.locator('dialog[open]').count()) note('[desktop] lightbox did not close with Escape')
  const focused = await page.evaluate(() => document.activeElement?.getAttribute('data-lightbox-item'))
  if (focused !== '0') note('[desktop] focus did not return to the gallery after closing')

  // Training: the sticky image changes with the active description.
  await page.locator('[data-training-item="2"]').scrollIntoViewIfNeeded()
  await page.evaluate(() => window.scrollBy(0, 1))
  await page.waitForTimeout(1500)
  const shown = await page.locator('.training__shot.is-shown').count()
  if (shown !== 3) note(`[desktop] training image did not advance (shown: ${shown})`)
  await page.screenshot({ path: `${OUT}desktop-training-3.png` })
  await context.close()
}

// Phone
{
  const { context, page } = await visit('mobile', { width: 390, height: 844 }, { isMobile: true })
  await page.evaluate(() => window.scrollTo(0, 0))
  await page.waitForTimeout(800)
  await page.locator('[data-menu-toggle]').click()
  await page.waitForTimeout(1100)
  await page.screenshot({ path: `${OUT}mobile-menu.png` })
  if ((await page.locator('[data-menu-toggle]').getAttribute('aria-expanded')) !== 'true') note('[mobile] menu did not open')
  await page.locator('[data-menu-link]').nth(1).click()
  await page.waitForTimeout(1200)
  if (await page.locator('.menu.is-open').count()) note('[mobile] menu did not close after choosing a link')
  await page.evaluate(() => window.scrollTo(0, 1600))
  await page.waitForTimeout(900)
  if (!(await page.locator('.mobile-bar.is-visible').count())) note('[mobile] Call / Enquire bar did not appear')
  await context.close()
}

// Tablet
{
  const { context } = await visit('tablet', { width: 834, height: 1112 }, { isMobile: true })
  await context.close()
}

// Reduced motion: everything visible without scrolling effects.
{
  const { context, page } = await visit('reduced', { width: 1440, height: 900 }, { reducedMotion: 'reduce' })
  const hidden = await page.evaluate(() =>
    Array.from(document.querySelectorAll('[data-reveal]')).filter((el) => getComputedStyle(el).opacity === '0').length,
  )
  if (hidden) note(`[reduced] ${hidden} elements stayed hidden with reduced motion`)
  await context.close()
}

await browser.close()
server.kill()

if (problems.length) {
  console.log(`\n${problems.length} problem(s):\n- ` + problems.join('\n- '))
  process.exitCode = 1
} else {
  console.log(`\nAll checks passed. Screenshots: ${OUT}`)
}
