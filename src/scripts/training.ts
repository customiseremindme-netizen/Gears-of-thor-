/** Training section (desktop): the sticky image follows the description in the middle of the screen. */
export function initTraining() {
  const root = document.querySelector<HTMLElement>('[data-training]')
  if (!root) return
  const items = Array.from(root.querySelectorAll<HTMLElement>('[data-training-item]'))
  const shots = Array.from(root.querySelectorAll<HTMLElement>('[data-shot]'))
  const current = root.querySelector<HTMLElement>('[data-training-current]')
  let active = 0

  const setActive = (index: number) => {
    if (index === active) return
    active = index
    items.forEach((el, i) => el.classList.toggle('is-active', i === index))
    // Earlier images stay underneath, so moving forward wipes the next image
    // up over the last one and moving back wipes it away again.
    shots.forEach((el, i) => el.classList.toggle('is-shown', i <= index))
    if (current) current.textContent = String(index + 1).padStart(2, '0')
  }

  const observer = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (entry.isIntersecting) setActive(Number((entry.target as HTMLElement).dataset.trainingItem))
      }
    },
    { rootMargin: '-48% 0px -48% 0px' },
  )
  items.forEach((el) => observer.observe(el))
}
