<?php

namespace TgIdProcessor\Processors;

use TgIdProcessor\Contracts\DigitCheckerInterface;
use TgIdProcessor\Models\Back;

class DigitChecker implements DigitCheckerInterface
{
    public function check(array $backText, Back $backData): bool
    {
        $mrzLines = [];
        foreach ($backText as $line) {
            if (str_contains($line, '<<')) {
                $mrzLines[] = $line;
            }
        }

        if (count($mrzLines) !== 3) {
            return false;
        }

        $docNumberLine = str_replace(' ', '', str_replace(['O', 'S'], ['0', '5'], $mrzLines[0]));
        $docNumberLine = substr($docNumberLine, 4, 13);
        $docNumberLine = preg_replace('/[^0-9]/', '', $docNumberLine);

        $docCheck = '00' . ltrim(substr($docNumberLine, strpos($docNumberLine, '00')), '0');

        $birthLine = str_replace(['O', 'S', ' '], ['0', '5', ''], str_replace('H', 'M', $mrzLines[1]));
        $birthCheck = substr($birthLine, 0, 7);
        $expiryCheck = substr($birthLine, 8, 7);

        $check2FirstMRZLine = $this->checkDigit($docCheck . $birthCheck . $expiryCheck, substr($birthLine, -1, 1));

        $checkDocNumber = $this->checkDigit(substr($docCheck, 0, 9), substr($docCheck, 9, 1));
        $backData->mrzDocumentNumber->value = substr($docCheck, 1, 8);
        $backData->mrzDocumentNumber->stat = $checkDocNumber;

        $checkBirthDate = $this->checkDigit(substr($birthCheck, 0, 6), substr($birthCheck, 6));
        $birthYear = '20' . substr($birthLine, 0, 2);
        if (!in_array(substr($birthLine, 0, 1), ['0', '1', '2'])) {
            $birthYear = '19' . substr($birthLine, 0, 2);
        }
        $birthMonth = substr($birthLine, 2, 2);
        $birthDay = substr($birthLine, 4, 2);
        $sexe = substr($birthLine, 7, 1);

        $backData->mrzSex->value = trim($sexe);
        $backData->mrzBirthDate->value = "{$birthDay}/{$birthMonth}/{$birthYear}";
        $backData->mrzBirthDate->stat = $checkBirthDate;

        $checkExpiryDate = $this->checkDigit(substr($expiryCheck, 0, 6), substr($expiryCheck, 6));
        $expirationYear = '20' . substr($birthLine, 8, 2);
        $expirationMonth = substr($birthLine, 10, 2);
        $expirationDay = substr($birthLine, 12, 2);
        $backData->mrzExpiryDate->value = "{$expirationDay}/{$expirationMonth}/{$expirationYear}";
        $backData->mrzExpiryDate->stat = $checkExpiryDate;

        return true;
    }

    private function checkDigit(string $data, string $checkDigit): bool
    {
        if ($data === '' || $checkDigit === '') {
            return false;
        }

        $charToValue = function (string $c): int {
            if ($c === '<') return 0;
            if (ctype_digit($c)) return (int) $c;
            return ord($c) - ord('A') + 10;
        };

        $weights = [7, 3, 1];
        $sum = 0;

        for ($i = 0; $i < strlen($data); $i++) {
            $sum += $charToValue($data[$i]) * $weights[$i % 3];
        }

        $checkDigitCalculated = $sum % 10;
        return $checkDigitCalculated === (int) $checkDigit[0];
    }
}
