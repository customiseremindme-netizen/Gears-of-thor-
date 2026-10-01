<?php
declare(strict_types=1);

namespace Got;

use DateTimeImmutable;
use DateTimeZone;

/** Opening hours formatting (all times are India Standard Time). */
final class Hours
{
    public const TIMEZONE = 'Asia/Kolkata';

    public const DAYS = [
        'mon' => 'Monday',
        'tue' => 'Tuesday',
        'wed' => 'Wednesday',
        'thu' => 'Thursday',
        'fri' => 'Friday',
        'sat' => 'Saturday',
        'sun' => 'Sunday',
    ];

    /** "17:30" → "5:30 PM" */
    public static function format12(string $time): string
    {
        [$h, $m] = array_map('intval', explode(':', $time) + [0, 0]);
        $suffix = $h >= 12 ? 'PM' : 'AM';
        $h12 = $h % 12 === 0 ? 12 : $h % 12;
        return sprintf('%d:%02d %s', $h12, $m, $suffix);
    }

    /** "5:30–9:30 AM" or "11:00 AM–2:00 PM" */
    public static function range(array $session): string
    {
        $open = self::format12((string) $session['open']);
        $close = self::format12((string) $session['close']);
        if (substr($open, -2) === substr($close, -2)) {
            return substr($open, 0, -3) . '–' . $close;
        }
        return $open . '–' . $close;
    }

    public static function dayText(array $sessions, string $joiner = ' and '): string
    {
        if (!$sessions) {
            return 'Closed';
        }
        return implode($joiner, array_map([self::class, 'range'], $sessions));
    }

    /**
     * Consecutive days with the same hours are grouped:
     * [['label' => 'Monday–Saturday', 'sessions' => [...], 'days' => ['mon', ...]], ...]
     */
    public static function grouped(array $days): array
    {
        $groups = [];
        foreach (array_keys(self::DAYS) as $day) {
            $sessions = is_array($days[$day] ?? null) ? array_values($days[$day]) : [];
            $last = count($groups) - 1;
            if ($last >= 0 && $groups[$last]['sessions'] == $sessions) {
                $groups[$last]['days'][] = $day;
            } else {
                $groups[] = ['days' => [$day], 'sessions' => $sessions];
            }
        }
        foreach ($groups as &$group) {
            $first = self::DAYS[$group['days'][0]];
            $lastDay = self::DAYS[end($group['days'])];
            $count = count($group['days']);
            $group['label'] = match (true) {
                $count === 1 => $first,
                $count === 2 => $first . ' & ' . $lastDay,
                default => $first . '–' . $lastDay,
            };
        }
        unset($group);
        return $groups;
    }

    /** "Monday–Saturday: 5:30–9:30 AM and 5:30–9:30 PM. Sunday: …" */
    public static function summary(array $days): string
    {
        $parts = [];
        foreach (self::grouped($days) as $group) {
            $parts[] = $group['label'] . ': ' . self::dayText($group['sessions']);
        }
        return implode('. ', $parts) . '.';
    }

    public static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone(self::TIMEZONE));
    }

    /** Today's hours in India. */
    public static function today(array $days, ?DateTimeImmutable $now = null): array
    {
        $now ??= self::now();
        $key = strtolower(substr($now->format('D'), 0, 3));
        $sessions = is_array($days[$key] ?? null) ? $days[$key] : [];
        return ['key' => $key, 'label' => self::DAYS[$key] ?? '', 'sessions' => $sessions, 'text' => self::dayText($sessions)];
    }

    /** schema.org openingHoursSpecification for search engines. */
    public static function schemaOrg(array $days): array
    {
        $bySession = [];
        foreach (self::DAYS as $key => $name) {
            foreach (is_array($days[$key] ?? null) ? $days[$key] : [] as $session) {
                $id = $session['open'] . '-' . $session['close'];
                $bySession[$id]['opens'] = $session['open'];
                $bySession[$id]['closes'] = $session['close'];
                $bySession[$id]['days'][] = 'https://schema.org/' . $name;
            }
        }
        $specs = [];
        foreach ($bySession as $item) {
            $specs[] = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => count($item['days']) === 1 ? $item['days'][0] : $item['days'],
                'opens' => $item['opens'],
                'closes' => $item['closes'],
            ];
        }
        return $specs;
    }
}
