/**
 * Generates the atmospheric placeholder images in src/assets/images/.
 *
 * These are abstract "light studies" (no people, no venue) that hold each
 * photo slot until the owner's real photos are added. To replace one, save
 * the real photo over the file with the same name (see docs/IMAGES.md).
 * Re-running this script overwrites ONLY files that are still placeholders
 * (listed in src/assets/images/placeholders.json).
 *
 *   npm run placeholders
 */
import sharp from 'sharp'
import { readFile, writeFile } from 'node:fs/promises'
import { existsSync } from 'node:fs'

const OUT = new URL('../src/assets/images/', import.meta.url)
const INK = '#0b0b0a'
const LIME = '#d8e12a'
const BONE = '#f2efe8'

const glow = (cx, cy, rx, ry, color, opacity, blur = 0) =>
  `<ellipse cx="${cx}" cy="${cy}" rx="${rx}" ry="${ry}" fill="${color}" opacity="${opacity}"${blur ? ` filter="url(#b${blur})"` : ''}/>`
const ring = (cx, cy, r, w, color, opacity, blur = 0) =>
  `<circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="${color}" stroke-width="${w}" opacity="${opacity}"${blur ? ` filter="url(#b${blur})"` : ''}/>`
const bar = (x, y, w, h, color, opacity, blur = 0, rot = 0) =>
  `<rect x="${x}" y="${y}" width="${w}" height="${h}" fill="${color}" opacity="${opacity}"${blur ? ` filter="url(#b${blur})"` : ''}${rot ? ` transform="rotate(${rot} ${x + w / 2} ${y + h / 2})"` : ''}/>`

function svg(w, h, body, { from = '#151513', to = INK, angle = 0 } = {}) {
  const blurs = [4, 10, 24, 48, 90, 160]
    .map((s) => `<filter id="b${s}" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="${s}"/></filter>`)
    .join('')
  return Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}">
  <defs>${blurs}
    <linearGradient id="base" gradientTransform="rotate(${angle} .5 .5)" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${from}"/><stop offset="1" stop-color="${to}"/></linearGradient>
    <radialGradient id="vig" cx=".5" cy=".5" r=".75"><stop offset=".55" stop-color="#000" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity=".75"/></radialGradient>
  </defs>
  <rect width="100%" height="100%" fill="url(#base)"/>
  ${body}
  <rect width="100%" height="100%" fill="url(#vig)"/>
