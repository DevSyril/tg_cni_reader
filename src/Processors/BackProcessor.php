<?php

namespace TgIdProcessor\Processors;

use TgIdProcessor\Contracts\BackProcessorInterface;
use TgIdProcessor\Contracts\DigitCheckerInterface;
use TgIdProcessor\Models\Back;

class BackProcessor implements BackProcessorInterface
{
    private DigitCheckerInterface $digitChecker;

    public function __construct(DigitCheckerInterface $digitChecker)
    {
        $this->digitChecker = $digitChecker;
    }

    public function processBack(Back $backInfo, array $textToList): array
    {
        $check = $this->digitChecker->check($textToList, $backInfo);

        $startIndex = 0;
        foreach ($textToList as $item) {
            $listIndex = ['Taille', 'Taile', 'Taite', 'Talle'];
            foreach ($listIndex as $index) {
                if (str_contains($item, $index)) {
                    $startIndex = array_search($item, $textToList, true);
                }
            }
        }

        $findData = function (string $text): bool {
            $text = preg_replace('/[^a-zA-Z0-9]/', '', trim($text));
            return strlen($text) > 10;
        };

        // PREMIERE LIGNE Taille groupe sanguin et domicile et numero
        if (isset($textToList[$startIndex])) {
            $line0 = str_replace([' CEL ', ' TEL '], '', $textToList[$startIndex]);
            $size = trim(preg_replace('/[^0-9,.]/', '', substr($line0, 0, 16)));
            $bloodType = trim(preg_replace('/[^ABO+-]/', '', str_replace(['0', '8', '4'], ['O', 'B', '+'], substr($line0, 22, 10))));
            $addressAndTel = substr($line0, 35);
            $address = trim(preg_replace('/[^A-Z ]/', '', $addressAndTel));
            $tel = trim(preg_replace('/[^0-9]/', '', $addressAndTel));

            $backInfo->size->value = $size;
            $backInfo->bloodType->value = $bloodType;
            $backInfo->address->value = $address;
            $backInfo->tel->value = $tel;
        }

        // DEUXIEME LIGNE Signes particuliers
        if (isset($textToList[$startIndex + 1])) {
            $line1 = $textToList[$startIndex + 1];
            if (!$findData($line1)) {
                $startIndex += 1;
                $line1 = $textToList[$startIndex + 1];
            }
            $line1 = substr($line1, 17);
            $sign = trim(preg_replace('/[^A-Z ]/', '', $line1));
            $docN = trim(preg_replace('/[^0-9]/', '', $line1));
            if (empty($docN)) {
                $startIndex += 1;
                $line1 = $textToList[$startIndex + 1];
                $docN = trim(preg_replace('/[^0-9]/', '', $line1));
                if (empty($docN)) {
                    $startIndex--;
                }
            }

            $backInfo->particularSign->value = $sign;
            $backInfo->documentNumber->value = $docN;
        }

        // TROISIEME LIGNE Père, Mère
        if (isset($textToList[$startIndex + 2])) {
            $line2 = $textToList[$startIndex + 2];
            if (!$findData($line2)) {
                $startIndex += 1;
                $line2 = $textToList[$startIndex + 2];
            }
            $line2 = str_replace(':', '', substr($line2, 4));
            $line2Liste = explode('Mere', $line2);

            $father = str_replace(' ', ',', trim($line2Liste[0] ?? ''));
            $fatherParts = explode(',', $father);
            $backInfo->fatherLastName->value = $fatherParts[0] ?? '';
            $fatherFirstNameStr = substr($father, strpos($father, ','));
            $backInfo->fatherFirstName->value = trim(str_replace(',', ' ', $fatherFirstNameStr));

            $mother = str_replace(' ', ',', trim($line2Liste[1] ?? ''));
            $motherParts = explode(',', $mother);
            $backInfo->motherLastName->value = $motherParts[0] ?? '';
            $motherFirstNameStr = substr($mother, strpos($mother, ','));
            $backInfo->motherFirstName->value = trim(str_replace(',', ' ', $motherFirstNameStr));
        }

        // CINQUIEME LIGNE Personne à prevenir (index + 3 dans le flux réel)
        if (isset($textToList[$startIndex + 3])) {
            $line3 = $textToList[$startIndex + 3];
            if (!$findData($line3)) {
                $startIndex += 1;
                $line3 = $textToList[$startIndex + 3];
            }
            $line3 = preg_replace('/[^A-Z0-9, ]/', '', substr($line3, 3));

            $parts = explode(',', $line3);
            $tel = trim(end($parts));
            array_pop($parts);
            $address = trim(end($parts));
            array_pop($parts);
            $name = trim(implode(' ', $parts));

            $backInfo->personToContactTel->value = $tel;
            $backInfo->personToContactAddress->value = $address;
            $backInfo->personToContactName->value = $name;
        }

        // MRZ Ligne3
        $mrzLine3 = '';
        foreach (array_reverse($textToList) as $line) {
            if (str_contains($line, '<<')) {
                $mrzLine3 = $line;
                break;
            }
        }
        if (!empty($mrzLine3)) {
            $mrzLine3 = str_replace(' ', '', preg_replace('/[0-9]/', '', $mrzLine3));
            $indexOfChev = strpos($mrzLine3, '<');
            $indexOf2Chev = strpos($mrzLine3, '<<');

            if ($indexOfChev === $indexOf2Chev) {
                if (str_contains($mrzLine3, 'KK')) {
                    $mrzLine3 = substr_replace($mrzLine3, '', strpos($mrzLine3, 'KK'), 2);
                    $mrzLine3 = substr_replace($mrzLine3, '<<', $indexOfChev + 1, 0);
                }
            } elseif ($indexOfChev !== $indexOf2Chev) {
                if (($mrzLine3[$indexOfChev - 1] ?? '') === 'K') {
                    $mrzLine3 = substr_replace($mrzLine3, '', $indexOfChev - 1, 1);
                    $mrzLine3 = substr_replace($mrzLine3, '<', $indexOfChev - 1, 0);
                } elseif (($mrzLine3[$indexOfChev + 1] ?? '') === 'K') {
                    $mrzLine3 = substr_replace($mrzLine3, '', $indexOfChev + 1, 1);
                    $mrzLine3 = substr_replace($mrzLine3, '<', $indexOfChev + 1, 0);
                }
            }

            $line6Liste = explode('<<', $mrzLine3);
            $backInfo->mrzLastName->value = trim($line6Liste[0] ?? '');
            $backInfo->mrzFirstName->value = trim(str_replace('<', ' ', $line6Liste[1] ?? ''));
            $backInfo->country->value = 'TOGO';
        }

        return [$backInfo, $check];
    }
}
