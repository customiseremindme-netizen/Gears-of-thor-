/**
 * ALL WEBSITE TEXT AND CONTACT DETAILS LIVE HERE.
 *
 * Edit this file to change wording, links, contact details or navigation.
 * - Headings: "\n" starts a new line on screen; *word* shows a word in the accent colour.
 * - Contact details below are the publicly listed ones; they are marked for owner
 *   confirmation in docs/OWNER-CHECKLIST.md before the site is published on a
 *   custom domain.
 * - Do not add prices, reviews, ratings, member counts, trainer details or
 *   results unless the owner has supplied and confirmed them.
 */

const address = {
  street: '30A, First Floor, Trichy Road',
  locality: 'Palladam',
  region: 'Tamil Nadu',
  postalCode: '641664',
  country: 'IN',
}

const addressText = `${address.street}, ${address.locality}, ${address.region} ${address.postalCode}`

export const site = {
  brand: {
    name: 'GOT FITNEZZ',
    expanded: 'Gears Of Thor',
    logoAlt: 'GOT FITNEZZ — Gears Of Thor',
  },

  seo: {
    title: 'GOT FITNEZZ Palladam | Gears Of Thor Gym',
    description:
      'Build strength, find your rhythm and make fitness part of your everyday life at GOT FITNEZZ — Gears Of Thor, Trichy Road, Palladam.',
    themeColor: '#0B0B0A',
  },

  contact: {
    phoneDisplay: '+91 86086 11123',
    phoneHref: 'tel:+918608611123',
    instagramUrl: 'https://www.instagram.com/got_fitnezz/',
    instagramHandle: '@got_fitnezz',
    address,
    addressText,
    // Opens Google Maps directions to the listed address. Replace with the
    // gym's own Google Maps link once the owner confirms it.
    directionsUrl:
      'https://www.google.com/maps/dir/?api=1&destination=' +
      encodeURIComponent(`GOT FITNEZZ, ${addressText}`),
    // WhatsApp is not confirmed yet. Add the number here (e.g. '+91 86086 11123')
    // only after the owner confirms WhatsApp is monitored.
    whatsapp: null as string | null,
  },

  nav: [
    { label: 'About', href: '#about' },
    { label: 'Training', href: '#training' },
    { label: 'The Space', href: '#space' },
    { label: 'Membership', href: '#membership' },
    { label: 'Contact', href: '#contact' },
  ],
  navCta: { label: 'Enquire Now', href: '#contact' },

  hero: {
    eyebrow: 'GOT FITNEZZ · PALLADAM',
    headline: 'YOUR NEXT REP.\nYOUR NEXT *LEVEL.*',
    text: 'Build strength, find your rhythm and make fitness part of your everyday life at GOT FITNEZZ — Gears Of Thor.',
    primary: { label: 'Explore the Gym', href: '#space' },
    secondary: { label: 'Enquire About Membership', href: '#contact' },
    sideLabel: 'Gears Of Thor',
  },

  about: {
    index: '01',
    eyebrow: 'About',
    heading: 'A STRONGER ROUTINE\nSTARTS *HERE.*',
    text: 'Starting fresh or getting back into your routine? Take the next step at GOT FITNEZZ in Palladam. Explore the gym, speak with the team and find out how to get started.',
    link: { label: 'Speak with the team', href: '#contact' },
    imageCaption: 'Strength · Cardio · Gym training',
  },

  signature: {
    index: '02',
    words: 'GEARS OF *THOR*',
    caption: 'Palladam, Tamil Nadu',
  },

  training: {
    index: '03',
    eyebrow: 'Training',
    heading: 'PUT YOUR GOALS\nIN *MOTION.*',
    link: { label: 'Ask about training', href: '#contact' },
    items: [
      {
        title: 'Strength Training',
        text: 'Make resistance training part of your routine and work towards a stronger you.',
        image: 'training-strength',
      },
      {
        title: 'Cardio',
        text: 'Keep moving with workouts focused on stamina and everyday fitness.',
        image: 'training-cardio',
      },
      {
        title: 'Gym Training',
        text: 'Create space in your week for consistent training and steady progress.',
        image: 'training-gym',
      },
    ],
  },

  /**
   * Gallery. While the photos are not genuine GOT FITNEZZ photos, this section
   * uses the "MAKE TIME TO MOVE" wording. When the owner's real gym photos are
   * in place, set `genuine: true` to switch to "STEP INSIDE GOT."
   */
  gallery: {
    index: '04',
    eyebrow: 'The Space',
    genuine: false,
    genuineCopy: {
      heading: 'STEP INSIDE *GOT.*',
      text: 'Explore the space where your next workout begins.',
    },
    editorialCopy: {
      heading: 'MAKE TIME\nTO *MOVE.*',
      text: 'For a real look inside GOT FITNEZZ, see the latest from the gym on Instagram, or visit us on Trichy Road, Palladam.',
    },
    instagramLabel: 'See GOT on Instagram',
    directionsLabel: 'Get Directions',
    // Shown under the gallery while it does not hold genuine GOT FITNEZZ photos.
    editorialNote: 'Editorial imagery. Not GOT FITNEZZ premises, staff or members.',
    images: ['gallery-1', 'gallery-2', 'gallery-3', 'gallery-4', 'gallery-5', 'gallery-6'],
  },

  membership: {
    index: '05',
    eyebrow: 'Membership',
    heading: 'MAKE YOUR\nFIRST *MOVE.*',
    text: 'Ask the GOT FITNEZZ team about membership options, opening hours and visiting the gym.',
    cta: { label: 'Let’s Get Started', href: '#contact' },
  },

  contactSection: {
    index: '06',
    eyebrow: 'Contact',
    heading: 'FIND US IN\n*PALLADAM.*',
    text: 'Call or message the team about membership, opening hours and visiting the gym.',
    addressLabel: 'Address',
    phoneLabel: 'Phone',
    instagramLabel: 'Instagram',
    directionsLabel: 'Get Directions',
    callLabel: 'Call Now',
    instagramAction: 'Follow on Instagram',
    panelHeading: 'Speak with\nthe team',
  },

  closing: {
    line: 'SHOW UP FOR\n*YOURSELF.*',
    callLabel: 'Call the gym',
    enquireLabel: 'Enquire Now',
  },

  footer: {
    tagline: 'Gears Of Thor — Palladam, Tamil Nadu.',
    // Add a line here if stock photography is used anywhere on the site, e.g.
    // 'Some photography is editorial stock imagery and does not show GOT FITNEZZ premises, staff or members.'
    imageNote: '' as string,
  },

  mobileBar: { call: 'Call', enquire: 'Enquire' },
} as const

export type Site = typeof site
