/**
 * Creates public/og-image.jpg (1200×630), the picture shown when the site is
 * shared on WhatsApp, Facebook and similar. It uses the hero image, the logo
 * and the hero headline from src/content/site.ts.
 *
 *   node scripts/make-og.mjs     (run again after changing the hero image or headline)
 */
import { chromium } from 'playwright'
import sharp from 'sharp'
import { readFile } from 'node:fs/promises'

const root = new URL('../', import.meta.url)
const file = (p) => new URL(p, root).pathname
const dataUrl = async (p, type) => `data:${type};base64,${(await readFile(file(p))).toString('base64')}`

const siteSource = await readFile(file('src/content/site.ts'), 'utf8')
const headline = (siteSource.match(/headline: '([^']+)'/)?.[1] ?? 'YOUR NEXT REP.\\nYOUR NEXT *LEVEL.*')
  .split('\\n')
  .map((line) => line.replace(/\*([^*]+)\*/g, '<span class="a">$1</span>'))
  .join('<br>')

const hero = await sharp(file('src/assets/images/hero.jpg')).resize(1200, 630, { fit: 'cover' }).jpeg({ quality: 85 }).toBuffer()
const html = `<!doctype html><html><head><style>
@font-face{font-family:LG;src:url(${await dataUrl('node_modules/@fontsource/league-gothic/files/league-gothic-latin-400-normal.woff2', 'font/woff2')})}
@font-face{font-family:IS;src:url(${await dataUrl('node_modules/@fontsource-variable/instrument-sans/files/instrument-sans-latin-wght-normal.woff2', 'font/woff2')});font-weight:400 700}
html,body{margin:0;width:1200px;height:630px;overflow:hidden;background:#0b0b0a}
.bg{position:absolute;inset:0;background:url(data:image/jpeg;base64,${hero.toString('base64')}) center/cover}
.shade{position:absolute;inset:0;background:linear-gradient(90deg,rgba(11,11,10,.85),rgba(11,11,10,.2) 70%),linear-gradient(0deg,rgba(11,11,10,.7),transparent 50%)}
.logo{position:absolute;left:64px;top:52px;height:96px}
h1{position:absolute;left:64px;bottom:112px;margin:0;font:400 128px/.86 LG;color:#f2efe8;text-transform:uppercase}
.a{color:#d8e12a}
p{position:absolute;left:66px;bottom:56px;margin:0;font:600 17px/1 IS;letter-spacing:.24em;color:#f2efe8;text-transform:uppercase}
p span{color:#d8e12a}
</style></head><body><div class="bg"></div><div class="shade"></div>
<img class="logo" src="${await dataUrl('src/assets/brand/logo.png', 'image/png')}">
<h1>${headline}</h1><p>GOT FITNEZZ <span>·</span> Trichy Road, Palladam</p></body></html>`

const browser = await chromium.launch(process.env.PW_CHROMIUM ? { executablePath: process.env.PW_CHROMIUM } : {})
const page = await browser.newPage({ viewport: { width: 1200, height: 630 } })
await page.setContent(html, { waitUntil: 'load' })
await page.evaluate(() => document.fonts.ready)
const png = await page.screenshot({ type: 'png' })
await browser.close()
await sharp(png).jpeg({ quality: 86, mozjpeg: true }).toFile(file('public/og-image.jpg'))
console.log('Created public/og-image.jpg')
