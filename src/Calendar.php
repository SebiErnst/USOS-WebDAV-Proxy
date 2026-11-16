<?php
declare(strict_types=1);
namespace UsosProxy;
use Sabre\VObject\Reader;
use Sabre\VObject\Component\VCalendar;
final class Calendar {
    public const INITIAL_STYLES = [
        'unicode' => 0x1F170,
        'bold-script' => 0x1D4D0,
        'bold' => 0x1D400,
        'italic' => 0x1D434,
        'sans-bold' => 0x1D5D4,
        'sans-bold-italic' => 0x1D63C,
        'monospace' => 0x1D670,
    ];
    public static function initials(string $letters, string $style): string {
        if ($style === 'plain') return '[' . $letters . ']';
        if (!isset(self::INITIAL_STYLES[$style])) throw new \InvalidArgumentException('Unknown initials style');
        return implode('', array_map(static fn($c)=>mb_chr(self::INITIAL_STYLES[$style] + ord($c)-65), str_split($letters)));
    }
    public static function title(string $name, array $custom): string {
        if (isset($custom[$name])) return $custom[$name];
        $name = preg_split('/\s+[-–—]\s+/u', $name, 2)[0];
        $name = preg_replace('/\s*\(en\)\s*$/iu', '', $name);
        $small = ['i','a','oraz','lub','albo','czy','ani','dla','do','na','w','we','z','ze','o','od','po','pod','nad','przez','bez','u','in','and','of'];
        return implode('', array_map(static function ($word) use ($small) {
            $first = mb_substr($word, 0, 1);
            return in_array(mb_strtolower($word), $small, true) || preg_match('/^\p{Ll}\p{Lu}/u', $word)
                ? mb_strtolower($first) : mb_strtoupper($first);
        }, preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY)));
    }
    public static function merge(array $sources, array $custom, string|bool $style = 'plain'): string {
        if (is_bool($style)) $style = $style ? 'unicode' : 'plain';
        $out = new VCalendar();
        $out->add('X-PUBLISHED-TTL', 'PT1H');
        foreach ($sources as $source) {
            $cal = Reader::read($source['ics']);
            if (!$cal instanceof VCalendar) throw new \RuntimeException('Invalid calendar');
            $zones = [];
            foreach ($cal->select('VTIMEZONE') as $zone) {
                $old = (string)$zone->TZID;
                $zones[$old] = 'proxy-' . substr(hash('sha256', $source['id']),0,16) . '-' . $old;
                $zone->TZID = $zones[$old];
                $out->add(clone $zone);
            }
            foreach ($cal->select('VEVENT') as $event) {
                $e = clone $event;
                foreach ($e->children() as $property) {
                    if (isset($property['TZID']) && isset($zones[(string)$property['TZID']])) $property['TZID'] = $zones[(string)$property['TZID']];
                }
                $description = (string)$e->DESCRIPTION;
                if (preg_match('/^Sala:\h*([^\r\n]+)\R([^\r\n]+)(?:\R|$)/mu', $description, $m) && preg_match('/^[A-Za-z0-9]+-[A-Za-z0-9]+$/', trim($m[2]))) {
                    $e->LOCATION = trim($m[2]) . '/' . trim($m[1]);
                    $e->DESCRIPTION = trim(str_replace($m[0], '', $description));
                }
                $group = preg_match('/[?&]gr_nr=(\d+)/', $description, $m) ? $m[1] : null;
                $summary = (string)$e->SUMMARY;
                $fullName = $summary;
                $isLecture = false;
                if (preg_match('/^([A-Z]+)\s*-\s*(.+)$/u', $summary, $m)) {
                    $isLecture = $m[1] === 'W';
                    $fullName = trim($m[2]);
                    $types = ['CWL'=>'lab','CWP'=>'proj.','W'=>'wyk.'];
                    $summary = self::title($m[2], $custom) . ' ' . ($types[$m[1]] ?? $m[1]);
                } else $summary = self::title($summary, $custom);
                $notes = trim((string)$e->DESCRIPTION);
                $e->DESCRIPTION = 'Przedmiot: ' . $fullName . ($notes !== '' ? "\n\n" . $notes : '');
                if ($group !== null && !$isLecture) $summary .= ' (gr. ' . $group . ')';
                if ($source['initials'] !== '') {
                    $prefix = self::initials($source['initials'], $style);
                    $summary = $prefix . ' ' . $summary;
                }
                $e->SUMMARY = $summary;
                $e->UID = hash('sha256', $source['id'] . ':' . (string)$e->UID) . '@usos-proxy';
                $out->add($e);
            }
        }
        return $out->serialize();
    }
}
