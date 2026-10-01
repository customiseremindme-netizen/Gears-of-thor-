/** Header background on scroll, hide on scroll down / show on scroll up, and the progress line. */
export function initHeader() {
  const header = document.querySelector<HTMLElement>('[data-header]')
  const bar = document.querySelector<HTMLElement>('.progress__bar')
  if (!header) return
  let lastY = window.scrollY
  let ticking = false

  const update = () => {
    ticking = false
    const y = window.scrollY
    header.classList.toggle('is-scrolled', y > 24)
    const busy = document.documentElement.classList.contains('menu-open') || header.contains(document.activeElement)
    if (busy || y < 160) header.classList.remove('is-hidden')
    else if (y > lastY + 6) header.classList.add('is-hidden')
    else if (y < lastY - 6) header.classList.remove('is-hidden')
    lastY = y
    if (bar) {
      const max = document.documentElement.scrollHeight - window.innerHeight
      bar.style.transform = `scaleX(${max > 0 ? Math.min(1, y / max).toFixed(4) : 0})`
    }
  }
  const schedule = () => {
    if (!ticking) {
      ticking = true
      requestAnimationFrame(update)
    }
  }
  window.addEventListener('scroll', schedule, { passive: true })
  window.addEventListener('resize', schedule)
  header.addEventListener('focusin', () => header.classList.remove('is-hidden'))
  update()
}
