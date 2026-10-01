<?php
declare(strict_types=1);

namespace Got;

/**
 * The owner checklist: business details still to confirm and content still
 * to add. Computed from the draft content, so it updates as you work.
 */
final class Checklist
{
    /** Items the owner ticks by hand (stored in settings). */
    public const MANUAL = [
        'check.contact' => 'Address and phone number checked',
        'check.training' => 'Training options confirmed',
        'check.privacy' => 'Privacy notice reviewed',
        'check.whatsapp_skip' => 'Not using WhatsApp for enquiries',
        'check.facilities_skip' => 'No facility labels needed',
        'check.membership_skip' => 'Keep membership as enquiry-only (no prices)',
    ];

    public static function items(): array
    {
        $c = Content::load('draft');
        $site = Site::fromContent($c, true);
        $tick = static fn (string $key): bool => (bool) Settings::get($key, false);
        $photos = count($site->galleryPhotos());

        $items = [
            // Needed before launch
            [
                'key' => 'hours', 'level' => 'required',
                'title' => 'Confirm your opening hours',
                'detail' => 'The listed hours are loaded but hidden until you confirm them. Visitors are asked to call until then.',
                'done' => !empty($c['hours']['confirmed']),
                'link' => '/admin/content/business#group-opening-hours', 'action' => 'Check hours',
            ],
            [
                'key' => 'contact', 'level' => 'required',
                'title' => 'Check the address and phone number',
                'detail' => 'Currently: ' . $site->addressText() . ' · ' . $site->phone(),
                'done' => $tick('check.contact'),
                'tick' => 'check.contact', 'link' => '/admin/content/business', 'action' => 'Edit',
            ],
            [
                'key' => 'training', 'level' => 'required',
                'title' => 'Confirm your training options',
                'detail' => 'Showing: ' . Text::listing(array_map(static fn ($i) => s($i['title']), $site->trainingItems()))
                    . '. Switch on extra options (functional, personal training, nutrition) only if you offer them.',
                'done' => $tick('check.training'),
                'tick' => 'check.training', 'link' => '/admin/content/training', 'action' => 'Review',
            ],
            [
                'key' => 'privacy', 'level' => 'required',
                'title' => 'Read and approve the privacy notice',
                'detail' => 'Make sure it matches how your team handles enquiries.',
                'done' => $tick('check.privacy'),
                'tick' => 'check.privacy', 'link' => '/admin/content/privacy', 'action' => 'Read',
            ],
            [
                'key' => 'search', 'level' => 'required',
                'title' => 'Let search engines find the site',
                'detail' => 'Switch off “Hide the website from search engines” when you launch.',
                'done' => empty($c['seo']['hide_from_search']),
                'link' => '/admin/content/seo', 'action' => 'Open',
            ],

            // Recommended
            [
                'key' => 'hero', 'level' => 'recommended',
                'title' => 'Add a hero photo or video of your gym',
                'detail' => 'Until then the top of the page shows your logo on a dark background.',
                'done' => $site->media($c['hero']['image'] ?? null) !== null || $site->media($c['hero']['video'] ?? null, 'video') !== null,
                'link' => '/admin/content/hero', 'action' => 'Add',
            ],
            [
                'key' => 'gallery', 'level' => 'recommended',
                'title' => 'Add at least 6 photos of the gym',
                'detail' => $photos === 0
                    ? 'The Gym section and its menu link stay hidden until you add photos. Your Instagram photos are a good start.'
                    : "{$photos} photo" . ($photos === 1 ? '' : 's') . ' added so far.',
                'done' => $photos >= 6,
                'link' => '/admin/content/gym', 'action' => 'Add photos',
            ],
            [
                'key' => 'statement', 'level' => 'recommended',
                'title' => 'Add a photo to the About section',
                'detail' => 'Without one, the section shows a typographic “Gears Of Thor” design.',
                'done' => $site->media($c['statement']['image'] ?? null) !== null,
                'link' => '/admin/content/statement', 'action' => 'Add',
            ],
            [
                'key' => 'maps', 'level' => 'recommended',
                'title' => 'Paste your Google Maps link',
                'detail' => 'Gives visitors exact directions to the gym instead of an address search.',
                'done' => s($c['business']['maps_url'] ?? '') !== '',
                'link' => '/admin/content/business', 'action' => 'Add link',
            ],
            [
                'key' => 'notifications', 'level' => 'recommended',
                'title' => 'Get an email when an enquiry arrives',
                'detail' => 'Optional, free, and uses your hosting’s email.',
                'done' => (bool) Settings::get('notify_enabled', false),
                'link' => '/admin/settings', 'action' => 'Set up',
                'owner' => true,
            ],

            // Optional — when you have real information
            [
                'key' => 'whatsapp', 'level' => 'optional',
                'title' => 'WhatsApp button',
                'detail' => 'Add your WhatsApp number and confirm it, or tick if you don’t use WhatsApp for enquiries.',
                'done' => $site->whatsappHref() !== null || $tick('check.whatsapp_skip'),
                'tick' => 'check.whatsapp_skip', 'link' => '/admin/content/business', 'action' => 'Add',
            ],
            [
                'key' => 'facilities', 'level' => 'optional',
                'title' => 'Confirm facilities (AC, lockers, parking…)',
                'detail' => 'Each facility label appears only after you switch it on.',
                'done' => $site->facilities() !== [] || $tick('check.facilities_skip'),
                'tick' => 'check.facilities_skip', 'link' => '/admin/content/gym#group-facilities', 'action' => 'Review',
            ],
            [
                'key' => 'membership', 'level' => 'optional',
                'title' => 'Membership plans and prices',
                'detail' => 'Add real plans and prices, or tick to keep a simple enquiry panel.',
                'done' => $site->plans() !== [] || $tick('check.membership_skip'),
                'tick' => 'check.membership_skip', 'link' => '/admin/content/membership', 'action' => 'Add plans',
            ],
            [
                'key' => 'coaches', 'level' => 'optional',
                'title' => 'Introduce your coaches',
                'detail' => 'Real photos, names, roles and checked qualifications only.',
                'done' => $site->coaches() !== [],
                'link' => '/admin/content/coaches', 'action' => 'Add',
            ],
            [
                'key' => 'reviews', 'level' => 'optional',
                'title' => 'Add genuine member reviews',
                'detail' => 'With the member’s permission and where it was posted.',
                'done' => $site->reviews() !== [],
                'link' => '/admin/content/reviews', 'action' => 'Add',
            ],
            [
                'key' => 'stories', 'level' => 'optional',
                'title' => 'Share member stories',
                'detail' => 'Only with the member’s written consent.',
                'done' => $site->stories() !== [],
                'link' => '/admin/content/stories', 'action' => 'Add',
            ],
            [
                'key' => 'turnstile', 'level' => 'optional',
                'title' => 'Extra spam protection (Cloudflare Turnstile)',
                'detail' => 'Built-in spam filters are already on. Add Turnstile only if spam gets through.',
                'done' => Turnstile::enabled(),
                'link' => '/admin/settings', 'action' => 'Set up',
                'owner' => true,
            ],
        ];

        return $items;
    }

    /** @return array{done: int, total: int} for the required + recommended items */
    public static function progress(array $items): array
    {
        $relevant = array_filter($items, static fn ($i) => $i['level'] !== 'optional');
        return [
            'done' => count(array_filter($relevant, static fn ($i) => $i['done'])),
            'total' => count($relevant),
        ];
    }
}
