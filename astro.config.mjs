// @ts-check
import { defineConfig } from 'astro/config'

/**
 * The public address is read from the hosting platform so links in search
 * results and social previews point to the live site:
 * - SITE_URL: set this once a custom domain is connected (optional).
 * - URL: provided automatically by Netlify.
 * - VERCEL_PROJECT_PRODUCTION_URL: provided automatically by Vercel.
 */
const site =
  process.env.SITE_URL ||
  process.env.URL ||
  (process.env.VERCEL_PROJECT_PRODUCTION_URL ? `https://${process.env.VERCEL_PROJECT_PRODUCTION_URL}` : undefined) ||
  'http://localhost:4321'

export default defineConfig({
  site,
  output: 'static',
  trailingSlash: 'ignore',
  build: { inlineStylesheets: 'auto', assets: '_astro' },
  image: { responsiveStyles: false },
  vite: { build: { assetsInlineLimit: 0 } },
  devToolbar: { enabled: false },
})