</svg>`)
}

// Each slot: size and an abstract composition (proportional to w/h).
const slots = {
  hero: [2400, 1500, (w, h) => [
    glow(w * 0.78, -h * 0.1, w * 0.32, h * 0.7, BONE, 0.16, 160),
    ...Array.from({ length: 9 }, (_, i) => bar(w * (0.08 + i * 0.105), h * 0.05, w * 0.012, h * 0.95, '#2a2a26', 0.55 - i * 0.03, 4)),
    bar(0, h * 0.62, w, h * 0.02, '#3a3a33', 0.7, 10),
    ring(w * 0.72, h * 0.62, h * 0.24, h * 0.07, '#1d1d1a', 1),
    ring(w * 0.72, h * 0.62, h * 0.27, h * 0.012, LIME, 0.55, 10),
    ring(w * 0.72, h * 0.62, h * 0.27, h * 0.004, LIME, 0.9),
    glow(w * 0.6, h * 0.95, w * 0.5, h * 0.18, LIME, 0.06, 90),
  ].join(''), { from: '#1b1b18' }],
  'about-closeup': [1200, 1500, (w, h) => [
    glow(w * 0.15, h * 0.1, w * 0.6, h * 0.35, BONE, 0.2, 160),
    ...[0.46, 0.4, 0.33, 0.24, 0.12].map((r, i) => ring(w * 0.62, h * 0.58, w * r, w * 0.03, i % 2 ? '#262622' : '#1a1a17', 1)),
    ring(w * 0.62, h * 0.58, w * 0.48, w * 0.008, BONE, 0.35, 4),
    ring(w * 0.62, h * 0.58, w * 0.06, w * 0.02, LIME, 0.8, 4),
    glow(w * 0.62, h * 0.58, w * 0.08, w * 0.08, INK, 1),
  ].join('')],
  'signature-wide': [2400, 1300, (w, h) => [
    glow(w * 0.5, h * 0.58, w * 0.62, h * 0.12, BONE, 0.2, 90),
    glow(w * 0.5, h * 0.6, w * 0.34, h * 0.03, LIME, 0.35, 24),
    ...Array.from({ length: 14 }, (_, i) => bar(w * (0.02 + i * 0.072), h * 0.15, w * 0.006, h * 0.45, '#24241f', 0.8, 4)),
    bar(0, h * 0.6, w, h * 0.4, '#0d0d0c', 0.85),
    ...Array.from({ length: 7 }, (_, i) => bar(w * (0.1 + i * 0.13), h * 0.64 + i * 3, w * 0.07, h * 0.006, BONE, 0.08, 4)),
  ].join(''), { from: '#121210' }],
  'training-strength': [1200, 1500, (w, h) => [
    glow(w * 0.5, -h * 0.05, w * 0.45, h * 0.4, BONE, 0.18, 160),
    bar(-w * 0.1, h * 0.52, w * 1.2, h * 0.012, '#4a4a42', 0.9, 4),
    ring(w * 0.08, h * 0.525, h * 0.17, h * 0.05, '#1c1c19', 1),
    ring(w * 0.92, h * 0.525, h * 0.17, h * 0.05, '#1c1c19', 1),
    ring(w * 0.92, h * 0.525, h * 0.195, h * 0.006, LIME, 0.7, 4),
    glow(w * 0.5, h * 0.95, w * 0.6, h * 0.12, '#000', 0.6, 48),
  ].join('')],
  'training-cardio': [1200, 1500, (w, h) => [
    glow(w * 0.85, h * 0.2, w * 0.5, h * 0.3, BONE, 0.14, 160),
    ...Array.from({ length: 11 }, (_, i) => bar(-w * 0.2, h * (0.18 + i * 0.07), w * (0.7 + (i % 3) * 0.25), h * 0.006, i === 5 ? LIME : BONE, i === 5 ? 0.75 : 0.1 + (i % 4) * 0.04, i === 5 ? 4 : 10, -14)),
    glow(w * 0.3, h * 0.85, w * 0.5, h * 0.15, LIME, 0.05, 90),
  ].join('')],
  'training-gym': [1200, 1500, (w, h) => [
    glow(w * 0.5, h * 0.35, w * 0.55, h * 0.3, BONE, 0.13, 160),
    ...Array.from({ length: 4 }, (_, i) => bar(w * (0.12 + i * 0.25), h * 0.08, w * 0.03, h * 0.62, '#26261f', 1, 4)),
    ...Array.from({ length: 6 }, (_, i) => bar(w * 0.08, h * (0.16 + i * 0.09), w * 0.84, h * 0.008, '#33332d', 0.9)),
    bar(w * 0.12, h * 0.34, w * 0.78, h * 0.008, LIME, 0.65, 4),
    bar(0, h * 0.72, w, h * 0.28, '#0c0c0b', 0.92),
  ].join('')],
  membership: [2400, 1400, (w, h) => [
    glow(w * 0.68, h * 0.4, w * 0.36, h * 0.5, BONE, 0.17, 160),
    glow(w * 0.68, h * 0.45, w * 0.12, h * 0.18, LIME, 0.12, 90),
    bar(w * 0.35, h * 0.47, w * 0.65, h * 0.014, '#46463f', 0.9, 4),
    ring(w * 0.86, h * 0.475, h * 0.23, h * 0.06, '#1b1b18', 1),
    ring(w * 0.86, h * 0.475, h * 0.26, h * 0.005, BONE, 0.4, 4),
  ].join(''), { from: '#171714' }],
  'gallery-1': [1600, 1100, (w, h) => [
    glow(w * 0.3, h * 0.2, w * 0.4, h * 0.4, BONE, 0.16, 160),
    // A row of weight plates seen edge-on: thin upright slabs of different heights.
    ...[0.42, 0.36, 0.3, 0.24, 0.18].map((r, i) => bar(w * (0.3 + i * 0.045), h * (0.62 - r), w * 0.028, h * r * 2, '#1f1f1c', 1, 4)),
    bar(w * 0.12, h * 0.615, w * 0.76, h * 0.012, '#4a4a42', 0.9),
    bar(w * 0.3, h * 0.2, w * 0.003, h * 0.84, LIME, 0.7, 4),
  ].join('')],
  'gallery-2': [1100, 1450, (w, h) => [
    glow(w * 0.5, h * 0.05, w * 0.3, h * 0.6, BONE, 0.2, 160),
    bar(w * 0.47, 0, w * 0.06, h, '#22221e', 1, 4),
    bar(w * 0.2, h * 0.3, w * 0.6, h * 0.012, LIME, 0.6, 4),
    bar(w * 0.2, h * 0.3, w * 0.6, h * 0.004, LIME, 1),
  ].join('')],
  'gallery-3': [1100, 1450, (w, h) => [
    glow(w * 0.2, h * 0.75, w * 0.6, h * 0.3, LIME, 0.08, 160),
    glow(w * 0.7, h * 0.3, w * 0.5, h * 0.4, BONE, 0.12, 160),
    ...Array.from({ length: 8 }, (_, i) => bar(w * 0.1, h * (0.1 + i * 0.1), w * 0.8, h * 0.05, '#1c1c19', 1)),
    ...Array.from({ length: 8 }, (_, i) => bar(w * 0.1, h * (0.1 + i * 0.1), w * 0.8, h * 0.003, BONE, 0.22)),
  ].join('')],
  'gallery-4': [1600, 1100, (w, h) => [
    glow(w * 0.5, h * 0.5, w * 0.5, h * 0.25, BONE, 0.14, 160),
    ...Array.from({ length: 22 }, (_, i) => bar(w * (0.03 + i * 0.045), h * 0.25, w * 0.008, h * 0.5, i === 11 ? LIME : '#34342e', i === 11 ? 0.8 : 0.9, i === 11 ? 4 : 0)),
  ].join('')],
  'gallery-5': [1600, 1100, (w, h) => [
    glow(w * 0.85, h * 0.15, w * 0.45, h * 0.45, BONE, 0.16, 160),
    ring(w * 0.35, h * 0.55, h * 0.36, h * 0.1, '#1c1c19', 1),
    ring(w * 0.35, h * 0.55, h * 0.2, h * 0.06, '#24241f', 1),
    ring(w * 0.35, h * 0.55, h * 0.41, h * 0.006, BONE, 0.3, 4),
    bar(w * 0.35, h * 0.545, w * 0.8, h * 0.012, '#4a4a42', 0.9),
    glow(w * 0.35, h * 0.55, h * 0.04, h * 0.04, LIME, 0.8, 4),
  ].join('')],
  'gallery-6': [1100, 1450, (w, h) => [
    glow(w * 0.5, h * 0.4, w * 0.5, h * 0.5, BONE, 0.1, 160),
    ...Array.from({ length: 9 }, (_, i) => bar(-w * 0.3, h * (0.05 + i * 0.12), w * 1.6, h * 0.004, i === 4 ? LIME : BONE, i === 4 ? 0.7 : 0.12, 0, 32)),
  ].join('')],
}

async function render(name, [w, h, compose, opts]) {
  const base = sharp(svg(w, h, compose(w, h), opts)).removeAlpha()
  const grain = await sharp({ create: { width: w, height: h, channels: 3, noise: { type: 'gaussian', mean: 128, sigma: 26 } } })
    .png()
    .toBuffer()
  await base
    .composite([{ input: grain, blend: 'soft-light' }])
    .jpeg({ quality: 84, mozjpeg: true })
    .toFile(new URL(`${name}.jpg`, OUT).pathname)
}

const manifestUrl = new URL('placeholders.json', OUT)
const manifest = existsSync(manifestUrl) ? JSON.parse(await readFile(manifestUrl, 'utf8')) : Object.keys(slots)
for (const name of Object.keys(slots)) {
  if (existsSync(new URL(`${name}.jpg`, OUT)) && !manifest.includes(name)) {
    console.log(`skip ${name}.jpg (real photo)`)
    continue
  }
  await render(name, slots[name])
  console.log(`made ${name}.jpg`)
}
await writeFile(manifestUrl, JSON.stringify(manifest.filter((n) => n in slots), null, 2) + '\n')
