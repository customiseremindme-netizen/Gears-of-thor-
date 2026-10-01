/** Accessible gallery lightbox using the native <dialog> element. */
export function initLightbox() {
  const dialog = document.querySelector<HTMLDialogElement>('[data-lightbox]')
  const items = Array.from(document.querySelectorAll<HTMLButtonElement>('[data-lightbox-item]'))
  if (!dialog || !items.length) return
  const img = dialog.querySelector<HTMLImageElement>('[data-lightbox-img]')!
  const count = dialog.querySelector<HTMLElement>('[data-lightbox-count]')!
  const caption = dialog.querySelector<HTMLElement>('[data-lightbox-caption]')!
  const supported = typeof dialog.showModal === 'function'
  let index = 0
  let opener: HTMLElement | null = null

  const show = (i: number) => {
    index = (i + items.length) % items.length
    const item = items[index]
    const src = item.dataset.full ?? ''
    img.classList.add('is-loading')
    img.onload = () => img.classList.remove('is-loading')
    img.src = src
    img.alt = item.dataset.alt ?? ''
    count.textContent = `${String(index + 1).padStart(2, '0')} / ${String(items.length).padStart(2, '0')}`
    caption.textContent = item.dataset.alt ?? ''
    // Warm up the neighbours so next/previous feel instant.
    for (const n of [index + 1, index - 1]) {
      const next = items[(n + items.length) % items.length]?.dataset.full
      if (next) new Image().src = next
    }
  }

  const open = (i: number, from: HTMLElement) => {
    if (!supported) {
      window.open(items[i].dataset.full, '_blank', 'noopener')
      return
    }
    opener = from
    show(i)
    dialog.showModal()
    document.documentElement.classList.add('lightbox-open')
    dialog.querySelector<HTMLElement>('[data-lightbox-close]')?.focus()
  }

  items.forEach((item, i) => item.addEventListener('click', () => open(i, item)))
  dialog.querySelector('[data-lightbox-close]')?.addEventListener('click', () => dialog.close())
  dialog.querySelector('[data-lightbox-prev]')?.addEventListener('click', () => show(index - 1))
  dialog.querySelector('[data-lightbox-next]')?.addEventListener('click', () => show(index + 1))
  dialog.addEventListener('click', (event) => {
    // A click on the dark backdrop (outside the picture and buttons) closes it.
    const target = event.target as HTMLElement
    if (target === dialog || target.classList.contains('lightbox__inner')) dialog.close()
  })
  dialog.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowRight') show(index + 1)
    else if (event.key === 'ArrowLeft') show(index - 1)
  })
  dialog.addEventListener('close', () => {
    document.documentElement.classList.remove('lightbox-open')
    opener?.focus({ preventScroll: true })
  })

  // Swipe left or right on touch screens.
  let startX = 0
  dialog.addEventListener('pointerdown', (e) => (startX = e.clientX), { passive: true })
  dialog.addEventListener('pointerup', (e) => {
    if (e.pointerType !== 'touch') return
    const dx = e.clientX - startX
    if (Math.abs(dx) > 50) show(index + (dx < 0 ? 1 : -1))
  })
}
