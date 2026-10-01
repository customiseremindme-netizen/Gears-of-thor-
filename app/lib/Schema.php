<?php
declare(strict_types=1);

namespace Got;

/**
 * Describes every editable part of the website for the dashboard.
 * Each page lists its fields; the dashboard builds its forms from this and
 * the sanitiser uses it to validate and clean everything that is saved.
 */
final class Schema
{
    private static ?array $mediaPaths = null;

    /** Website sections in the order they can appear (ids match content 'sections'). */
    public const SECTIONS = [
        'hero' => ['label' => 'Hero (top of the page)', 'anchor' => 'top', 'locked' => true],
        'statement' => ['label' => 'About — brand statement', 'anchor' => 'about'],
        'strip' => ['label' => 'Moving text strip', 'anchor' => ''],
        'training' => ['label' => 'Training', 'anchor' => 'training'],
        'gym' => ['label' => 'The Gym — photos', 'anchor' => 'gym'],
        'coaches' => ['label' => 'Coaches', 'anchor' => 'coaches'],
        'reviews' => ['label' => 'Member reviews', 'anchor' => 'reviews'],
        'stories' => ['label' => 'Member stories', 'anchor' => 'stories'],
        'membership' => ['label' => 'Membership', 'anchor' => 'membership'],
        'contact' => ['label' => 'Visit & contact form', 'anchor' => 'contact', 'locked' => true],
        'faq' => ['label' => 'FAQ', 'anchor' => 'faq'],
    ];

    private const TOKENS_HELP = 'Shortcuts: [phone], [address], [hours], [training] and [instagram] are replaced with your current details.';
    private const ACCENT_HELP = 'Press Enter for a new line. Put *stars* around words to show them in your accent colour.';

