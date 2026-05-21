<?php

namespace TgDocumentProcessor\Tools;

use TgDocumentProcessor\Contracts\ImageReaderInterface;
use thiagoalessio\TesseractOCR\TesseractOCR;

class ImageReader implements ImageReaderInterface
{
    private string $tesseractPath;

    private string $language;

    private string $tessdataPrefix;

    public function __construct(string $tesseractPath = '', string $language = 'fra', string $tessdataPrefix = '')
    {
        $this->tesseractPath = $tesseractPath;
        $this->language = $language;
        $this->tessdataPrefix = $tessdataPrefix !== '' ? realpath($tessdataPrefix) : '';
    }

    public function readTextOnImage(string $path): array
    {
        if (!empty($this->tessdataPrefix)) {
            putenv('TESSDATA_PREFIX=' . $this->tessdataPrefix);
        }

        $ocr = new TesseractOCR($path);
        if (!empty($this->tesseractPath)) {
            $ocr->executable($this->tesseractPath);
        }
        $ocr->lang($this->language);

        $result = $ocr->run();
        // $result = $this->sanitizeUtf8($result);
        $result = trim($result);
        $result = $this->replaceFrenchChar($result);
        $result = $this->reduceWhiteSpace($result);

        $resultList = explode("\n", $result);
        $resultList = $this->cleanInArray($resultList);

        return $resultList;
    }

    private function cleanInArray(array $array): array
    {
        return array_values(array_filter($array, function ($i) {
            return $i !== null && $i !== '' && $i !== ' ';
        }));
    }

    private function sanitizeUtf8(string $text): string
    {
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $text);

        $text = preg_replace_callback('/[\x80-\xFF]/', function (array $m): string {
            $ord = ord($m[0]);

            if ($ord === 0xA3) return 'E';
            if ($ord >= 0xC0 && $ord <= 0xFF) {
                return match ($ord) {
                    0xC0, 0xC1, 0xC2, 0xC3 => 'A',
                    0xC8, 0xC9 => 'E',
                    0xCC, 0xCD => 'I',
                    0xD2, 0xD3 => 'O',
                    0xD9, 0xDA => 'U',
                    0xC7 => 'C',
                    0xD1 => 'N',
                    default => '?',
                };
            }

            return '?';
        }, $text);

        return $text;
    }

    private function replaceFrenchChar(string $text): string
    {
        $text = str_replace(['é', 'è', 'ê', 'ë', 'à', 'â', 'ù', 'û', 'ü'], ['e', 'e', 'e', 'e', 'a', 'a', 'u', 'u', 'u'], $text);
        return trim($text);
    }

    private function reduceWhiteSpace(string $text): string
    {
        $text = str_replace('_', ' ', $text);
        $text = preg_replace('/\s{2,}/', ' ', $text);
        return trim($text);
    }
}
