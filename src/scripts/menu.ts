/** Full-screen mobile menu: opens and closes with animation, traps focus, closes on Escape. */
export function initMenu() {
  const toggle = document.querySelector<HTMLButtonElement>('[data-menu-toggle]')
  const menu = document.querySelector<HTMLElement>('[data-menu]')
  const label = document.querySelector<HTMLElement>('[data-menu-label]')
  if (!toggle || !menu) return
  const root = document.documentElement
  let open = false
  let closeTimer = 0

  const focusables = () =>
    Array.from(menu.querySelectorAll<HTMLElement>('a[href], button:not([disabled])')).concat(toggle)

  const setOpen = (next: boolean, { restoreFocus = true } = {}) => {
    if (next === open) return
    open = next
    window.clearTimeout(closeTimer)
    toggle.setAttribute('aria-expanded', String(open))
    toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu')
    if (label) label.textContent = open ? 'Close' : 'Menu'
    root.classList.toggle('menu-open', open)
    if (open) {
      menu.hidden = false
      void menu.offsetHeight // let the browser draw the closed state before animating
      menu.classList.add('is-open')
      menu.querySelector<HTMLElement>('a[href]')?.focus({ preventScroll: true })
    } else {
      menu.classList.remove('is-open')
      closeTimer = window.setTimeout(() => (menu.hidden = true), 700)
      if (restoreFocus) toggle.focus({ preventScroll: true })
    }
  }

  toggle.setAttribute('aria-label', 'Open menu')
  toggle.addEventListener('click', () => setOpen(!open))
  menu.addEventListener('click', (event) => {
    if ((event.target as HTMLElement).closest('a[href]')) setOpen(false, { restoreFocus: false })
  })
  document.addEventListener('keydown', (event) => {
    if (!open) return
    if (event.key === 'Escape') {
      setOpen(false)
      return
    }
    if (event.key !== 'Tab') return
    const items = focusables()
    const first = items[0]
    const last = items[items.length - 1]
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault()
      last.focus()
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault()
      first.focus()
    }
  })
  window.matchMedia('(min-width: 1024px)').addEventListener('change', (e) => {
    if (e.matches) setOpen(false, { restoreFocus: false })
  })
}
