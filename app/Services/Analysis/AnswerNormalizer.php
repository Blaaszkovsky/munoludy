<?php

namespace App\Services\Analysis;

class AnswerNormalizer
{
    public function normalize(string $value): string
    {
        $v = trim($value);
        $v = mb_strtolower($v, 'UTF-8');
        // Strip Polish diacritics
        $v = strtr($v, [
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
            'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
        ]);
        // Keep alphanumerics, spaces, hyphens — replace everything else with space
        $v = preg_replace('/[^a-z0-9\s\-]/u', ' ', $v);
        $v = preg_replace('/\s+/', ' ', $v);
        return trim($v);
    }
}
