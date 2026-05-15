<?php

namespace TgDocumentProcessor\Drivers\DriverLicense\Parsers;

use TgDocumentProcessor\Drivers\DriverLicense\Models\LicenseData;
use TgDocumentProcessor\Tools\MrzParser;

class LicenseOcrParser
{
    public function parse(array $frontText, array $backText): LicenseData
    {
        $data = new LicenseData();

        // --- Recto parsing ---
        $frontText = MrzParser::cleanOcrText($frontText);
        $allFront = implode("\n", $frontText);

        // License number: NUMERO: 000 054 246
        if (preg_match('/NUMERO\s*:?\s*([\d\s]+)/i', $allFront, $m)) {
            $num = preg_replace('/[^0-9]/', '', $m[1]);
            $data->licenseNumber->value = $num;
        }

        // Last name: NOM: AKATI
        if (preg_match('/NOM\s*:?\s*([A-Z]+)/i', $allFront, $m)) {
            $data->lastName->value = trim($m[1]);
        }

        // First name + birth date + sex (may be merged with NE LE)
        if (preg_match('/PRENOM\s*:?\s*(.+?)(?:NE\s*[\(@]*\s*LE\s*:?\s*|\s*NE\s*:?\s*)/i', $allFront, $m)) {
            $nameRaw = preg_replace('/[^a-zA-Z- ]/', '', trim($m[1]));
            $data->firstName->value = trim(preg_replace('/\s+/', ' ', $nameRaw));
        }

        // Birth date: 14-01-1972
        if (preg_match('/(\d{2})[-\/](\d{2})[-\/](\d{4})/', $allFront, $m)) {
            $data->birthDate->value = $m[1] . '/' . $m[2] . '/' . $m[3];
        }

        // Sex: sex= M or Sexe: M
        if (preg_match('/sex\s*[=:]\s*([MF])/i', $allFront, $m)) {
            $data->sex->value = strtoupper($m[1]);
        }

        // Birth place: a AGOU-GARE, TOGO
        if (preg_match('/a\s+([A-Z][A-Z -]+),?\s*TOGO/i', $allFront, $m)) {
            $place = preg_replace('/\s*\\\\\s*/', ' ', trim($m[1]));
            $data->birthPlace->value = $place;
        }

        // Issue date: DATE DEDELIVRANCE / DATE DE DELIVRANCE / DATE DE LIVRANCE: 08-05-2019
        if (preg_match('/DATE\s+D[E\']?\s*DELIVRANCE\s*:?\s*(\d{2})[-\/](\d{2})[-\/](\d{4})/i', $allFront, $m)) {
            $data->issueDate->value = $m[1] . '/' . $m[2] . '/' . $m[3];
        } elseif (preg_match('/DATE\s+DE\s+LIVRANCE\s*:?\s*(\d{2})[-\/](\d{2})[-\/](\d{4})/i', $allFront, $m)) {
            $data->issueDate->value = $m[1] . '/' . $m[2] . '/' . $m[3];
        }

        // Expiry date: DATE D'EXPIRATION: 07-05-2024
        if (preg_match("/DATE\s+D['\"]?\s*EXPIRATION\s*:?\s*(\d{2})[-\/](\d{2})[-\/](\d{4})/i", $allFront, $m)) {
            $data->expiryDate->value = $m[1] . '/' . $m[2] . '/' . $m[3];
        }

        // --- Verso parsing ---
        $backText = MrzParser::cleanOcrText($backText);
        $allBack = implode("\n", $backText);

        // Categories: CATEGORIE(S) DE VEHICULE(S): B
        if (preg_match('/CATEGORIE.*?VEHICULE.*?:\s*([A-Z,0-9]+)/i', $allBack, $m)) {
            $data->categories->value = trim($m[1]);
        }

        // CNI/Passport number: No. C.N.l\/PASSEPORT: 112 117 250 58
        if (preg_match('/(?:C\.?\s*N\.?\s*(?:I|1|L)\s*|PASSEPORT)\s*:?\s*([\d\s]+)/i', $allBack, $m)) {
            $num = preg_replace('/[^0-9]/', '', $m[1]);
            $data->cniOrPassportNumber->value = $num;
        }

        // Genre: GENRE: R
        if (preg_match('/GENRE\s*:?\s*([A-Z])/i', $allBack, $m)) {
            $data->genre->value = trim($m[1]);
        }

        // Blood type: GROUPE SANGUIN: O+
        if (preg_match('/GROUPE\s*SANGUIN\s*:?\s*([A-Z][+-])/i', $allBack, $m)) {
            $type = str_replace(['0', '8', '4'], ['O', 'B', 'A'], $m[1]);
            $data->bloodType->value = trim($type);
        }

        // Nationality: NATIONALITE: TOGOLAISE
        if (preg_match('/NATIONALITE\s*:?\s*([A-Z]+)/i', $allBack, $m)) {
            $data->nationality->value = trim($m[1]);
        }

        // Address: ADRESSE: QT AGOE ZOSSIME
        if (preg_match('/ADRESSE\s*:?\s*(.+?)(?:\s*RESTRICTIONS|$)/i', $allBack, $m)) {
            $addr = preg_replace('/[^A-Z0-9 -]/', '', trim($m[1]));
            $data->address->value = trim(preg_replace('/\s+/', ' ', $addr));
        }

        // Restrictions: RESTRICTIONS: 90457886 LOME / PAS DE RESTRICTION
        if (preg_match('/RESTRICTIONS\s*:?\s*(.+?)$/i', $allBack, $m)) {
            $rest = preg_replace('/[^A-Z0-9 -]/', '', trim($m[1]));
            $data->restrictions->value = trim(preg_replace('/\s+/', ' ', $rest));
        }
        if (empty($data->restrictions->value) && preg_match('/PAS\s+DE\s+RESTRICTION/i', $allBack)) {
            $data->restrictions->value = 'PAS DE RESTRICTION';
        }

        return $data;
    }
}
