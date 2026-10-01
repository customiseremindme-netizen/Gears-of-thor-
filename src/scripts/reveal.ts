/** Reveals headings, text and images as they scroll into view. */
export function initReveals(reduceMotion: boolean) {
  const elements = Array.from(document.querySelectorAll<HTMLElement>('[data-reveal]'))
  const showAll = () => elements.forEach((el) => el.classList.add('is-in'))
  // If the script arrived very late (slow connection), show everything rather
  // than hiding content the visitor may already be reading.
  if (reduceMotion || !('IntersectionObserver' in window) || performance.now() > 4000) {
    showAll()
    return
  }
  document.documentElement.classList.add('motion')
  const observer = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (!entry.isIntersecting) continue
        entry.target.classList.add('is-in')
        observer.unobserve(entry.target)
      }
    },
    { rootMargin: '0px 0px -8% 0px', threshold: 0.12 },
  )
  elements.forEach((el) => observer.observe(el))
}
