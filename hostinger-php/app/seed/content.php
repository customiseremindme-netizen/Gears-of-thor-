<?php
declare(strict_types=1);

/**
 * Default website content.
 *
 * Everything here can be changed in the dashboard. Information that still
 * needs the owner's confirmation is either switched off (opening hours,
 * WhatsApp, extra training options, facilities) or simply not invented
 * (prices, trainers, reviews, results). See docs/OWNER-CHECKLIST.md.
 */
return [
    'meta' => ['schema' => 1],

    'brand' => [
        'name' => 'GOT FITNEZZ',
        'expanded' => 'Gears Of Thor',
        'logo' => null,
        'logo_alt' => 'GOT FITNEZZ — Gears Of Thor logo',
        'icon' => null,
    ],

    // Accent sampled from the shield in the GOT FITNEZZ logo.
    'theme' => [
        'bg' => '#0B0B0B',
        'surface' => '#171717',
        'text' => '#F5F3ED',
        'muted' => '#B5B5B5',
        'accent' => '#D8E12A',
        'accent_text' => '#0B0B0B',
    ],

    'business' => [
        'phone' => '+91 86086 11123',
        'whatsapp' => '',
        'whatsapp_confirmed' => false,
        'email' => '',
        'street' => '30A, First Floor, Trichy Road',
        'locality' => 'Palladam',
        'region' => 'Tamil Nadu',
        'postal_code' => '641664',
        'maps_url' => '',
        'instagram' => 'https://www.instagram.com/got_fitnezz/',
        'facebook' => '',
        'youtube' => '',
    ],

    // Listed hours awaiting the owner's confirmation: they stay hidden on the
    // public website until "confirmed" is switched on in the dashboard.
    'hours' => [
        'confirmed' => false,
        'days' => [
            'mon' => [['open' => '05:30', 'close' => '09:30'], ['open' => '17:30', 'close' => '21:30']],
            'tue' => [['open' => '05:30', 'close' => '09:30'], ['open' => '17:30', 'close' => '21:30']],
            'wed' => [['open' => '05:30', 'close' => '09:30'], ['open' => '17:30', 'close' => '21:30']],
            'thu' => [['open' => '05:30', 'close' => '09:30'], ['open' => '17:30', 'close' => '21:30']],
            'fri' => [['open' => '05:30', 'close' => '09:30'], ['open' => '17:30', 'close' => '21:30']],
            'sat' => [['open' => '05:30', 'close' => '09:30'], ['open' => '17:30', 'close' => '21:30']],
            'sun' => [['open' => '07:00', 'close' => '09:30'], ['open' => '18:00', 'close' => '20:30']],
        ],
        'note' => '',
    ],

    'seo' => [
        'title' => 'GOT FITNEZZ Palladam | Gears Of Thor Gym',
        'description' => 'Explore GOT FITNEZZ in Palladam. Discover training options, view the gym and contact the team for membership details.',
        'share_image' => null,
        'hide_from_search' => false,
    ],

    'nav' => [
        'about' => 'About',
        'training' => 'Training',
        'gym' => 'The Gym',
        'membership' => 'Membership',
        'contact' => 'Contact',
        'cta' => 'Enquire Now',
        'mobile_call' => 'Call',
        'mobile_enquire' => 'Enquire',
    ],

    // Order of the sections on the page; "on" switches a section off entirely.
    'sections' => [
        ['id' => 'hero', 'on' => true],
        ['id' => 'statement', 'on' => true],
        ['id' => 'strip', 'on' => true],
        ['id' => 'training', 'on' => true],
        ['id' => 'gym', 'on' => true],
        ['id' => 'coaches', 'on' => true],
        ['id' => 'reviews', 'on' => true],
        ['id' => 'stories', 'on' => true],
        ['id' => 'membership', 'on' => true],
        ['id' => 'contact', 'on' => true],
        ['id' => 'faq', 'on' => true],
    ],

    'hero' => [
        'eyebrow' => 'GOT FITNEZZ · PALLADAM',
        'headline' => "BUILD STRENGTH.\nBUILD *YOURSELF.*",
        'text' => 'Make time for a stronger you. Explore training at GOT FITNEZZ — Gears Of Thor, Palladam.',
        'primary_label' => 'Enquire About Membership',
        'secondary_label' => 'Explore the Gym',
        'image' => null,
        'mobile_image' => null,
        'video' => null,
        'overlay' => 'strong',
        'scroll_label' => 'Scroll',
    ],

    'statement' => [
        'eyebrow' => 'About',
        'heading' => "SHOW UP.\nPUT IN THE *WORK.*",
        'text' => 'Your next step starts here. Discover a space for building strength, improving fitness and making movement part of your routine.',
        'detail' => 'Gears Of Thor',
        'image' => null,
    ],

    'strip' => [
        'words' => "STRENGTH\nCONSISTENCY\nPROGRESS",
    ],

    'training' => [
        'eyebrow' => 'Training',
        'heading' => "FIND YOUR WAY\nTO *TRAIN.*",
        'intro' => '',
        'cta_label' => 'Ask About Training',
        'items' => [
            [
                'title' => 'Strength Training',
                'text' => 'Make strength part of your routine with focused resistance training.',
                'image' => null,
                'visible' => true,
            ],
            [
                'title' => 'Cardio',
                'text' => 'Keep moving and work towards better stamina with cardio training.',
                'image' => null,
                'visible' => true,
            ],
            [
                'title' => 'Gym Training',
                'text' => 'Build a consistent workout routine around your fitness goals.',
                'image' => null,
                'visible' => true,
            ],
            // Drafts: switch on only after confirming GOT FITNEZZ offers them.
            [
                'title' => 'Functional Training',
                'text' => 'Train everyday movement patterns with varied, full-body workouts.',
                'image' => null,
                'visible' => false,
            ],
            [
                'title' => 'Personal Training',
                'text' => 'One-to-one sessions planned around your goals. Ask the team about availability.',
                'image' => null,
                'visible' => false,
            ],
            [
                'title' => 'Nutrition Guidance',
                'text' => 'Practical guidance on eating habits that support your training.',
                'image' => null,
                'visible' => false,
            ],
        ],
    ],

    'gym' => [
        'eyebrow' => 'The Gym',
        'heading' => "GET A FEEL\nFOR THE *SPACE.*",
        'intro' => '',
        'photos' => [],
        'facilities_heading' => 'At the gym',
        // Shown only after the owner confirms each one.
        'facilities' => [
            ['label' => 'Air-conditioned', 'visible' => false],
            ['label' => 'Lockers', 'visible' => false],
            ['label' => 'Parking', 'visible' => false],
            ['label' => 'Changing rooms', 'visible' => false],
            ['label' => 'Drinking water', 'visible' => false],
        ],
    ],

    'coaches' => [
        'eyebrow' => 'Coaches',
        'heading' => "TRAIN WITH\nTHE *TEAM.*",
        'intro' => '',
        'items' => [],
    ],

    'reviews' => [
        'eyebrow' => 'Member reviews',
        'heading' => "IN THEIR OWN\n*WORDS.*",
        'items' => [],
    ],

    'stories' => [
        'eyebrow' => 'Member stories',
        'heading' => "PROGRESS,\n*EARNED.*",
        'intro' => '',
        'disclaimer' => 'Shared with each member’s permission. Individual results vary.',
        'items' => [],
    ],

    'membership' => [
        'eyebrow' => 'Membership',
        'heading' => "YOUR NEXT CHAPTER\nSTARTS *HERE.*",
        'text' => 'Speak to the GOT FITNEZZ team about membership options, training and finding a routine that works for you.',
        'cta_label' => 'Ask About Membership',
        'image' => null,
        'plans' => [],
        'plans_note' => '',
        'plan_cta_label' => 'Ask about this plan',
    ],

    'contact' => [
        'eyebrow' => 'Visit',
        'heading' => "COME TRAIN\nIN *PALLADAM.*",
        'text' => '',
        'address_label' => 'Address',
        'phone_label' => 'Phone',
        'hours_label' => 'Opening hours',
        'social_label' => 'Follow',
        'directions_label' => 'Get Directions',
        'call_label' => 'Call Now',
        'whatsapp_label' => 'WhatsApp',
        'form' => [
            'heading' => 'Request a callback',
            'intro' => 'Leave your details and the GOT FITNEZZ team can call you back about membership and training.',
            'name_label' => 'Name',
            'phone_label' => 'Mobile number',
            'goal_label' => 'Fitness goal',
            'time_label' => 'Preferred contact time',
            'message_label' => 'Message (optional)',
            'goals' => "Build strength\nLose weight\nImprove stamina\nGeneral fitness\nNot sure yet",
            'times' => "Morning\nAfternoon\nEvening\nAny time",
            'consent' => 'I agree that GOT FITNEZZ may contact me about this enquiry.',
            'submit' => 'Request a Callback',
            'success' => 'Thanks for reaching out. Your enquiry has been received.',
        ],
    ],

    'faq' => [
        'eyebrow' => 'FAQ',
        'heading' => "GOOD TO\n*KNOW.*",
        'items' => [
            [
                'q' => 'How do I enquire about membership?',
                'a' => 'Use the callback form on this page or call [phone]. The GOT FITNEZZ team can talk you through membership options and training.',
                'visible' => true,
            ],
            [
                'q' => 'Where is GOT FITNEZZ located?',
                'a' => 'You’ll find us at [address]. Use “Get Directions” in the Visit section to open the route in Google Maps.',
                'visible' => true,
            ],
            [
                'q' => 'What are the opening hours?',
                'a' => '[hours]',
                'visible' => true,
            ],
            [
                'q' => 'Can I visit before joining?',
                'a' => 'Please call [phone] or send an enquiry, and the team will let you know how visits work.',
                'visible' => true,
            ],
            [
                'q' => 'What training options are available?',
                'a' => 'Training at GOT FITNEZZ includes [training]. To find the right fit for your goals, contact the team on [phone].',
                'visible' => true,
            ],
        ],
    ],

    'footer' => [
        'closing' => "MAKE YOUR NEXT\nREP *COUNT.*",
        'copyright' => 'GOT FITNEZZ',
        'privacy_label' => 'Privacy notice',
    ],

    'privacy' => [
        'title' => 'Privacy notice',
        'body' => <<<'MD'
This notice explains how GOT FITNEZZ (Gears Of Thor) handles the details you share through this website.

## What we collect

When you send an enquiry we collect your name, mobile number, fitness goal, preferred contact time and any message you write. We also record when the enquiry was sent and a scrambled (hashed) form of your internet address, which helps us block spam. This website does not use advertising or tracking cookies.

## Why we use it

We use your details only to respond to your enquiry about membership and training at GOT FITNEZZ. We do not sell your details or share them with other businesses for marketing.

## Who can see it

Enquiries are stored on the server that hosts this website. Only authorised GOT FITNEZZ staff who sign in to the website dashboard can view them.

## How long we keep it

We keep enquiries only for as long as we need them to follow up with you, and delete them when they are no longer needed.

## Your choices

To ask what information we hold about you, or to have it corrected or deleted, call us on [phone] or visit us at [address].

## Changes to this notice

We may update this notice from time to time. The latest version will always be on this page.
MD,
    ],
];