    public static function pages(): array
    {
        return [
            'hero' => [
                'title' => 'Hero',
                'menu' => 'sections',
                'intro' => 'The first thing visitors see at the top of the page.',
                'groups' => [
                    ['title' => 'Text and buttons', 'fields' => [
                        ['path' => 'hero.eyebrow', 'type' => 'text', 'label' => 'Small line above the headline', 'max' => 60],
                        ['path' => 'hero.headline', 'type' => 'textarea', 'label' => 'Headline', 'rows' => 2, 'max' => 90, 'required' => true, 'help' => self::ACCENT_HELP],
                        ['path' => 'hero.text', 'type' => 'textarea', 'label' => 'Supporting text', 'rows' => 3, 'max' => 300],
                        ['path' => 'hero.primary_label', 'type' => 'text', 'label' => 'Main button', 'max' => 40, 'required' => true, 'help' => 'Takes visitors to the callback form.'],
                        ['path' => 'hero.secondary_label', 'type' => 'text', 'label' => 'Second button', 'max' => 40, 'help' => 'Takes visitors to your gym photos. It appears once photos are added to “The Gym”.'],
                        ['path' => 'hero.scroll_label', 'type' => 'text', 'label' => 'Scroll hint', 'max' => 20],
                    ]],
                    ['title' => 'Background photo or video', 'intro' => 'Without a photo the hero shows your logo on a dark background. Use your own gym photos only.', 'fields' => [
                        ['path' => 'hero.image', 'type' => 'image', 'label' => 'Background photo', 'help' => 'A wide, sharp photo of your gym — at least 1920 pixels wide. Also shown on phones and while the video loads.'],
                        ['path' => 'hero.mobile_image', 'type' => 'image', 'label' => 'Photo for phones (optional)', 'help' => 'A tall (portrait) photo for small screens. If empty, the main photo is used.'],
                        ['path' => 'hero.video', 'type' => 'video', 'label' => 'Background video (optional)', 'help' => 'MP4, 10–20 seconds, no sound, ideally under 15 MB. Plays on larger screens only; phones show the photo. Add a background photo too.'],
                        ['path' => 'hero.overlay', 'type' => 'select', 'label' => 'Darkening over the photo', 'options' => ['medium' => 'Medium', 'strong' => 'Strong (recommended)', 'strongest' => 'Strongest — for bright photos'], 'help' => 'Keeps the headline easy to read.'],
                    ]],
                ],
            ],

            'statement' => [
                'title' => 'About',
                'menu' => 'sections',
                'intro' => 'A short brand statement with a large photo.',
                'groups' => [
                    ['title' => 'Text', 'fields' => [
                        ['path' => 'statement.eyebrow', 'type' => 'text', 'label' => 'Small label', 'max' => 40],
                        ['path' => 'statement.heading', 'type' => 'textarea', 'label' => 'Heading', 'rows' => 2, 'max' => 90, 'required' => true, 'help' => self::ACCENT_HELP],
                        ['path' => 'statement.text', 'type' => 'textarea', 'label' => 'Text', 'rows' => 4, 'max' => 600],
                        ['path' => 'statement.detail', 'type' => 'text', 'label' => 'Small brand detail', 'max' => 40, 'help' => 'Shown as a small signature, for example “Gears Of Thor”.'],
                    ]],
                    ['title' => 'Photo', 'fields' => [
                        ['path' => 'statement.image', 'type' => 'image', 'label' => 'Photo', 'help' => 'A strong photo of your gym. Portrait or landscape both work.'],
                    ]],
                ],
            ],

            'strip' => [
                'title' => 'Moving text strip',
                'menu' => 'sections',
                'intro' => 'A slow, pausable band of words between sections.',
                'groups' => [
                    ['title' => 'Words', 'fields' => [
                        ['path' => 'strip.words', 'type' => 'textarea', 'label' => 'Words', 'rows' => 4, 'max' => 200, 'help' => 'One word or short phrase per line.'],
                    ]],
                ],
            ],

            'training' => [
                'title' => 'Training',
                'menu' => 'sections',
                'intro' => 'Training options shown as large panels.',
                'notice' => 'Only switch on options GOT FITNEZZ actually offers. Please avoid the word “CrossFit” unless the gym is an official CrossFit affiliate — “functional training” is a safe alternative.',
                'groups' => [
                    ['title' => 'Section text', 'fields' => [
                        ['path' => 'training.eyebrow', 'type' => 'text', 'label' => 'Small label', 'max' => 40],
                        ['path' => 'training.heading', 'type' => 'textarea', 'label' => 'Heading', 'rows' => 2, 'max' => 90, 'required' => true, 'help' => self::ACCENT_HELP],
                        ['path' => 'training.intro', 'type' => 'textarea', 'label' => 'Introduction (optional)', 'rows' => 3, 'max' => 400],
                        ['path' => 'training.cta_label', 'type' => 'text', 'label' => 'Button', 'max' => 40, 'required' => true],
                    ]],
                    ['title' => 'Training options', 'fields' => [
                        ['path' => 'training.items', 'type' => 'list', 'item_label' => 'Training option', 'title_field' => 'title', 'max_items' => 9, 'fields' => [
                            ['key' => 'title', 'type' => 'text', 'label' => 'Name', 'max' => 50, 'required' => true],
                            ['key' => 'text', 'type' => 'textarea', 'label' => 'Short description', 'rows' => 3, 'max' => 240],
                            ['key' => 'image', 'type' => 'image', 'label' => 'Photo (optional)'],
                            ['key' => 'visible', 'type' => 'toggle', 'label' => 'Show on the website', 'default' => true],
                        ]],
                    ]],
                ],
            ],

            'gym' => [
                'title' => 'The Gym',
                'menu' => 'sections',
                'intro' => 'A gallery of your real gym: equipment, training areas and atmosphere. The section stays hidden until you add photos.',
                'groups' => [
                    ['title' => 'Section text', 'fields' => [
                        ['path' => 'gym.eyebrow', 'type' => 'text', 'label' => 'Small label', 'max' => 40],
                        ['path' => 'gym.heading', 'type' => 'textarea', 'label' => 'Heading', 'rows' => 2, 'max' => 90, 'required' => true, 'help' => self::ACCENT_HELP],
                        ['path' => 'gym.intro', 'type' => 'textarea', 'label' => 'Introduction (optional)', 'rows' => 3, 'max' => 400],
                    ]],
                    ['title' => 'Photos', 'intro' => 'Mix wide and tall photos. Visitors can open each photo in a larger view.', 'fields' => [
                        ['path' => 'gym.photos', 'type' => 'list', 'item_label' => 'Photo', 'title_field' => 'caption', 'max_items' => 40, 'bulk_upload' => 'image', 'fields' => [
                            ['key' => 'image', 'type' => 'image', 'label' => 'Photo', 'required' => true],
                            ['key' => 'caption', 'type' => 'text', 'label' => 'Caption (optional)', 'max' => 120],
                            ['key' => 'layout', 'type' => 'select', 'label' => 'Size in the gallery', 'options' => ['auto' => 'Automatic', 'wide' => 'Wide', 'tall' => 'Tall', 'standard' => 'Standard']],
                        ]],
                    ]],
                    ['title' => 'Facilities', 'intro' => 'Switch a facility on only after confirming it is available.', 'fields' => [
                        ['path' => 'gym.facilities_heading', 'type' => 'text', 'label' => 'Facilities heading', 'max' => 40],
                        ['path' => 'gym.facilities', 'type' => 'list', 'item_label' => 'Facility', 'title_field' => 'label', 'max_items' => 16, 'fields' => [
                            ['key' => 'label', 'type' => 'text', 'label' => 'Facility', 'max' => 40, 'required' => true],
                            ['key' => 'visible', 'type' => 'toggle', 'label' => 'Confirmed — show on the website', 'default' => false],
                        ]],
                    ]],
                ],
            ],

            'coaches' => [
                'title' => 'Coaches',
                'menu' => 'sections',
                'intro' => 'Introduce your trainers. The section stays hidden until at least one coach is added and switched on.',
                'notice' => 'Use real trainer photos only, and list qualifications only after checking them.',
                'groups' => [
                    ['title' => 'Section text', 'fields' => [
                        ['path' => 'coaches.eyebrow', 'type' => 'text', 'label' => 'Small label', 'max' => 40],
                        ['path' => 'coaches.heading', 'type' => 'textarea', 'label' => 'Heading', 'rows' => 2, 'max' => 90, 'required' => true, 'help' => self::ACCENT_HELP],
                        ['path' => 'coaches.intro', 'type' => 'textarea', 'label' => 'Introduction (optional)', 'rows' => 3, 'max' => 400],
                    ]],
                    ['title' => 'Coaches', 'fields' => [
                        ['path' => 'coaches.items', 'type' => 'list', 'item_label' => 'Coach', 'title_field' => 'name', 'max_items' => 12, 'fields' => [
                            ['key' => 'photo', 'type' => 'image', 'label' => 'Photo'],
                            ['key' => 'name', 'type' => 'text', 'label' => 'Name', 'max' => 60, 'required' => true],
                            ['key' => 'role', 'type' => 'text', 'label' => 'Role', 'max' => 60],
                            ['key' => 'qualifications', 'type' => 'textarea', 'label' => 'Qualifications (one per line)', 'rows' => 3, 'max' => 500],
                            ['key' => 'verified', 'type' => 'toggle', 'label' => 'I have checked these qualifications (needed to show them)', 'default' => false],
                            ['key' => 'visible', 'type' => 'toggle', 'label' => 'Show on the website', 'default' => true],
                        ]],
                    ]],
                ],
            ],

            'reviews' => [
                'title' => 'Member reviews',
                'menu' => 'sections',
                'intro' => 'Genuine reviews from members. A review appears only when the member’s permission is ticked.',
                'notice' => 'Never add made-up reviews. Copy reviews word for word and say where they were posted (for example, Google).',
                'groups' => [
                    ['title' => 'Section text', 'fields' => [
                        ['path' => 'reviews.eyebrow', 'type' => 'text', 'label' => 'Small label', 'max' => 40],
                        ['path' => 'reviews.heading', 'type' => 'textarea', 'label' => 'Heading', 'rows' => 2, 'max' => 90, 'required' => true, 'help' => self::ACCENT_HELP],
                    ]],
                    ['title' => 'Reviews', 'fields' => [
                        ['path' => 'reviews.items', 'type' => 'list', 'item_label' => 'Review', 'title_field' => 'name', 'max_items' => 12, 'fields' => [
                            ['key' => 'quote', 'type' => 'textarea', 'label' => 'Review text', 'rows' => 4, 'max' => 700, 'required' => true],
                            ['key' => 'name', 'type' => 'text', 'label' => 'Name shown (as the member agreed)', 'max' => 60, 'required' => true],
                            ['key' => 'source', 'type' => 'text', 'label' => 'Where it was posted', 'max' => 60, 'help' => 'For example: Google review.'],
                            ['key' => 'link', 'type' => 'url', 'label' => 'Link to the original review (optional)'],
                            ['key' => 'permission', 'type' => 'toggle', 'label' => 'The member gave permission to show this review here', 'default' => false],
                            ['key' => 'visible', 'type' => 'toggle', 'label' => 'Show on the website', 'default' => true],
                        ]],
                    ]],
                ],
            ],

            'stories' => [
                'title' => 'Member stories',
                'menu' => 'sections',
                'intro' => 'Owner-approved transformation stories. A story appears only when the member’s consent is ticked.',
                'notice' => 'Only share stories and photos the member has agreed to in writing. Do not promise results.',
                'groups' => [
                    ['title' => 'Section text', 'fields' => [
                        ['path' => 'stories.eyebrow', 'type' => 'text', 'label' => 'Small label', 'max' => 40],
                        ['path' => 'stories.heading', 'type' => 'textarea', 'label' => 'Heading', 'rows' => 2, 'max' => 90, 'required' => true, 'help' => self::ACCENT_HELP],
                        ['path' => 'stories.intro', 'type' => 'textarea', 'label' => 'Introduction (optional)', 'rows' => 3, 'max' => 400],
                        ['path' => 'stories.disclaimer', 'type' => 'text', 'label' => 'Note under the stories', 'max' => 160],
                    ]],
                    ['title' => 'Stories', 'fields' => [
                        ['path' => 'stories.items', 'type' => 'list', 'item_label' => 'Story', 'title_field' => 'name', 'max_items' => 8, 'fields' => [
                            ['key' => 'name', 'type' => 'text', 'label' => 'Member name (as they agreed)', 'max' => 60, 'required' => true],
                            ['key' => 'title', 'type' => 'text', 'label' => 'Headline', 'max' => 80],
                            ['key' => 'text', 'type' => 'textarea', 'label' => 'Their story', 'rows' => 4, 'max' => 900],
                            ['key' => 'before', 'type' => 'image', 'label' => '“Before” photo (optional)'],
                            ['key' => 'after', 'type' => 'image', 'label' => '“After” photo (optional)'],
                            ['key' => 'consent', 'type' => 'toggle', 'label' => 'The member gave written consent to share this story and photos', 'default' => false],
                            ['key' => 'visible', 'type' => 'toggle', 'label' => 'Show on the website', 'default' => true],
                        ]],
                    ]],
                ],
            ],

            'membership' => [
                'title' => 'Membership',
                'menu' => 'sections',
                'intro' => 'Without plans this is a single enquiry panel. Add plans only with real, current prices.',
                'groups' => [
                    ['title' => 'Section text', 'fields' => [
                        ['path' => 'membership.eyebrow', 'type' => 'text', 'label' => 'Small label', 'max' => 40],
                        ['path' => 'membership.heading', 'type' => 'textarea', 'label' => 'Heading', 'rows' => 2, 'max' => 90, 'required' => true, 'help' => self::ACCENT_HELP],
                        ['path' => 'membership.text', 'type' => 'textarea', 'label' => 'Text', 'rows' => 3, 'max' => 500],
                        ['path' => 'membership.cta_label', 'type' => 'text', 'label' => 'Button', 'max' => 40, 'required' => true],
                        ['path' => 'membership.image', 'type' => 'image', 'label' => 'Background photo (optional)'],
                    ]],
                    ['title' => 'Membership plans (optional)', 'intro' => 'Leave empty to show the enquiry panel only. Do not add offers or discounts that are not confirmed.', 'fields' => [
                        ['path' => 'membership.plans', 'type' => 'list', 'item_label' => 'Plan', 'title_field' => 'name', 'max_items' => 6, 'fields' => [
                            ['key' => 'name', 'type' => 'text', 'label' => 'Plan name', 'max' => 50, 'required' => true],
                            ['key' => 'price', 'type' => 'text', 'label' => 'Price', 'max' => 30, 'help' => 'For example: ₹1,500'],
                            ['key' => 'period', 'type' => 'text', 'label' => 'Duration', 'max' => 30, 'help' => 'For example: per month, 3 months'],
                            ['key' => 'features', 'type' => 'textarea', 'label' => 'What’s included (one per line)', 'rows' => 4, 'max' => 600],
                            ['key' => 'featured', 'type' => 'toggle', 'label' => 'Highlight this plan', 'default' => false],
                            ['key' => 'visible', 'type' => 'toggle', 'label' => 'Show on the website', 'default' => true],
                        ]],
                        ['path' => 'membership.plans_note', 'type' => 'textarea', 'label' => 'Note under the plans (optional)', 'rows' => 2, 'max' => 300],
                        ['path' => 'membership.plan_cta_label', 'type' => 'text', 'label' => 'Button on each plan', 'max' => 40],
                    ]],
                ],
            ],

            'contact' => [
                'title' => 'Visit & contact form',
                'menu' => 'sections',
                'intro' => 'Your address, phone and opening hours come from Business info. Here you can change the section text and the callback form.',
                'groups' => [
                    ['title' => 'Section text', 'fields' => [
                        ['path' => 'contact.eyebrow', 'type' => 'text', 'label' => 'Small label', 'max' => 40],
                        ['path' => 'contact.heading', 'type' => 'textarea', 'label' => 'Heading', 'rows' => 2, 'max' => 90, 'required' => true, 'help' => self::ACCENT_HELP],
                        ['path' => 'contact.text', 'type' => 'textarea', 'label' => 'Text (optional)', 'rows' => 3, 'max' => 400],
                    ]],
                    ['title' => 'Labels and buttons', 'fields' => [
                        ['path' => 'contact.address_label', 'type' => 'text', 'label' => 'Address label', 'max' => 30],
                        ['path' => 'contact.phone_label', 'type' => 'text', 'label' => 'Phone label', 'max' => 30],
                        ['path' => 'contact.hours_label', 'type' => 'text', 'label' => 'Opening hours label', 'max' => 30],
                        ['path' => 'contact.social_label', 'type' => 'text', 'label' => 'Social media label', 'max' => 30],
                        ['path' => 'contact.directions_label', 'type' => 'text', 'label' => 'Directions button', 'max' => 30],
                        ['path' => 'contact.call_label', 'type' => 'text', 'label' => 'Call button', 'max' => 30],
                        ['path' => 'contact.whatsapp_label', 'type' => 'text', 'label' => 'WhatsApp button', 'max' => 30],
                    ]],
                    ['title' => 'Callback form', 'fields' => [
                        ['path' => 'contact.form.heading', 'type' => 'text', 'label' => 'Form heading', 'max' => 60],
                        ['path' => 'contact.form.intro', 'type' => 'textarea', 'label' => 'Form introduction', 'rows' => 2, 'max' => 300, 'help' => 'Please do not promise a reply time unless you can always keep it.'],
                        ['path' => 'contact.form.name_label', 'type' => 'text', 'label' => 'Name field label', 'max' => 40, 'required' => true],
                        ['path' => 'contact.form.phone_label', 'type' => 'text', 'label' => 'Mobile field label', 'max' => 40, 'required' => true],
                        ['path' => 'contact.form.goal_label', 'type' => 'text', 'label' => 'Goal field label', 'max' => 40, 'required' => true],
                        ['path' => 'contact.form.goals', 'type' => 'textarea', 'label' => 'Fitness goal choices (one per line)', 'rows' => 5, 'max' => 600, 'required' => true],
                        ['path' => 'contact.form.time_label', 'type' => 'text', 'label' => 'Contact time field label', 'max' => 40, 'required' => true],
                        ['path' => 'contact.form.times', 'type' => 'textarea', 'label' => 'Contact time choices (one per line)', 'rows' => 4, 'max' => 400, 'required' => true],
                        ['path' => 'contact.form.message_label', 'type' => 'text', 'label' => 'Message field label', 'max' => 40, 'required' => true],
                        ['path' => 'contact.form.consent', 'type' => 'textarea', 'label' => 'Consent statement', 'rows' => 2, 'max' => 300, 'required' => true, 'help' => 'Visitors must tick this before sending. A link to your privacy notice is added automatically.'],
                        ['path' => 'contact.form.submit', 'type' => 'text', 'label' => 'Send button', 'max' => 40, 'required' => true],
                        ['path' => 'contact.form.success', 'type' => 'textarea', 'label' => 'Message after sending', 'rows' => 2, 'max' => 300, 'required' => true, 'help' => 'Shown only after the enquiry is safely saved. Avoid promising a reply time or a booking.'],
                    ]],
                ],
            ],

            'faq' => [
                'title' => 'FAQ',
                'menu' => 'sections',
                'intro' => 'Frequently asked questions, shown as expandable answers.',
                'groups' => [
                    ['title' => 'Section text', 'fields' => [
                        ['path' => 'faq.eyebrow', 'type' => 'text', 'label' => 'Small label', 'max' => 40],
                        ['path' => 'faq.heading', 'type' => 'textarea', 'label' => 'Heading', 'rows' => 2, 'max' => 90, 'required' => true, 'help' => self::ACCENT_HELP],
                    ]],
                    ['title' => 'Questions', 'fields' => [
                        ['path' => 'faq.items', 'type' => 'list', 'item_label' => 'Question', 'title_field' => 'q', 'max_items' => 24, 'fields' => [
                            ['key' => 'q', 'type' => 'text', 'label' => 'Question', 'max' => 160, 'required' => true],
                            ['key' => 'a', 'type' => 'textarea', 'label' => 'Answer', 'rows' => 4, 'max' => 1500, 'required' => true, 'help' => self::TOKENS_HELP],
                            ['key' => 'visible', 'type' => 'toggle', 'label' => 'Show on the website', 'default' => true],
                        ]],
                    ]],
                ],
            ],

            'footer' => [
                'title' => 'Menu & footer',
                'menu' => 'sections',
                'intro' => 'Menu labels, mobile buttons and the footer.',
                'groups' => [
                    ['title' => 'Menu', 'fields' => [
                        ['path' => 'nav.about', 'type' => 'text', 'label' => 'About link', 'max' => 24],
                        ['path' => 'nav.training', 'type' => 'text', 'label' => 'Training link', 'max' => 24],
                        ['path' => 'nav.gym', 'type' => 'text', 'label' => 'The Gym link', 'max' => 24],
                        ['path' => 'nav.membership', 'type' => 'text', 'label' => 'Membership link', 'max' => 24],
                        ['path' => 'nav.contact', 'type' => 'text', 'label' => 'Contact link', 'max' => 24],
                        ['path' => 'nav.cta', 'type' => 'text', 'label' => 'Header button', 'max' => 24, 'required' => true],
                    ]],
                    ['title' => 'Phone buttons', 'intro' => 'The two buttons fixed to the bottom of the screen on phones.', 'fields' => [
                        ['path' => 'nav.mobile_call', 'type' => 'text', 'label' => 'Call button', 'max' => 16, 'required' => true],
                        ['path' => 'nav.mobile_enquire', 'type' => 'text', 'label' => 'Enquire button', 'max' => 16, 'required' => true],
                    ]],
                    ['title' => 'Footer', 'fields' => [
                        ['path' => 'footer.closing', 'type' => 'textarea', 'label' => 'Closing line', 'rows' => 2, 'max' => 90, 'help' => self::ACCENT_HELP],
                        ['path' => 'footer.copyright', 'type' => 'text', 'label' => 'Copyright name', 'max' => 60, 'required' => true],
                        ['path' => 'footer.privacy_label', 'type' => 'text', 'label' => 'Privacy link', 'max' => 40, 'required' => true],
                    ]],
                ],
            ],

            'business' => [
                'title' => 'Business info',
                'menu' => 'business',
                'intro' => 'Contact details and opening hours used across the whole website, including search results.',
                'groups' => [
                    ['title' => 'Phone and messaging', 'fields' => [
                        ['path' => 'business.phone', 'type' => 'tel', 'label' => 'Phone number', 'required' => true, 'help' => 'Shown on the website and used by every Call button.'],
                        ['path' => 'business.whatsapp', 'type' => 'tel', 'label' => 'WhatsApp number (optional)'],
                        ['path' => 'business.whatsapp_confirmed', 'type' => 'toggle', 'label' => 'Show the WhatsApp button', 'help' => 'Switch on only after confirming the number above is active on WhatsApp.'],
                        ['path' => 'business.email', 'type' => 'email', 'label' => 'Email (optional)', 'help' => 'Shown in the Visit section and footer when filled in.'],
                    ]],
                    ['title' => 'Address', 'fields' => [
                        ['path' => 'business.street', 'type' => 'text', 'label' => 'Street address', 'max' => 120, 'required' => true],
                        ['path' => 'business.locality', 'type' => 'text', 'label' => 'Town', 'max' => 60, 'required' => true],
                        ['path' => 'business.region', 'type' => 'text', 'label' => 'State', 'max' => 60],
                        ['path' => 'business.postal_code', 'type' => 'text', 'label' => 'PIN code', 'max' => 12],
                        ['path' => 'business.maps_url', 'type' => 'url', 'label' => 'Google Maps link (recommended)', 'help' => 'In Google Maps, find GOT FITNEZZ, tap Share and paste the link here for exact directions. Without it, directions search for the address.'],
                    ]],
                    ['title' => 'Social media', 'fields' => [
                        ['path' => 'business.instagram', 'type' => 'url', 'label' => 'Instagram'],
                        ['path' => 'business.facebook', 'type' => 'url', 'label' => 'Facebook (optional)'],
                        ['path' => 'business.youtube', 'type' => 'url', 'label' => 'YouTube (optional)'],
                    ]],
                    ['title' => 'Opening hours', 'intro' => 'Times are India Standard Time. Leave a day empty if the gym is closed.', 'fields' => [
                        ['path' => 'hours.days', 'type' => 'hours', 'label' => 'Weekly hours'],
                        ['path' => 'hours.confirmed', 'type' => 'toggle', 'label' => 'These hours are correct — show them on the website', 'help' => 'While this is off, visitors are asked to call for current timings.'],
                        ['path' => 'hours.note', 'type' => 'text', 'label' => 'Note under the hours (optional)', 'max' => 120, 'help' => 'For example: Hours may differ on public holidays.'],
                    ]],
                ],
            ],

            'brand' => [
                'title' => 'Logo & colours',
                'menu' => 'brand',
                'intro' => 'Your logo, gym name and website colours.',
                'groups' => [
                    ['title' => 'Logo and name', 'fields' => [
                        ['path' => 'brand.logo', 'type' => 'image', 'label' => 'Logo', 'help' => 'Shown in the header, hero and footer. A PNG with a transparent background works best on the dark design.'],
                        ['path' => 'brand.logo_alt', 'type' => 'text', 'label' => 'Logo description (for screen readers)', 'max' => 120],
                        ['path' => 'brand.icon', 'type' => 'image', 'label' => 'Browser and phone icon', 'help' => 'A square image (at least 512 × 512) used in browser tabs and phone home screens.'],
                        ['path' => 'brand.name', 'type' => 'text', 'label' => 'Gym name', 'max' => 40, 'required' => true, 'help' => 'Also used as the text logo if no logo image is set.'],
                        ['path' => 'brand.expanded', 'type' => 'text', 'label' => 'Expanded name', 'max' => 60],
                    ]],
                    ['title' => 'Colours', 'intro' => 'The accent is taken from the lime shield in your logo. Keep text colours light and backgrounds dark so everything stays readable.', 'fields' => [
                        ['path' => 'theme.accent', 'type' => 'color', 'label' => 'Accent (buttons and highlighted words)', 'default' => '#D8E12A'],
                        ['path' => 'theme.accent_text', 'type' => 'color', 'label' => 'Text on accent buttons', 'default' => '#0B0B0B'],
                        ['path' => 'theme.bg', 'type' => 'color', 'label' => 'Page background', 'default' => '#0B0B0B'],
                        ['path' => 'theme.surface', 'type' => 'color', 'label' => 'Panel background', 'default' => '#171717'],
                        ['path' => 'theme.text', 'type' => 'color', 'label' => 'Main text', 'default' => '#F5F3ED'],
                        ['path' => 'theme.muted', 'type' => 'color', 'label' => 'Secondary text', 'default' => '#B5B5B5'],
                    ]],
                ],
            ],

            'seo' => [
                'title' => 'Search & sharing',
                'menu' => 'seo',
                'intro' => 'How your website appears in Google and when shared on WhatsApp, Instagram or Facebook.',
                'groups' => [
                    ['title' => 'Search results', 'fields' => [
                        ['path' => 'seo.title', 'type' => 'text', 'label' => 'Page title', 'max' => 70, 'required' => true, 'help' => 'Shown in Google and on browser tabs. Aim for under 60 characters.', 'counter' => 60],
                        ['path' => 'seo.description', 'type' => 'textarea', 'label' => 'Description', 'rows' => 3, 'max' => 200, 'required' => true, 'help' => 'The short summary under the title in Google. Aim for 120–155 characters.', 'counter' => 155],
                        ['path' => 'seo.hide_from_search', 'type' => 'toggle', 'label' => 'Hide the website from search engines', 'help' => 'Only use this while testing. Remember to switch it off at launch.'],
                    ]],
                    ['title' => 'Social sharing', 'fields' => [
                        ['path' => 'seo.share_image', 'type' => 'image', 'label' => 'Sharing image', 'help' => 'Shown when your link is shared. Best size: 1200 × 630 pixels.'],
                    ]],
                ],
            ],

            'privacy' => [
                'title' => 'Privacy notice',
                'menu' => 'pages',
                'intro' => 'The privacy notice linked from the footer and the callback form.',
                'notice' => 'Please read this notice carefully and change anything that does not match how GOT FITNEZZ handles enquiries.',
                'groups' => [
                    ['title' => 'Privacy notice', 'fields' => [
                        ['path' => 'privacy.title', 'type' => 'text', 'label' => 'Page title', 'max' => 60, 'required' => true],
                        ['path' => 'privacy.body', 'type' => 'textarea', 'label' => 'Text', 'rows' => 22, 'max' => 20000, 'required' => true, 'help' => 'Start a line with ## for a heading and - for a bullet point. ' . self::TOKENS_HELP],
                    ]],
                ],
            ],
        ];
    }

