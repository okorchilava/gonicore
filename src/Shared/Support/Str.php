<?php

declare(strict_types=1);

namespace GoniCore\Shared\Support;

/**
 * String utility helpers.
 */
final class Str
{
    /**
     * Georgian Mkhedruli → Latin transliteration table (ISO 9984 / BGN/PCGN).
     *
     * @var array<string, string>
     */
    private const GEORGIAN = [
        'ა'=>'a',  'ბ'=>'b',  'გ'=>'g',  'დ'=>'d',  'ე'=>'e',
        'ვ'=>'v',  'ზ'=>'z',  'თ'=>'t',  'ი'=>'i',  'კ'=>'k',
        'ლ'=>'l',  'მ'=>'m',  'ნ'=>'n',  'ო'=>'o',  'პ'=>'p',
        'ჟ'=>'zh', 'რ'=>'r',  'ს'=>'s',  'ტ'=>'t',  'უ'=>'u',
        'ფ'=>'p',  'ქ'=>'k',  'ღ'=>'gh', 'ყ'=>'q',  'შ'=>'sh',
        'ჩ'=>'ch', 'ც'=>'ts', 'ძ'=>'dz', 'წ'=>'ts', 'ჭ'=>'ch',
        'ხ'=>'kh', 'ჯ'=>'j',  'ჰ'=>'h',
        // Archaic / rare
        'ჱ'=>'e',  'ჲ'=>'y',  'ჳ'=>'w',  'ჴ'=>'kh', 'ჵ'=>'o',
        'ჶ'=>'f',  'ჷ'=>'e',  'ჸ'=>'',
    ];

    /**
     * Cyrillic → Latin transliteration table (common / BGN-PCGN).
     *
     * @var array<string, string>
     */
    private const CYRILLIC = [
        'а'=>'a',  'б'=>'b',  'в'=>'v',  'г'=>'g',  'д'=>'d',
        'е'=>'e',  'ё'=>'yo', 'ж'=>'zh', 'з'=>'z',  'и'=>'i',
        'й'=>'y',  'к'=>'k',  'л'=>'l',  'м'=>'m',  'н'=>'n',
        'о'=>'o',  'п'=>'p',  'р'=>'r',  'с'=>'s',  'т'=>'t',
        'у'=>'u',  'ф'=>'f',  'х'=>'kh', 'ц'=>'ts', 'ч'=>'ch',
        'ш'=>'sh', 'щ'=>'sch','ъ'=>'',   'ы'=>'y',  'ь'=>'',
        'э'=>'e',  'ю'=>'yu', 'я'=>'ya',
        // Uppercase handled via mb_strtolower before lookup
    ];

    /**
     * Latin diacritic → ASCII base map (covers Western/Central European and more).
     *
     * @var array<string, string>
     */
    private const LATIN_DIACRITICS = [
        'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a','æ'=>'ae',
        'ç'=>'c','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','ì'=>'i','í'=>'i',
        'î'=>'i','ï'=>'i','ð'=>'d','ñ'=>'n','ò'=>'o','ó'=>'o','ô'=>'o',
        'õ'=>'o','ö'=>'o','ø'=>'o','ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u',
        'ý'=>'y','þ'=>'th','ÿ'=>'y','ā'=>'a','ă'=>'a','ą'=>'a','ć'=>'c',
        'č'=>'c','ď'=>'d','đ'=>'d','ē'=>'e','ĕ'=>'e','ě'=>'e','ę'=>'e',
        'ğ'=>'g','ġ'=>'g','ģ'=>'g','ħ'=>'h','ī'=>'i','ĭ'=>'i','į'=>'i',
        'ı'=>'i','ķ'=>'k','ĺ'=>'l','ļ'=>'l','ľ'=>'l','ł'=>'l','ń'=>'n',
        'ņ'=>'n','ň'=>'n','ō'=>'o','ő'=>'o','œ'=>'oe','ŗ'=>'r','ř'=>'r',
        'ś'=>'s','ş'=>'s','š'=>'s','ß'=>'ss','ţ'=>'t','ť'=>'t','ū'=>'u',
        'ŭ'=>'u','ů'=>'u','ű'=>'u','ų'=>'u','ź'=>'z','ż'=>'z','ž'=>'z',
    ];

    /**
     * Convert a string to a URL-safe ASCII slug with full transliteration.
     *
     * Priority:
     *  1. PHP `intl` extension  — `Any-Latin; Latin-ASCII; Lower()` (best quality)
     *  2. Built-in tables       — Georgian, Cyrillic, Latin diacritics (no extension needed)
     *  3. Strip remaining non-ASCII bytes as a last resort
     *
     * Examples:
     *   "Hello World!"          → "hello-world"
     *   "ქართული ენა"           → "kartuli-ena"
     *   "Привет мир"            → "privet-mir"
     *   "Héllo Wörld"           → "hello-world"
     */
    public static function slug(string $value, string $separator = '-'): string
    {
        $value = mb_strtolower($value, 'UTF-8');

        // 1. intl — handles virtually every script accurately.
        if (function_exists('transliterator_transliterate')) {
            $result = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $value);
            if ($result !== false) {
                $value = $result;
            }
        } else {
            // 2a. Georgian Mkhedruli
            $value = strtr($value, self::GEORGIAN);

            // 2b. Cyrillic
            $value = strtr($value, self::CYRILLIC);

            // 2c. Latin diacritics
            $value = strtr($value, self::LATIN_DIACRITICS);

            // 2d. Strip any remaining non-ASCII bytes
            $value = (string) preg_replace('/[^\x00-\x7F]/u', '', $value);
        }

        // Normalise: collapse any non-alphanumeric run into the separator.
        $value = (string) preg_replace('/[^a-z0-9]+/', $separator, $value);
        $value = trim($value, $separator);

        // Guard: empty result after all of the above (rare edge-case).
        if ($value === '') {
            $value = substr(bin2hex(random_bytes(6)), 0, 10);
        }

        return $value;
    }

    /**
     * Generate a cryptographically secure random hex string.
     */
    public static function random(int $length = 32): string
    {
        return substr(bin2hex(random_bytes((int) ceil($length / 2))), 0, $length);
    }

    /**
     * Truncate $value to at most $maxLength characters, appending $ellipsis if cut.
     */
    public static function truncate(string $value, int $maxLength, string $ellipsis = '…'): string
    {
        if (mb_strlen($value) <= $maxLength) {
            return $value;
        }

        $cutTo = $maxLength - mb_strlen($ellipsis);

        return rtrim(mb_substr($value, 0, max(0, $cutTo))) . $ellipsis;
    }

    /**
     * Convert snake_case or kebab-case to StudlyCaps (PascalCase).
     */
    public static function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }

    /**
     * Convert StudlyCaps or camelCase to snake_case.
     */
    public static function snake(string $value): string
    {
        $value = (string) preg_replace('/([A-Z])/', '_$1', lcfirst($value));
        return strtolower(trim($value, '_'));
    }
}
