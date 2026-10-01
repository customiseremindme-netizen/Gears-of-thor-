import type { APIRoute } from 'astro'

export const GET: APIRoute = ({ site }) => {
  const isProduction = (process.env.CONTEXT ?? process.env.VERCEL_ENV ?? 'production') === 'production'
  const body = isProduction
    ? `User-agent: *\nAllow: /\n\nSitemap: ${new URL('/sitemap.xml', site)}\n`
    : 'User-agent: *\nDisallow: /\n'
  return new Response(body, { headers: { 'Content-Type': 'text/plain; charset=utf-8' } })
}
