<?php

namespace TgDocumentProcessor\Dictionnaries;

class Dics
{
    public const array BLOOD_TYPES = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    public const array PHONE_NUMBER_STARTERS = ['90', '91', '92', '93', '70', '71', '99', '98', '97', '96', '79', '78'];

    public const array PARTICULAR_SIGNS = ['NEANT'];

    public const string COUNTRY = 'TOGO';

    private static ?string $localContent = null;

    private static function loadLocalContent(): string
    {
        if (self::$localContent === null) {
            $path = __DIR__ . '/../../data/Local.txt';
            if (file_exists($path)) {
                self::$localContent = file_get_contents($path);
            } else {
                self::$localContent = '';
            }
        }
        return self::$localContent;
    }

    public static function setLocalContentForTests(string $content): void
    {
        self::$localContent = $content;
    }

    public static function resetLocalContent(): void
    {
        self::$localContent = null;
    }

    public static function localExist(string $localName): bool
    {
        $content = self::loadLocalContent();
        if (empty($content)) {
            return false;
        }

        $exist = true;
        $parts = explode(' ', str_replace('-', ' ', $localName));
        foreach ($parts as $local) {
            $normalized = mb_strtolower(str_replace(['é', 'è', 'ê', 'ë'], 'e', $local));
            $normalizedContent = mb_strtolower(str_replace(['é', 'è', 'ê', 'ë'], 'e', $content));
            $exist = $exist && str_contains($normalizedContent, $normalized);
        }
        return $exist;
    }
}
