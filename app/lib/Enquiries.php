<?php
declare(strict_types=1);

namespace Got;

/** Stored enquiries and their follow-up status. */
final class Enquiries
{
    public const STATUSES = [
        'new' => 'New',
        'contacted' => 'Contacted',
        'follow_up' => 'Follow-up',
        'joined' => 'Joined',
    ];

    public static function create(array $data): int
    {
        $now = now_utc();
        return Db::get()->transaction(static function (Db $db) use ($data, $now): int {
            $id = $db->insert('enquiries', [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'phone_display' => $data['phone_display'],
                'goal' => $data['goal'],
                'contact_time' => $data['contact_time'],
                'message' => $data['message'],
                'status' => 'new',
                'notes' => null,
                'is_spam' => !empty($data['is_spam']) ? 1 : 0,
                'spam_reason' => $data['spam_reason'] ?? null,
                'consent_text' => mb_substr((string) $data['consent_text'], 0, 500),
                'consent_at' => $now,
                'ip_hash' => $data['ip_hash'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
                'updated_by' => null,
            ]);
            $db->insert('enquiry_events', [
                'enquiry_id' => $id,
                'user_id' => null,
                'action' => 'received',
                'detail' => !empty($data['is_spam']) ? 'Filtered as possible spam (' . ($data['spam_reason'] ?? '') . ')' : 'Received from the website form',
                'created_at' => $now,
            ]);
            return $id;
        });
    }

    public static function find(int $id): ?array
    {
        return Db::get()->one('SELECT * FROM enquiries WHERE id = ?', [$id]);
    }

    /** Same mobile number and message within a few minutes: treat as one enquiry. */
    public static function recentDuplicate(string $phone, string $message, int $seconds = 600): ?int
    {
        $row = Db::get()->one(
            'SELECT id, message FROM enquiries WHERE phone = ? AND created_at >= ? ORDER BY id DESC LIMIT 1',
            [$phone, gmdate('Y-m-d H:i:s', time() - $seconds)]
        );
        if ($row && trim((string) $row['message']) === trim($message)) {
            return (int) $row['id'];
        }
        return null;
    }

    /** @return array{0: array, 1: int} rows and total count */
    public static function search(array $filters, int $page = 1, int $perPage = 25): array
    {
        [$where, $params] = self::where($filters);
        $db = Db::get();
        $total = (int) $db->value("SELECT COUNT(*) FROM enquiries WHERE {$where}", $params);
        $rows = $db->all(
            "SELECT * FROM enquiries WHERE {$where} ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, max(0, ($page - 1) * $perPage)])
        );
        return [$rows, $total];
    }

    /** @return array{0: string, 1: array} */
    public static function where(array $filters): array
    {
        $clauses = [];
        $params = [];
        if (($filters['view'] ?? '') === 'spam') {
            $clauses[] = 'is_spam = 1';
        } else {
            $clauses[] = 'is_spam = 0';
            $status = (string) ($filters['status'] ?? '');
            if (isset(self::STATUSES[$status])) {
                $clauses[] = 'status = ?';
                $params[] = $status;
            }
        }
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $digits = (string) preg_replace('/\D/', '', $q);
            if (strlen($digits) >= 4) {
                $clauses[] = '(name LIKE ? OR phone LIKE ?)';
                $params[] = '%' . $q . '%';
                $params[] = '%' . $digits . '%';
            } else {
                $clauses[] = '(name LIKE ? OR goal LIKE ? OR message LIKE ? OR notes LIKE ?)';
                array_push($params, "%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%");
            }
        }
        if (!empty($filters['from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filters['from'])) {
            $clauses[] = 'created_at >= ?';
            $params[] = self::istDateToUtc((string) $filters['from'], false);
        }
        if (!empty($filters['to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $filters['to'])) {
            $clauses[] = 'created_at <= ?';
            $params[] = self::istDateToUtc((string) $filters['to'], true);
        }
        return [implode(' AND ', $clauses), $params];
    }

    /** Start or end of a calendar day in India, as a UTC timestamp. */
    private static function istDateToUtc(string $date, bool $endOfDay): string
    {
        $local = new \DateTimeImmutable($date . ($endOfDay ? ' 23:59:59' : ' 00:00:00'), new \DateTimeZone('Asia/Kolkata'));
        return $local->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    public static function counts(): array
    {
        $counts = array_fill_keys(array_keys(self::STATUSES), 0);
        $counts['all'] = 0;
        foreach (Db::get()->all('SELECT status, COUNT(*) AS n FROM enquiries WHERE is_spam = 0 GROUP BY status') as $row) {
            if (isset($counts[$row['status']])) {
                $counts[$row['status']] = (int) $row['n'];
            }
            $counts['all'] += (int) $row['n'];
        }
        $counts['spam'] = (int) Db::get()->value('SELECT COUNT(*) FROM enquiries WHERE is_spam = 1');
        return $counts;
    }

    public static function setStatus(int $id, string $status, int $userId): bool
    {
        if (!isset(self::STATUSES[$status])) {
            return false;
        }
        $current = self::find($id);
        if (!$current) {
            return false;
        }
        if ($current['status'] === $status) {
            return true;
        }
        Db::get()->update('enquiries', ['status' => $status, 'updated_at' => now_utc(), 'updated_by' => $userId], 'id = ?', [$id]);
        self::log($id, $userId, 'status', 'Status changed from ' . (self::STATUSES[$current['status']] ?? $current['status']) . ' to ' . self::STATUSES[$status]);
        return true;
    }

    public static function setNotes(int $id, string $notes, int $userId): void
    {
        $current = self::find($id);
        if (!$current || (string) $current['notes'] === $notes) {
            return;
        }
        Db::get()->update('enquiries', ['notes' => $notes, 'updated_at' => now_utc(), 'updated_by' => $userId], 'id = ?', [$id]);
        self::log($id, $userId, 'notes', 'Notes updated');
    }

    public static function setSpam(int $id, bool $spam, int $userId): void
    {
        Db::get()->update('enquiries', ['is_spam' => $spam ? 1 : 0, 'updated_at' => now_utc(), 'updated_by' => $userId], 'id = ?', [$id]);
        self::log($id, $userId, 'spam', $spam ? 'Moved to spam' : 'Marked as a genuine enquiry');
    }

    public static function delete(int $id): void
    {
        Db::get()->transaction(static function (Db $db) use ($id): void {
            $db->run('DELETE FROM enquiry_events WHERE enquiry_id = ?', [$id]);
            $db->run('DELETE FROM enquiries WHERE id = ?', [$id]);
        });
    }

    /** Remove spam older than $days (all spam when $days is 0). */
    public static function purgeSpam(int $days = 30): int
    {
        $cutoff = gmdate('Y-m-d H:i:s', time() - $days * 86400);
        $db = Db::get();
        return $db->transaction(static function (Db $db) use ($cutoff): int {
            $ids = array_map('intval', array_column($db->all('SELECT id FROM enquiries WHERE is_spam = 1 AND created_at <= ?', [$cutoff]), 'id'));
            foreach (array_chunk($ids, 200) as $chunk) {
                $in = implode(',', array_fill(0, count($chunk), '?'));
                $db->run("DELETE FROM enquiry_events WHERE enquiry_id IN ({$in})", $chunk);
                $db->run("DELETE FROM enquiries WHERE id IN ({$in})", $chunk);
            }
            return count($ids);
        });
    }

    public static function events(int $id): array
    {
        return Db::get()->all(
            'SELECT e.*, u.name AS user_name FROM enquiry_events e LEFT JOIN users u ON u.id = e.user_id
             WHERE e.enquiry_id = ? ORDER BY e.id DESC',
            [$id]
        );
    }

    public static function log(int $id, ?int $userId, string $action, string $detail): void
    {
        Db::get()->insert('enquiry_events', [
            'enquiry_id' => $id,
            'user_id' => $userId,
            'action' => $action,
            'detail' => mb_substr($detail, 0, 255),
            'created_at' => now_utc(),
        ]);
    }

    /** Rows for CSV export, newest first. */
    public static function exportRows(array $filters): array
    {
        [$where, $params] = self::where($filters);
        return Db::get()->all("SELECT * FROM enquiries WHERE {$where} ORDER BY created_at DESC, id DESC", $params);
    }

    /** Local phone format for display/CSV: "86086 11123" for Indian numbers. */
    public static function localPhone(string $e164): string
    {
        if (preg_match('/^\+91(\d{5})(\d{5})$/', $e164, $m)) {
            return $m[1] . ' ' . $m[2];
        }
        return $e164;
    }
}
