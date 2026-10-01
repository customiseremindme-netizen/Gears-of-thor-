# Images and video

## Slots

Every picture on the site has a named slot. Files live in
`src/assets/images/` and are resized to AVIF/WebP automatically at build.
Add `?slots` to the end of any preview address to see each slot labelled.

| Slot (file name) | Where | Shape and size | What suits it |
| --- | --- | --- | --- |
| `hero.jpg` | Top of the page, full screen | Landscape, 2400×1500 or larger | Cinematic, dark, room on the left for the headline |
| `about-closeup.jpg` | About | Portrait 4:5, 1200×1500 | Close-up of weights or equipment |
| `signature-wide.jpg` | Full-width scroll image | Landscape, 2400×1300 | Wide shot of a training floor, calm centre for the words |
| `training-strength.jpg` | Training 01 | Portrait 4:5, 1200×1500 | Strength training |
| `training-cardio.jpg` | Training 02 | Portrait 4:5, 1200×1500 | Cardio |
| `training-gym.jpg` | Training 03 | Portrait 4:5, 1200×1500 | Gym training |
| `membership.jpg` | Membership | Landscape, 2400×1400 | Atmospheric, room on the left for the heading |
| `gallery-1.jpg`, `gallery-4.jpg`, `gallery-5.jpg` | Gallery | Landscape, 1600×1100 | The space, equipment, details |
| `gallery-2.jpg`, `gallery-3.jpg`, `gallery-6.jpg` | Gallery | Portrait, 1100×1450 | The space, equipment, details |

Optional hero video: MP4 (and ideally WebM) in `public/media/`, 6–12 seconds,
under about 6 MB, no sound needed (it always plays muted). Set `heroVideo` in
`src/content/images.ts`.

## Replacing a placeholder (for Claude)

1. Save the photo over the slot file (same name, `.jpg`, longest side about
   2400 px for wide slots and 1600 px for others; strip location data).
2. In `src/content/images.ts`: write a real `alt` description, adjust
   `position` so the important part stays in view, set `placeholder: false`.
3. Remove the slot name from `src/assets/images/placeholders.json` (so
   `npm run placeholders` never overwrites it).
4. If all gallery photos are genuine GOT FITNEZZ photos, set
   `gallery.genuine: true` in `src/content/site.ts`.
5. If any stock photo is used, keep `gallery.genuine: false`, and add a line in
   `site.footer.imageNote` saying stock imagery does not show GOT FITNEZZ
   premises, staff or members.
6. Record the photo below. If the hero changed: `node scripts/make-og.mjs`.
7. `npm run build && npm run qa`, then check crops on desktop and phone.

## Source and licence record

| Slot | Source | Licence / permission | Notes |
| --- | --- | --- | --- |
| All slots | Generated placeholders (`scripts/make-placeholders.mjs`) | Created for this project, no restrictions | Abstract light studies; no people or premises shown |
| Logo | Supplied by the owner | Owner's own artwork | `src/assets/brand/logo.png`, original in `docs/brand/` |

Stock photos (for example Unsplash or Pexels licences) may be used commercially
without attribution, but must never be presented as GOT FITNEZZ's premises,
staff or members. Record the photographer and photo page link here.
