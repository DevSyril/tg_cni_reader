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
