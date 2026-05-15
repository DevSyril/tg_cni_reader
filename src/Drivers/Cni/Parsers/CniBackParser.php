<?php

namespace TgDocumentProcessor\Drivers\Cni\Parsers;

use TgDocumentProcessor\Drivers\Cni\Models\CniBack;
use TgDocumentProcessor\Tools\MrzParser;

class CniBackParser
{
    public function parse(CniBack $backInfo, array $textToList): array
    {
        $findLine = function (array $keywords, array $lines): ?int {
            foreach ($lines as $i => $line) {
                foreach ($keywords as $keyword) {
                    if (str_contains($line, $keyword)) {
                        return $i;
                    }
                }
            }
            return null;
        };

        $cleanedText = [];
        foreach ($textToList as $line) {
            if (str_contains($line, 'I<') && str_contains($line, '<<')) {
                if (preg_match('/I<[A-Z]{3}[0-9]/', $line, $m, PREG_OFFSET_CAPTURE)) {
                    $mrzStart = $m[0][1];
                    $textPart = trim(substr($line, 0, $mrzStart));
                    if ($textPart !== '') {
                        $cleanedText[] = $textPart;
                    }
                    $cleanedText[] = substr($line, $mrzStart);
                    continue;
                }
            }
            $cleanedText[] = $line;
        }

        $check = $this->checkMrz($cleanedText, $backInfo);

        $tailleIdx = $findLine(['Taille', 'Taile', 'Taite', 'Talle'], $cleanedText);
        if ($tailleIdx !== null && isset($cleanedText[$tailleIdx])) {
            $line0 = str_replace([' CEL ', ' TEL '], '', $cleanedText[$tailleIdx]);
            $size = trim(preg_replace('/[^0-9,.]/', '', substr($line0, 0, 16)));
            $bloodType = trim(preg_replace('/[^ABO+-]/', '', str_replace(['0', '8', '4'], ['O', 'B', '+'], substr($line0, 22, 10))));
            $addressAndTel = substr($line0, 35);
            $address = trim(preg_replace('/[^A-Z ]/', '', $addressAndTel));
            $tel = trim(preg_replace('/[^0-9]/', '', $addressAndTel));

            $backInfo->size->value = $size;
            $backInfo->bloodType->value = $bloodType;
            $backInfo->address->value = $address;
            $backInfo->tel->value = $tel;

            unset($cleanedText[$tailleIdx]);
            $cleanedText = array_values($cleanedText);
        }

        $signesIdx = $findLine(['Signes particuliers'], $cleanedText);
        if ($signesIdx !== null && isset($cleanedText[$signesIdx])) {
            $line = $cleanedText[$signesIdx];
            $content = substr($line, strlen('Signes particuliers: '));

            if (($perePos = stripos($content, 'Pere:')) !== false) {
                $sign = trim(substr($content, 0, $perePos));
                $backInfo->particularSign->value = trim(preg_replace('/[^A-Z ]/', '', $sign));

                $afterPere = trim(substr($content, $perePos + 5));

                if (($merePos = stripos($afterPere, 'Mere:')) !== false) {
                    $pereStr = trim(substr($afterPere, 0, $merePos));
                    $pereParts = explode(',', $pereStr);
                    $backInfo->fatherLastName->value = trim($pereParts[0] ?? '');
                    $backInfo->fatherFirstName->value = trim($pereParts[1] ?? '');

                    $afterMere = trim(substr($afterPere, $merePos + 5));

                    if (($personPos = stripos($afterMere, 'Personne a prevenir')) !== false) {
                        $mereStr = trim(substr($afterMere, 0, $personPos));
                        $mereParts = explode(',', $mereStr);
                        $backInfo->motherLastName->value = trim($mereParts[0] ?? '');
                        $backInfo->motherFirstName->value = trim($mereParts[1] ?? '');

                        $personStr = ltrim(substr($afterMere, $personPos + strlen('Personne a prevenir')), ': ');
                        $personParts = explode(',', $personStr);
                        if (count($personParts) >= 1) {
                            $telRaw = trim(end($personParts));
                            $telDigits = preg_replace('/[^0-9]/', '', $telRaw);
                            $backInfo->personToContactTel->value = $telDigits;

                            array_pop($personParts);
                            if (!empty($personParts)) {
                                $address = trim(end($personParts));
                                $backInfo->personToContactAddress->value = trim(preg_replace('/[^A-Z ]/', '', $address));
                                array_pop($personParts);
                            }
                            if (!empty($personParts)) {
                                $name = trim(implode(' ', $personParts));
                                $backInfo->personToContactName->value = trim(preg_replace('/[^A-Z ]/', '', $name));
                            }
                        }
                    } else {
                        $mereStr = trim($afterMere);
                        $mereParts = explode(',', $mereStr);
                        $backInfo->motherLastName->value = trim($mereParts[0] ?? '');
                        $backInfo->motherFirstName->value = trim($mereParts[1] ?? '');
                    }
                } else {
                    $pereStr = trim($afterPere);
                    $pereParts = explode(',', $pereStr);
                    $backInfo->fatherLastName->value = trim($pereParts[0] ?? '');
                    $backInfo->fatherFirstName->value = trim($pereParts[1] ?? '');
                }

                if (preg_match('/(\d{8,})\s*$/', $content, $dm)) {
                    $backInfo->documentNumber->value = $dm[1];
                }
            } else {
                $backInfo->particularSign->value = trim(preg_replace('/[^A-Z ]/', '', $content));
                $docN = trim(preg_replace('/[^0-9]/', '', $content));
                $backInfo->documentNumber->value = $docN;
            }

            unset($cleanedText[$signesIdx]);
            $cleanedText = array_values($cleanedText);
        }

        if ($backInfo->fatherLastName->value === null && $backInfo->fatherFirstName->value === null) {
            $pereIdx = $findLine(['Pere:'], $cleanedText);
            if ($pereIdx !== null && isset($cleanedText[$pereIdx])) {
                $line = $cleanedText[$pereIdx];
                $content = substr($line, strpos($line, 'Pere:') + 5);

                if (($merePos = stripos($content, 'Mere:')) !== false) {
                    $pereStr = trim(substr($content, 0, $merePos));
                    $mereStr = trim(substr($content, $merePos + 5));
                } else {
                    $pereStr = trim($content);
                    $mereStr = '';
                }

                $pereParts = explode(',', $pereStr);
                $backInfo->fatherLastName->value = trim($pereParts[0] ?? '');
                $backInfo->fatherFirstName->value = trim($pereParts[1] ?? '');

                if (!empty($mereStr)) {
                    if (($personPos = stripos($mereStr, 'Personne a prevenir')) !== false) {
                        $mereStr = trim(substr($mereStr, 0, $personPos));
                    }
                    $mereParts = explode(',', $mereStr);
                    $backInfo->motherLastName->value = trim($mereParts[0] ?? '');
                    $backInfo->motherFirstName->value = trim($mereParts[1] ?? '');
                }

                unset($cleanedText[$pereIdx]);
                $cleanedText = array_values($cleanedText);
            }
        }

        if ($backInfo->personToContactName->value === null || $backInfo->personToContactTel->value === null) {
            $personIdx = $findLine(['Personne a prevenir'], $cleanedText);
            if ($personIdx !== null && isset($cleanedText[$personIdx])) {
                $line = $cleanedText[$personIdx];
                $personStr = ltrim(substr($line, strpos($line, 'Personne a prevenir') + strlen('Personne a prevenir')), ': ');
                $personStr = preg_replace('/[^A-Z0-9, ]/', '', $personStr);

                $parts = explode(',', $personStr);
                if (count($parts) >= 1) {
                    $tel = trim(end($parts));
                    $telDigits = preg_replace('/[^0-9]/', '', $tel);
                    $backInfo->personToContactTel->value = $telDigits;

                    array_pop($parts);
                    if (!empty($parts)) {
                        $backInfo->personToContactAddress->value = trim(end($parts));
                        array_pop($parts);
                    }
                    if (!empty($parts)) {
                        $backInfo->personToContactName->value = trim(implode(' ', $parts));
                    }
                }

                unset($cleanedText[$personIdx]);
                $cleanedText = array_values($cleanedText);
            }
        }

        $mrzLine3 = null;
        $linesWithChevrons = [];
        foreach ($cleanedText as $line) {
            if (str_contains($line, '<<')) {
                $linesWithChevrons[] = $line;
            }
        }
        if (count($linesWithChevrons) >= 1) {
            $mrzLine3 = end($linesWithChevrons);
        }

        if ($mrzLine3 !== null) {
            $cleanMrz = str_replace(' ', '', preg_replace('/[0-9]/', '', $mrzLine3));
            $indexOfChev = strpos($cleanMrz, '<');
            $indexOf2Chev = strpos($cleanMrz, '<<');

            if ($indexOfChev === $indexOf2Chev) {
                if (str_contains($cleanMrz, 'KK')) {
                    $cleanMrz = substr_replace($cleanMrz, '', strpos($cleanMrz, 'KK'), 2);
                    $cleanMrz = substr_replace($cleanMrz, '<<', $indexOfChev + 1, 0);
                }
            } elseif ($indexOfChev !== $indexOf2Chev) {
                if (($cleanMrz[$indexOfChev - 1] ?? '') === 'K') {
                    $cleanMrz = substr_replace($cleanMrz, '', $indexOfChev - 1, 1);
                    $cleanMrz = substr_replace($cleanMrz, '<', $indexOfChev - 1, 0);
                } elseif (($cleanMrz[$indexOfChev + 1] ?? '') === 'K') {
                    $cleanMrz = substr_replace($cleanMrz, '', $indexOfChev + 1, 1);
                    $cleanMrz = substr_replace($cleanMrz, '<', $indexOfChev + 1, 0);
                }
            }

            $parts = explode('<<', $cleanMrz);
            $backInfo->mrzLastName->value = trim($parts[0] ?? '');
            $backInfo->mrzFirstName->value = trim(str_replace('<', ' ', $parts[1] ?? ''));
            $backInfo->country->value = 'TOGO';
        }

        return [$backInfo, $check];
    }

    private function checkMrz(array $backText, CniBack $backData): bool
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

        $check2FirstMRZLine = MrzParser::verifyCheckDigit($docCheck . $birthCheck . $expiryCheck, substr($birthLine, -1, 1));

        $checkDocNumber = MrzParser::verifyCheckDigit(substr($docCheck, 0, 9), substr($docCheck, 9, 1));
        $backData->mrzDocumentNumber->value = substr($docCheck, 1, 8);
        $backData->mrzDocumentNumber->stat = $checkDocNumber;

        $checkBirthDate = MrzParser::verifyCheckDigit(substr($birthCheck, 0, 6), substr($birthCheck, 6));
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

        $checkExpiryDate = MrzParser::verifyCheckDigit(substr($expiryCheck, 0, 6), substr($expiryCheck, 6));
        $expirationYear = '20' . substr($birthLine, 8, 2);
        $expirationMonth = substr($birthLine, 10, 2);
        $expirationDay = substr($birthLine, 12, 2);
        $backData->mrzExpiryDate->value = "{$expirationDay}/{$expirationMonth}/{$expirationYear}";
        $backData->mrzExpiryDate->stat = $checkExpiryDate;

        return true;
    }
}
