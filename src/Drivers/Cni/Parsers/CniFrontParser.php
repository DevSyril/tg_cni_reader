<?php

namespace TgDocumentProcessor\Drivers\Cni\Parsers;

use TgDocumentProcessor\Drivers\Cni\Models\CniFront;

class CniFrontParser
{
    public function parse(CniFront $frontInfo, array $textToList): CniFront
    {
        $numeroIdx = null;
        foreach ($textToList as $i => $item) {
            if (str_contains($item, 'Numero') || str_contains($item, 'Numer')) {
                $numeroIdx = $i;
                break;
            }
        }
        if ($numeroIdx === null) {
            return $frontInfo;
        }

        $line0 = $textToList[$numeroIdx];
        $frontInfo->cardNumber->value = trim(preg_replace('/[^0-9-]/', '', substr($line0, 6)));

        if (str_contains($line0, 'Nom:')) {
            $nomPart = substr($line0, strpos($line0, 'Nom:') + 4);
            $frontInfo->lastName->value = trim(preg_replace('/[^A-Z]/', '', $nomPart));
        }

        $remaining = array_values(array_slice($textToList, $numeroIdx + 1));

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

        $extractDate = function (string $text): ?string {
            if (preg_match('/(\d{2})[-\/](\d{2})[-\/](\d{4})/', $text, $m)) {
                return $m[1] . '/' . $m[2] . '/' . $m[3];
            }
            return null;
        };

        if ($frontInfo->lastName->value === null) {
            $nomIdx = $findLine(['Nom:'], $remaining);
            if ($nomIdx !== null) {
                $line = $remaining[$nomIdx];
                $frontInfo->lastName->value = trim(preg_replace('/[^A-Z]/', '', substr($line, 3)));
                unset($remaining[$nomIdx]);
                $remaining = array_values($remaining);
            }
        }

        $prenomIdx = $findLine(['Prenom:'], $remaining);
        if ($prenomIdx !== null) {
            $line = $remaining[$prenomIdx];
            $afterLabel = substr($line, 6);

            if (preg_match('/Ne le\s*:?\s*/i', $afterLabel, $m, PREG_OFFSET_CAPTURE)) {
                $splitPos = $m[0][1];
                $namePart = trim(substr($afterLabel, 0, $splitPos));
                $restPart = trim(substr($afterLabel, $splitPos + strlen($m[0][0])));

                $frontInfo->firstName->value = trim(preg_replace('/[^a-zA-Z- ]/', '', $namePart));

                $date = $extractDate($restPart);
                if ($date !== null) {
                    $frontInfo->birthDate->value = $date;
                }
                if (preg_match('/Sexe\s*:?\s*([MF])/i', $restPart, $sm)) {
                    $frontInfo->sex->value = strtoupper($sm[1]);
                }
                if (preg_match('/Sexe\s*:?\s*[MF]\s+(.+?)$/i', $restPart, $lm)) {
                    $locStr = trim($lm[1]);
                    $locStr = preg_replace('/^A\s+/i', '', $locStr);
                    $locParts = explode('/', $locStr);
                    $frontInfo->birthLocation->value = trim(preg_replace('/[^A-Z ]/', '', $locParts[0] ?? ''));
                    $frontInfo->birthPrefecture->value = trim(preg_replace('/[^A-Z ]/', '', $locParts[1] ?? ''));
                }
            } else {
                $frontInfo->firstName->value = trim(preg_replace('/[^a-zA-Z- ]/', '', $afterLabel));
            }

            unset($remaining[$prenomIdx]);
            $remaining = array_values($remaining);
        }

        if ($frontInfo->birthDate->value === null || $frontInfo->sex->value === null || $frontInfo->birthLocation->value === null) {
            $neLeIdx = $findLine(['Ne le'], $remaining);
            if ($neLeIdx !== null) {
                $line = $remaining[$neLeIdx];

                if ($frontInfo->birthDate->value === null) {
                    $date = $extractDate($line);
                    if ($date !== null) {
                        $frontInfo->birthDate->value = $date;
                    }
                }
                if ($frontInfo->sex->value === null) {
                    $frontInfo->sex->value = trim(preg_replace('/[^MF]/', '', $line));
                }
                if ($frontInfo->birthLocation->value === null) {
                    if (preg_match('/Sexe\s*:?\s*[MF]\s+(.+?)$/i', $line, $lm)) {
                        $locStr = trim($lm[1]);
                        $locStr = preg_replace('/^A\s+/i', '', $locStr);
                        $locParts = explode('/', $locStr);
                        $frontInfo->birthLocation->value = trim(preg_replace('/[^A-Z ]/', '', $locParts[0] ?? ''));
                        $frontInfo->birthPrefecture->value = trim(preg_replace('/[^A-Z ]/', '', $locParts[1] ?? ''));
                    }
                }

                unset($remaining[$neLeIdx]);
                $remaining = array_values($remaining);
            }
        }

        if ($frontInfo->birthLocation->value === null || $frontInfo->birthPrefecture->value === null) {
            $aIdx = null;
            foreach ($remaining as $i => $line) {
                $trimmed = trim($line);
                if (preg_match('/^A\s*:\s*/i', $trimmed)) {
                    $aIdx = $i;
                    break;
                }
            }
            if ($aIdx !== null) {
                $line = $remaining[$aIdx];
                $afterA = preg_replace('/^A\s*:\s*/i', '', trim($line));
                $locParts = explode('/', $afterA);
                $frontInfo->birthLocation->value = trim(preg_replace('/[^A-Z -]/', '', $locParts[0] ?? ''));
                $frontInfo->birthPrefecture->value = trim(preg_replace('/[^A-Z -]/', '', $locParts[1] ?? ''));
                unset($remaining[$aIdx]);
                $remaining = array_values($remaining);
            }
        }

        $profIdx = $findLine(['Profession:'], $remaining);
        if ($profIdx !== null) {
            $line = $remaining[$profIdx];

            $professionRaw = substr($line, 10);
            if (preg_match('/^([A-Z ]+?)\s+Fait le\s*:/i', $professionRaw, $pm)) {
                $frontInfo->profession->value = trim($pm[1]);
            } else {
                $frontInfo->profession->value = trim(preg_replace('/[^A-Z  -]/', '', $professionRaw));
            }

            if (preg_match('/Fait le\s*:?\s*(\d{2}[-\/]\d{2}[-\/]\d{4})/', $line, $fm)) {
                $dateStr = str_replace('-', '/', $fm[1]);
                $frontInfo->issueDate->value = $dateStr;
            }
            if (preg_match('/\d{2}[-\/]\d{2}[-\/]\d{4}[\/ ]+(\d+)/', $line, $om)) {
                $frontInfo->policeOfficeNumber->value = $om[1];
            }

            unset($remaining[$profIdx]);
            $remaining = array_values($remaining);
        }

        $expIdx = $findLine(['Expirele', 'Expire le', 'Expire'], $remaining);
        if ($expIdx !== null) {
            $line = $remaining[$expIdx];
            $date = $extractDate($line);
            if ($date !== null) {
                $frontInfo->expiryDate->value = $date;
            }
            unset($remaining[$expIdx]);
            $remaining = array_values($remaining);
        }

        if ($frontInfo->issueDate->value === null) {
            foreach ($remaining as $line) {
                if (str_contains($line, 'Expire') || str_contains($line, 'Expirele')) {
                    continue;
                }
                $date = $extractDate($line);
                if ($date !== null) {
                    $frontInfo->issueDate->value = $date;
                    if (preg_match('/\d{2}[-\/]\d{2}[-\/]\d{4}[\/ ]+(\d+)/', $line, $om)) {
                        $frontInfo->policeOfficeNumber->value = $om[1];
                    }
                    break;
                }
            }
        }

        return $frontInfo;
    }
}
