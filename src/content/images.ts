/**
 * IMAGE SLOTS
 *
 * Every picture on the site comes from this list. Source files live in
 * src/assets/images/ and are resized and converted (AVIF/WebP) automatically
 * when the site is built.
 *
 * The current files are designed placeholders (abstract light studies). To use
 * a real photo: save it over the matching file in src/assets/images/ (same
 * name, .jpg), describe it in `alt`, set `placeholder: false`, and remove its
 * name from src/assets/images/placeholders.json. See docs/IMAGES.md.
 *
 * `position` chooses which part of the photo stays visible when it is cropped
 * (like CSS object-position: "50% 50%" is the centre, "70% 40%" right of centre).
 */
import type { ImageMetadata } from 'astro'
import hero from '../assets/images/hero.jpg'
import aboutCloseup from '../assets/images/about-closeup.jpg'
import signatureWide from '../assets/images/signature-wide.jpg'
import trainingStrength from '../assets/images/training-strength.jpg'
import trainingCardio from '../assets/images/training-cardio.jpg'
import trainingGym from '../assets/images/training-gym.jpg'
import membership from '../assets/images/membership.jpg'
import gallery1 from '../assets/images/gallery-1.jpg'
import gallery2 from '../assets/images/gallery-2.jpg'
import gallery3 from '../assets/images/gallery-3.jpg'
import gallery4 from '../assets/images/gallery-4.jpg'
import gallery5 from '../assets/images/gallery-5.jpg'
import gallery6 from '../assets/images/gallery-6.jpg'

export type SiteImage = {
  src: ImageMetadata
  /** Describe the photo for people using screen readers. '' only for decorative placeholders. */
  alt: string
  /** Which part stays visible when cropped. */
  position: string
  /** What belongs in this slot (shown on the preview with ?slots). */
  brief: string
  placeholder: boolean
}

export const images = {
  hero: { src: hero, alt: '', position: '62% 50%', brief: 'Hero · cinematic gym photo or video poster · landscape, 2400×1500 or larger', placeholder: true },
  'about-closeup': { src: aboutCloseup, alt: '', position: '60% 55%', brief: 'About · close-up of weights or equipment · portrait 4:5, 1200×1500', placeholder: true },
  'signature-wide': { src: signatureWide, alt: '', position: '50% 50%', brief: 'Signature · wide editorial photo · landscape, 2400×1300', placeholder: true },
  'training-strength': { src: trainingStrength, alt: '', position: '50% 50%', brief: 'Training · strength training · portrait 4:5, 1200×1500', placeholder: true },
  'training-cardio': { src: trainingCardio, alt: '', position: '50% 50%', brief: 'Training · cardio · portrait 4:5, 1200×1500', placeholder: true },
  'training-gym': { src: trainingGym, alt: '', position: '50% 50%', brief: 'Training · gym training · portrait 4:5, 1200×1500', placeholder: true },
  membership: { src: membership, alt: '', position: '65% 50%', brief: 'Membership · immersive photo · landscape, 2400×1400', placeholder: true },
  'gallery-1': { src: gallery1, alt: '', position: '50% 50%', brief: 'Gallery 1 · landscape 1600×1100', placeholder: true },
  'gallery-2': { src: gallery2, alt: '', position: '50% 50%', brief: 'Gallery 2 · portrait 1100×1450', placeholder: true },
  'gallery-3': { src: gallery3, alt: '', position: '50% 50%', brief: 'Gallery 3 · portrait 1100×1450', placeholder: true },
  'gallery-4': { src: gallery4, alt: '', position: '50% 50%', brief: 'Gallery 4 · landscape 1600×1100', placeholder: true },
  'gallery-5': { src: gallery5, alt: '', position: '50% 50%', brief: 'Gallery 5 · landscape 1600×1100', placeholder: true },
  'gallery-6': { src: gallery6, alt: '', position: '50% 50%', brief: 'Gallery 6 · portrait 1100×1450', placeholder: true },
} satisfies Record<string, SiteImage>

export type ImageKey = keyof typeof images

/**
 * Optional hero video (muted, looping, no sound). Put the files in
 * public/media/ and set the paths, for example:
 *   export const heroVideo = { mp4: '/media/hero.mp4', webm: '/media/hero.webm' }
 * The hero image above is used as the poster and for visitors who prefer
 * reduced motion. Keep clips short (6–12 s) and under about 6 MB.
 */
export const heroVideo: { mp4?: string; webm?: string } | null = null
