/** The Call / Enquire bar on phones: shown after the hero, hidden over the contact section. */
export function initMobileBar() {
  const bar = document.querySelector<HTMLElement>('[data-mobile-bar]')
  const hero = document.querySelector('.hero')
  const contact = document.querySelector('#contact')
  if (!bar || !hero || !('IntersectionObserver' in window)) return
  let heroVisible = true
  let contactVisible = false
  const update = () => bar.classList.toggle('is-visible', !heroVisible && !contactVisible)
  new IntersectionObserver(([entry]) => {
    heroVisible = entry.isIntersecting
    update()
  }, { rootMargin: '-30% 0px 0px 0px' }).observe(hero)
  if (contact) {
    new IntersectionObserver(([entry]) => {
      contactVisible = entry.isIntersecting
      update()
    }).observe(contact)
  }
}
