<?php

namespace App\Helpers;

class NameNormalizer
{
    /**
     * Prepositions that remain lowercase when not at the start.
     */
    protected static array $prepositions = [
        'de', 'del', 'y', 'e', 'en', 'da', 'di', 'do', 'dos', 'das', 'van', 'von'
    ];

    /**
     * Articles that should only remain lowercase when preceded by 'de'.
     */
    protected static array $articlesAfterDe = [
        'la', 'las', 'los', 'el'
    ];

    /**
     * Acronyms / legal entities that should remain capitalized or fully uppercase.
     */
    protected static array $acronyms = [
        'c.a.' => 'C.A.',
        'ca' => 'C.A.',
        's.a.' => 'S.A.',
        'sa' => 'S.A.',
        's.r.l.' => 'S.R.L.',
        'srl' => 'S.R.L.',
        's.a.s.' => 'S.A.S.',
        'sas' => 'S.A.S.',
        'e.i.r.l.' => 'E.I.R.L.',
        'eirl' => 'E.I.R.L.',
        'ltd' => 'LTD',
        'inc' => 'INC',
        'r.i.f.' => 'R.I.F.',
        'rif' => 'R.I.F.',
        'c.i.' => 'C.I.',
        'ci' => 'C.I.',
        'dni' => 'DNI',
        'rut' => 'RUT',
    ];

    /**
     * Normalize a person or business name into UPPERCASE with UTF-8 support.
     * Enforces the project standard where all names (Users, Customers, Suppliers, etc.) are uppercase.
     */
    public static function personOrEntityName(?string $value): ?string
    {
        return self::uppercase($value);
    }

    /**
     * Legacy/Alternate: Normalize a name into proper title case with Spanish support.
     */
    public static function titleCase(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = trim(preg_replace('/\s+/u', ' ', $value));
        if ($clean === '') {
            return '';
        }

        $words = explode(' ', $clean);
        $totalWords = count($words);
        $formattedWords = [];

        foreach ($words as $index => $word) {
            $lowerWord = mb_strtolower($word, 'UTF-8');

            // Check if it's an acronym
            if (isset(self::$acronyms[$lowerWord])) {
                $formattedWords[] = self::$acronyms[$lowerWord];
                continue;
            }

            // Check if word contains internal dots or dashes (e.g. "J-123", "S.A.")
            if (str_contains($word, '.') && strlen($word) <= 6) {
                $formattedWords[] = mb_strtoupper($word, 'UTF-8');
                continue;
            }

            // Prepositions remain lowercase unless at the very beginning or end
            if ($index > 0 && $index < ($totalWords - 1) && in_array($lowerWord, self::$prepositions, true)) {
                $formattedWords[] = $lowerWord;
                continue;
            }

            // Articles (la, las, los, el) remain lowercase only when following 'de'
            if ($index > 0 && $index < ($totalWords - 1) && in_array($lowerWord, self::$articlesAfterDe, true)) {
                $prevWord = mb_strtolower($words[$index - 1], 'UTF-8');
                if ($prevWord === 'de') {
                    $formattedWords[] = $lowerWord;
                    continue;
                }
            }

            // Standard Title Case handling UTF-8 accents and ñ properly
            $formattedWords[] = mb_convert_case($lowerWord, MB_CASE_TITLE, 'UTF-8');
        }

        return implode(' ', $formattedWords);
    }

    /**
     * Normalize string to full uppercase with UTF-8 support (e.g. Products, Warehouses).
     * Example: "harina pan 1kg" -> "HARINA PAN 1KG"
     * Example: "depósito central" -> "DEPÓSITO CENTRAL"
     */
    public static function uppercase(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = trim(preg_replace('/\s+/u', ' ', $value));
        if ($clean === '') {
            return '';
        }

        return mb_strtoupper($clean, 'UTF-8');
    }
}