    public static function page(string $slug): ?array
    {
        return self::pages()[$slug] ?? null;
    }

    /** All fields of a page, flattened. */
    public static function fields(array $page): array
    {
        $fields = [];
        foreach ($page['groups'] as $group) {
            foreach ($group['fields'] as $field) {
                $fields[] = $field;
            }
        }
        return $fields;
    }

    /** Patterns of every content path that stores a photo or video id. */
    public static function mediaPaths(): array
    {
        if (self::$mediaPaths !== null) {
            return self::$mediaPaths;
        }
        $paths = [];
        foreach (self::pages() as $page) {
            foreach (self::fields($page) as $field) {
                if (in_array($field['type'], ['image', 'video'], true)) {
                    $paths[$field['path']] = $field['type'];
                }
                if ($field['type'] === 'list') {
                    foreach ($field['fields'] as $sub) {
                        if (in_array($sub['type'], ['image', 'video'], true)) {
                            $paths[$field['path'] . '.*.' . $sub['key']] = $sub['type'];
                        }
                    }
                }
            }
        }
        return self::$mediaPaths = $paths;
    }

    public static function isMediaPath(string $path): bool
    {
        $normalised = (string) preg_replace('/\.\d+(?=\.|$)/', '.*', $path);
        return isset(self::mediaPaths()[$normalised]);
    }

    /** Friendly description of where a content path appears (for "used in" lists). */
    public static function describePath(string $path): string
    {
        $top = explode('.', $path)[0];
        $labels = [
            'brand' => 'Logo & colours', 'seo' => 'Search & sharing', 'hero' => 'Hero', 'statement' => 'About',
            'training' => 'Training', 'gym' => 'The Gym', 'coaches' => 'Coaches', 'reviews' => 'Reviews',
            'stories' => 'Member stories', 'membership' => 'Membership',
        ];
        return $labels[$top] ?? ucfirst($top);
    }
}
