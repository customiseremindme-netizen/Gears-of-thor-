<?php
declare(strict_types=1);

namespace Got;

/** Search engine and social sharing information. */
final class Seo
{
    /**
     * Structured data for Google: an ExerciseGym (a type of LocalBusiness)
     * built only from details entered in the dashboard. No ratings or reviews
     * are ever generated here.
     */
    public static function jsonLd(Site $site): string
    {
        $name = $site->str('brand.name');
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'ExerciseGym',
            '@id' => abs_url('/') . '#gym',
            'name' => $name,
            'url' => abs_url('/'),
        ];
        if ($site->str('brand.expanded') !== '') {
            $data['alternateName'] = $site->str('brand.expanded');
        }
        if ($site->str('seo.description') !== '') {
            $data['description'] = $site->str('seo.description');
        }
        if ($site->phone() !== '') {
            $data['telephone'] = substr($site->phoneHref(), 4);
        }
        if ($site->str('business.email') !== '') {
            $data['email'] = $site->str('business.email');
        }
        $b = $site->c['business'];
        $address = array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => s($b['street'] ?? ''),
            'addressLocality' => s($b['locality'] ?? ''),
            'addressRegion' => s($b['region'] ?? ''),
            'postalCode' => s($b['postal_code'] ?? ''),
            'addressCountry' => 'IN',
        ]);
        if (count($address) > 2) {
            $data['address'] = $address;
        }
        if ($logo = $site->logo()) {
            $data['logo'] = Media::absoluteFileUrl($logo);
            $data['image'] = Media::absoluteFileUrl($logo);
        }
        if ($share = $site->media($site->get('seo.share_image', null))) {
            $data['image'] = Media::absoluteFileUrl($share);
        }
        if ($site->str('business.maps_url') !== '') {
            $data['hasMap'] = $site->str('business.maps_url');
        }
        $sameAs = array_column($site->socials(), 'url');
        if ($sameAs) {
            $data['sameAs'] = $sameAs;
        }
        if ($site->hoursConfirmed()) {
            $data['openingHoursSpecification'] = Hours::schemaOrg($site->hoursDays());
        }

        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
        return '<script type="application/ld+json">' . $json . '</script>';
    }

    /** Absolute URL of the image used when the site is shared. */
    public static function shareImage(Site $site): ?array
    {
        $media = $site->media($site->get('seo.share_image', null)) ?? $site->media($site->get('hero.image', null));
        if (!$media) {
            return null;
        }
        $width = (int) ($media['variants']['fallback'] ?? $media['width']);
        $height = $media['width'] ? (int) round($media['height'] * $width / $media['width']) : null;
        return ['url' => Media::absoluteFileUrl($media), 'width' => $width, 'height' => $height, 'alt' => (string) $media['alt']];
    }
}
