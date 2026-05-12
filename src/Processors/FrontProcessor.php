<?php

namespace TgIdProcessor\Processors;

use TgIdProcessor\Contracts\FrontProcessorInterface;
use TgIdProcessor\Models\Front;

class FrontProcessor implements FrontProcessorInterface
{
    public function processFront(Front $frontInfo, array $textToList): Front
    {
        $findData = function (string $text): bool {
            $text = preg_replace('/[^a-zA-Z0-9]/', '', trim($text));
            return strlen($text) > 5;
        };

        $startIndex = 0;
        foreach ($textToList as $item) {
            $listIndex = ['Numero', 'Numer'];
            foreach ($listIndex as $index) {
                if (str_contains($item, $index)) {
                    $startIndex = array_search($item, $textToList, true);
                }
            }
        }

        // PREMIERE LIGNE (Numero de carte)
        if (isset($textToList[$startIndex])) {
            $line0 = $textToList[$startIndex];
            $frontInfo->cardNumber->value = trim(preg_replace('/[^0-9-]/', '', substr($line0, 6)));
        }

        // DEUXIEME LIGNE (Nom)
        if (isset($textToList[$startIndex + 1])) {
            $line1 = $textToList[$startIndex + 1];
            if (!$findData($line1)) {
                $startIndex += 1;
                $line1 = $textToList[$startIndex + 1];
            }
            $frontInfo->lastName->value = trim(preg_replace('/[^A-Z]/', '', substr($line1, 3)));
        }

        // TROISIEME LIGNE (Prenom)
        if (isset($textToList[$startIndex + 2])) {
            $line2 = $textToList[$startIndex + 2];
            if (!$findData($line2)) {
                $startIndex += 1;
                $line2 = $textToList[$startIndex + 2];
            }
            $frontInfo->firstName->value = trim(preg_replace('/[^a-zA-Z- ]/', '', substr($line2, 6)));
        }

        // QUATRIEME LIGNE (Naissance Sexe)
        if (isset($textToList[$startIndex + 3])) {
            $line3 = $textToList[$startIndex + 3];
            if (!$findData($line3)) {
                $startIndex += 1;
                $line3 = $textToList[$startIndex + 3];
            }
            $frontInfo->birthDate->value = trim(str_replace('-', '/', preg_replace('/[^0-9-]/', '', $line3)));
            $frontInfo->sex->value = trim(preg_replace('/[^MF]/', '', $line3));
        }

        // CINQUIEME LIGNE (Lieu de Naissance)
        if (isset($textToList[$startIndex + 4])) {
            $line4 = $textToList[$startIndex + 4];
            if (!$findData($line4)) {
                $startIndex += 1;
                $line4 = $textToList[$startIndex + 4];
            }
            $line4 = str_replace(':', '', substr($line4, 1));
            $line4Liste = explode('/', $line4);

            $frontInfo->birthLocation->value = trim($line4Liste[0] ?? '');
            $frontInfo->birthPrefecture->value = trim($line4Liste[1] ?? '');
        }

        // SIXIEME LIGNE (Profession)
        if (isset($textToList[$startIndex + 5])) {
            $line5 = $textToList[$startIndex + 5];
            if (!$findData($line5)) {
                $startIndex += 1;
                $line5 = $textToList[$startIndex + 5];
            }
            $frontInfo->profession->value = trim(preg_replace('/[^A-Z  -]/', '', substr($line5, 10)));
        }

        // SEPTIEME LIGNE (Date de delivrance)
        if (isset($textToList[$startIndex + 6])) {
            $line6 = str_replace('O', '0', $textToList[$startIndex + 6]);
            if (!$findData($line6)) {
                $startIndex += 1;
                $line6 = $textToList[$startIndex + 6];
            }
            $line6Liste = explode('/', preg_replace('/[^0-9-\/]/', '', $line6));

            $frontInfo->issueDate->value = trim(str_replace('-', '/', $line6Liste[0] ?? ''));
            $frontInfo->policeOfficeNumber->value = trim($line6Liste[1] ?? '');
        }

        // HUITIEME LIGNE (Date d'expiration)
        if (isset($textToList[$startIndex + 7])) {
            $line7 = $textToList[$startIndex + 7];
            if (!$findData($line7)) {
                $startIndex += 1;
                $line7 = $textToList[$startIndex + 7];
            }
            $frontInfo->expiryDate->value = trim(str_replace('-', '/', preg_replace('/[^-0-9]/', '', substr($line7, 4))));
        }

        return $frontInfo;
    }
}
