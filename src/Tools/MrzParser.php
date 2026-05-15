<?php

namespace TgDocumentProcessor\Tools;

class MrzParser
{
    public static function charToValue(string $c): int
    {
        if ($c === '<' || $c === ' ') return 0;
        if (ctype_digit($c)) return (int) $c;
        return ord($c) - ord('A') + 10;
    }

    public static function computeCheckDigit(string $data): ?int
    {
        if ($data === '') return null;

        $weights = [7, 3, 1];
        $sum = 0;

        for ($i = 0; $i < strlen($data); $i++) {
            $sum += self::charToValue($data[$i]) * $weights[$i % 3];
        }

        return $sum % 10;
    }

    public static function verifyCheckDigit(string $data, string $expected): bool
    {
        if ($expected === '' || $expected === '<') return false;
        $calculated = self::computeCheckDigit($data);
        if ($calculated === null) return false;
        $expectedDigit = (int) $expected[0];
        return $calculated === $expectedDigit;
    }

    public static function cleanOcrText(array $lines): array
    {
        $cleaned = [];
        foreach ($lines as $line) {
            $line = preg_replace('/[£€@]/', '', $line);
            $cleaned[] = $line;
        }
        return $cleaned;
    }

    public static function parseDate(string $yy, string $mm, string $dd, int $centuryThreshold = 30): ?string
    {
        $yearNum = (int) $yy;
        $century = $yearNum < $centuryThreshold ? '20' : '19';

        if (!checkdate((int) $mm, (int) $dd, (int) ($century . $yy))) {
            return null;
        }

        return sprintf('%02d/%02d/%s', $dd, $mm, $century . $yy);
    }
}
