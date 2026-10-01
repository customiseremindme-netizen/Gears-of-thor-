<?php
declare(strict_types=1);

namespace Got;

/**
 * Everything the public page templates need: content, which sections are
 * shown, navigation, contact details and helpers for images.
 * Sections with nothing real to show are hidden automatically.
 */
final class Site
{
    public readonly array $c;
    public readonly bool $preview;
    /** @var array<int, array{id: string, visible: bool, reason: string}> */
    public readonly array $sections;
    public readonly array $tokens;

    private function __construct(array $content, bool $preview)
    {
        $this->c = $content;
        $this->preview = $preview;
        Media::preload(array_keys(Content::mediaIds($content)));

        $sections = [];
        $seen = [];
        foreach ($content['sections'] as $entry) {
            $id = is_array($entry) ? (string) ($entry['id'] ?? '') : '';
            if (!isset(Schema::SECTIONS[$id]) || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $locked = Schema::SECTIONS[$id]['locked'] ?? false;
            $on = $locked || !empty($entry['on']);
            $hasContent = $this->hasContent($id);
            $sections[] = [
                'id' => $id,
                'visible' => $on && $hasContent,
                'reason' => !$on ? 'off' : (!$hasContent ? 'empty' : ''),
            ];
        }
        // Sections added in later versions appear at their default position.
        foreach (array_keys(Schema::SECTIONS) as $id) {
            if (!isset($seen[$id])) {
                $sections[] = ['id' => $id, 'visible' => $this->hasContent($id), 'reason' => $this->hasContent($id) ? '' : 'empty'];
            }
        }
        // The hero always comes first.
        usort($sections, static fn ($a, $b) => ($b['id'] === 'hero') <=> ($a['id'] === 'hero'));
        $this->sections = $sections;
        $this->tokens = $this->buildTokens();
    }

    public static function build(bool $preview = false): self
    {
        return new self(Content::load($preview ? 'draft' : 'published'), $preview);
    }

    public static function fromContent(array $content, bool $preview = false): self
    {
        return new self(Content::mergeDefaults($content, Content::defaults()), $preview);
    }

    public function get(string $path, mixed $default = ''): mixed
    {
        return array_get($this->c, $path, $default);
    }

    public function str(string $path): string
    {
        return s(array_get($this->c, $path, ''));
    }

    public function isVisible(string $id): bool
    {
        foreach ($this->sections as $section) {
            if ($section['id'] === $id) {
                return $section['visible'];
            }
        }
        return false;
    }

    public function media(mixed $id, string $kind = 'image'): ?array
    {
        $media = is_int($id) ? Media::find($id) : null;
        return $media && $media['kind'] === $kind ? $media : null;
    }

    // --- Section content rules -------------------------------------------

    private function hasContent(string $id): bool
    {
        $c = $this->c;
        return match ($id) {
            'hero', 'contact' => true,
            'statement' => s($c['statement']['heading'] ?? '') !== '' || s($c['statement']['text'] ?? '') !== '',
            'strip' => Text::lines(s($c['strip']['words'] ?? '')) !== [],
            'training' => $this->trainingItems() !== [],
            'gym' => $this->galleryPhotos() !== [] || $this->facilities() !== [],
            'coaches' => $this->coaches() !== [],
            'reviews' => $this->reviews() !== [],
            'stories' => $this->stories() !== [],
            'membership' => s($c['membership']['heading'] ?? '') !== '',
            'faq' => $this->faqItems() !== [],
            default => false,
        };
    }

    public function trainingItems(): array
    {
        return array_values(array_filter(
            (array) ($this->c['training']['items'] ?? []),
            static fn ($i) => is_array($i) && !empty($i['visible']) && s($i['title'] ?? '') !== ''
        ));
    }

    public function galleryPhotos(): array
    {
        $photos = [];
        foreach ((array) ($this->c['gym']['photos'] ?? []) as $item) {
            $media = is_array($item) ? $this->media($item['image'] ?? null) : null;
            if ($media) {
                $photos[] = ['media' => $media, 'caption' => s($item['caption'] ?? ''), 'layout' => s($item['layout'] ?? 'auto')];
            }
        }
        return $photos;
    }

    public function facilities(): array
    {
        return array_values(array_filter(
            (array) ($this->c['gym']['facilities'] ?? []),
            static fn ($i) => is_array($i) && !empty($i['visible']) && s($i['label'] ?? '') !== ''
        ));
    }

    public function coaches(): array
    {
        return array_values(array_filter(
            (array) ($this->c['coaches']['items'] ?? []),
            static fn ($i) => is_array($i) && !empty($i['visible']) && s($i['name'] ?? '') !== ''
        ));
    }

    /** Reviews are shown only with the member's permission. */
    public function reviews(): array
    {
        return array_values(array_filter(
            (array) ($this->c['reviews']['items'] ?? []),
            static fn ($i) => is_array($i) && !empty($i['visible']) && !empty($i['permission'])
                && s($i['quote'] ?? '') !== '' && s($i['name'] ?? '') !== ''
        ));
    }

    /** Stories are shown only with the member's consent. */
    public function stories(): array
    {
        return array_values(array_filter(
            (array) ($this->c['stories']['items'] ?? []),
            static fn ($i) => is_array($i) && !empty($i['visible']) && !empty($i['consent']) && s($i['name'] ?? '') !== ''
        ));
    }

    public function plans(): array
    {
        return array_values(array_filter(
            (array) ($this->c['membership']['plans'] ?? []),
            static fn ($i) => is_array($i) && !empty($i['visible']) && s($i['name'] ?? '') !== ''
        ));
    }

    public function faqItems(): array
    {
        return array_values(array_filter(
            (array) ($this->c['faq']['items'] ?? []),
            static fn ($i) => is_array($i) && !empty($i['visible']) && s($i['q'] ?? '') !== '' && s($i['a'] ?? '') !== ''
        ));
    }

    // --- Navigation -------------------------------------------------------

    /** Menu links for sections that are currently shown. */
    public function nav(): array
    {
        $links = [];
        foreach (['statement' => 'about', 'training' => 'training', 'gym' => 'gym', 'membership' => 'membership', 'contact' => 'contact'] as $section => $key) {
            $label = $this->str('nav.' . $key);
            if ($label !== '' && $this->isVisible($section)) {
                $links[] = ['href' => '#' . Schema::SECTIONS[$section]['anchor'], 'label' => $label];
            }
        }
        return $links;
    }

    // --- Business details -------------------------------------------------

    public function phone(): string
    {
        return $this->str('business.phone');
    }

    public function phoneHref(): string
    {
        return Text::telHref($this->phone());
    }

    public function whatsappHref(): ?string
    {
        $number = $this->str('business.whatsapp');
        if ($number === '' || empty($this->c['business']['whatsapp_confirmed'])) {
            return null;
        }
        return Text::whatsappHref($number, 'Hi GOT FITNEZZ, I would like to know more about membership.');
    }

    /** @return string[] address lines */
    public function addressLines(): array
    {
        $b = $this->c['business'];
        $line2 = trim(s($b['locality'] ?? '') . (s($b['region'] ?? '') !== '' ? ', ' . s($b['region']) : '') . ' ' . s($b['postal_code'] ?? ''));
        return array_values(array_filter([s($b['street'] ?? ''), $line2]));
    }

    public function addressText(): string
    {
        return implode(', ', $this->addressLines());
    }

    public function directionsUrl(): string
    {
        $custom = $this->str('business.maps_url');
        if ($custom !== '') {
            return $custom;
        }
        $query = $this->str('brand.name') . ', ' . $this->addressText();
        return 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($query);
    }

    public function hoursConfirmed(): bool
    {
        return !empty($this->c['hours']['confirmed']) && $this->hoursDays() !== [];
    }

    public function hoursDays(): array
    {
        $days = (array) ($this->c['hours']['days'] ?? []);
        foreach ($days as $sessions) {
            if (is_array($sessions) && $sessions !== []) {
                return $days;
            }
        }
        return [];
    }

    /** Social profiles that have a link. */
    public function socials(): array
    {
        $out = [];
        foreach (['instagram' => 'Instagram', 'facebook' => 'Facebook', 'youtube' => 'YouTube'] as $key => $label) {
            $url = $this->str('business.' . $key);
            if ($url !== '') {
                $out[] = ['key' => $key, 'label' => $label, 'url' => $url, 'handle' => $key === 'instagram' ? Text::instagramHandle($url) : $label];
            }
        }
        return $out;
    }

    public function logo(): ?array
    {
        return $this->media($this->c['brand']['logo'] ?? null);
    }

    /** Replacements for [phone], [address], [hours], [training], [instagram] in owner text. */
    private function buildTokens(): array
    {
        $phone = $this->phone();
        $phoneLink = $phone !== '' ? '<a href="' . e(Text::telHref($phone)) . '">' . e($phone) . '</a>' : 'us';
        $instagram = $this->str('business.instagram');
        $hours = $this->hoursConfirmed()
            ? e(Hours::summary($this->hoursDays()))
            : 'Please call ' . $phoneLink . ' to check current opening hours.';
        $training = array_map(static fn ($i) => s($i['title']), $this->trainingItems());

        return [
            'phone' => $phoneLink,
            'address' => e($this->addressText()),
            'hours' => $hours,
            'training' => $training ? e(Text::listing($training)) : 'a range of training options',
            'instagram' => $instagram !== ''
                ? '<a href="' . e($instagram) . '" rel="noopener" target="_blank">' . e(Text::instagramHandle($instagram)) . '</a>'
                : '',
            'email' => $this->str('business.email') !== ''
                ? '<a href="mailto:' . e($this->str('business.email')) . '">' . e($this->str('business.email')) . '</a>'
                : '',
        ];
    }

    /** CSS custom properties for the owner's colours. */
    public function themeCss(): string
    {
        $t = $this->c['theme'];
        $vars = [];
        foreach (['bg', 'surface', 'text', 'muted', 'accent', 'accent_text'] as $key) {
            $value = s($t[$key] ?? '');
            if (preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
                $vars[] = '--' . str_replace('_', '-', $key) . ':' . $value;
            }
        }
        return ':root{' . implode(';', $vars) . '}';
    }
}
