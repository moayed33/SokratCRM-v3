<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\CrmOption;
use Illuminate\Support\Facades\Cache;

/**
 * Read-side helper for the GUI-managed picklists ("option sets").
 *
 * Everything lives behind ONE cache entry holding plain arrays: the
 * database cache store refuses object deserialization by design, and a
 * single key makes invalidation trivial (per-set keys could go stale
 * when a whole set is deleted).
 */
class CrmOptions
{
    public const CACHE_KEY = 'crm_options.sets';

    /**
     * @return list<array{value:string,label_ar:string,label_en:?string}>
     */
    public static function get(string $setKey): array
    {
        $sets = self::allSets();
        $options = $sets[mb_strtolower(trim($setKey))] ?? [];

        return is_array($options) ? $options : [];
    }

    /**
     * @return list<string>
     */
    public static function values(string $setKey): array
    {
        return array_map(
            static fn (array $option): string => (string) $option['value'],
            self::get($setKey),
        );
    }

    /**
     * Locale-aware label for one option array as returned by get().
     */
    public static function labelOf(array $option): string
    {
        $labelEn = trim((string) ($option['label_en'] ?? ''));

        if (app()->getLocale() === 'en' && $labelEn !== '') {
            return $labelEn;
        }

        return trim((string) ($option['label_ar'] ?? $option['value'] ?? ''));
    }

    /**
     * @return list<array{key:string,count:int}>
     */
    public static function sets(): array
    {
        $sets = self::allSets();

        $list = [];
        foreach ($sets as $key => $options) {
            $list[] = [
                'key' => (string) $key,
                'count' => is_array($options) ? count($options) : 0,
            ];
        }

        ksort($list);

        return $list;
    }

    public static function flush(?string $setKey = null): void
    {
        unset($setKey);

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, list<array{value:string,label_ar:string,label_en:?string}>>
     */
    private static function allSets(): array
    {
        $sets = Cache::rememberForever(self::CACHE_KEY, static function (): array {
            $grouped = [];

            CrmOption::query()
                ->where('is_active', true)
                ->orderBy('position')
                ->orderBy('id')
                ->get()
                ->each(static function (CrmOption $option) use (&$grouped): void {
                    $grouped[$option->set_key][] = [
                        'value' => (string) $option->value,
                        'label_ar' => (string) $option->label_ar,
                        'label_en' => $option->label_en,
                    ];
                });

            return $grouped;
        });

        return is_array($sets) ? $sets : [];
    }
}
