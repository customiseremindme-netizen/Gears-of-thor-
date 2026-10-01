/**
 * Scroll-linked effects. Each [data-scrub] element gets a --p custom property
 * from 0 to 1 that CSS uses for transforms and clip-paths:
 * - data-scrub="sticky": progress through a tall section with a sticky inner
 *   panel (the signature image opening to full width).
 * - data-scrub="pass": progress while the element passes through the viewport
 *   (membership photo, closing line).
 * Only elements near the viewport are updated, once per animation frame.
 */
export function initScrub(reduceMotion: boolean) {
  const elements = Array.from(document.querySelectorAll<HTMLElement>('[data-scrub]'))
  if (reduceMotion || !elements.length) return
  const active = new Set<HTMLElement>()
  let ticking = false

  const progress = (el: HTMLElement) => {
    const rect = el.getBoundingClientRect()
    const vh = window.innerHeight
    let p: number
    if (el.dataset.scrub === 'sticky') {
      const range = rect.height - vh
      p = range > 0 ? -rect.top / range : 1
    } else {
      p = (vh - rect.top) / (vh + rect.height)
    }
    return Math.min(1, Math.max(0, p))
  }
  const apply = (el: HTMLElement) => el.style.setProperty('--p', progress(el).toFixed(4))

  const update = () => {
    ticking = false
    active.forEach(apply)
  }
  const schedule = () => {
    if (!ticking) {
      ticking = true
      requestAnimationFrame(update)
    }
  }

  const observer = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        const el = entry.target as HTMLElement
        if (entry.isIntersecting) active.add(el)
        else {
          active.delete(el)
          apply(el) // settle at 0 or 1 when leaving
        }
      }
      schedule()
    },
    { rootMargin: '15% 0px 15% 0px' },
  )
  elements.forEach((el) => {
    apply(el)
    observer.observe(el)
  })
  window.addEventListener('scroll', schedule, { passive: true })
  window.addEventListener('resize', () => {
    elements.forEach(apply)
  })
}
