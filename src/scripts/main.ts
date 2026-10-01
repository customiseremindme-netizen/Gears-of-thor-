/**
 * Motion and interaction for the GOT FITNEZZ site.
 * Everything here is progressive: without JavaScript the page is complete and
 * readable; with "reduce motion" switched on, nothing moves.
 */
import { initReveals } from './reveal'
import { initHeader } from './header'
import { initMenu } from './menu'
import { initScrub } from './scrub'
import { initTraining } from './training'
import { initLightbox } from './lightbox'
import { initMobileBar } from './mobile-bar'
import { initHeroVideo } from './hero-video'

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches

function start() {
  if (new URLSearchParams(location.search).has('slots')) document.documentElement.classList.add('show-slots')
  initReveals(reduceMotion)
  initHeader()
  initMenu()
  initScrub(reduceMotion)
  initTraining()
  initLightbox()
  initMobileBar()
  initHeroVideo(reduceMotion)
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true })
else start()
