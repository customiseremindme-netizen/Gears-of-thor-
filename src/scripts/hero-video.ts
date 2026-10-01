/** Plays the optional hero video (muted, looping) unless reduced motion is preferred. */
export function initHeroVideo(reduceMotion: boolean) {
  const video = document.querySelector<HTMLVideoElement>('[data-hero-video]')
  if (!video || reduceMotion) return
  video.preload = 'auto'
  const play = () => video.play().catch(() => {})
  play()
  // Pause while the hero is off screen to save battery.
  new IntersectionObserver(([entry]) => (entry.isIntersecting ? play() : video.pause())).observe(video)
}
