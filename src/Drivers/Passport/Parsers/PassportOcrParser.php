<?php

namespace TgDocumentProcessor\Drivers\Passport\Parsers;

use TgDocumentProcessor\Drivers\Passport\Models\PassportData;
use TgDocumentProcessor\Tools\MrzParser;

class PassportOcrParser
{
    public function parse(array $text): PassportData
    {
        $data = new PassportData();
        $text = MrzParser::cleanOcrText($text);

        // --- Visible text fields (when OCR captures them) ---
        $allText = implode("\n", $text);

        // NOM / NAME / SURNAME: in visible text
        if (preg_match('/(?:NOM|SURNAME|SURNOM)[ \t]*:?[ \t]*([A-Z]{2,}(?:[ \t]+[A-Z]+)*)/i', $allText, $m)) {
            $data->visibleLastName->value = trim($m[1]);
        }

        // PRENOMS / GIVEN NAME(S) / FIRST NAME(S):
        if (preg_match('/(?:PRENOMS?|GIVEN NAMES?|PRENOM)\s*:?\s*([A-Za-z -]+?)(?:\s+N(?:E|A)|\s+DATE|\n|$)/i', $allText, $m)) {
            $data->visibleFirstName->value = trim(preg_replace('/[^a-zA-Z -]/', '', $m[1]));
        }

        // Visible birth date
        if (preg_match('/(?:DATE[ \t]*DE[ \t]*NAISSANCE|DATE[ \t]*OF[ \t]*BIRTH|NE[ \t]*[LE]+[ \t]*:?)[ \t]*:?[ \t]*(\d{2})[-\/](\d{2})[-\/](\d{4})/i', $allText, $m)) {
            $data->visibleBirthDate->value = $m[1] . '/' . $m[2] . '/' . $m[3];
        }

        // Visible issue date
        if (preg_match('/(?:DATE[ \t]*DE[ \t]*DELIVRANCE|DATE[ \t]*OF[ \t]*ISSUE|FAIT[ \t]*[LE]+)[ \t]*:?[ \t]*(\d{2})[-\/](\d{2})[-\/](\d{4})/i', $allText, $m)) {
            $data->visibleIssueDate->value = $m[1] . '/' . $m[2] . '/' . $m[3];
        }

        // Visible expiry date
        if (preg_match("/(?:DATE[ \t]*D['\"]?[ \t]*EXPIRATION|DATE[ \t]*OF[ \t]*EXPIRY|EXPIRATION|EXPIRE[ \t]*LE)[ \t]*:?[ \t]*(\d{2})[-\/](\d{2})[-\/](\d{4})/i", $allText, $m)) {
            $data->visibleExpiryDate->value = $m[1] . '/' . $m[2] . '/' . $m[3];
        }

        // Birth place
        if (preg_match('/(?:LIEU[ \t]*DE[ \t]*NAISSANCE|PLACE[ \t]*OF[ \t]*BIRTH|A[ \t]*:?[ \t]*)([A-Z][A-Z \t-]+?)(?:[ \t]*\/|[ \t]*$|TOGO)/i', $allText, $m)) {
            $data->visibleBirthPlace->value = trim(preg_replace('/[^A-Z -]/', '', $m[1]));
        }

        // Authority
        if (preg_match('/(?:AUTORITE|AUTHORITY|DELIVRE PAR)[ \t]*:?[ \t]*([A-Za-z \t-]+?)(?:[ \t]*\/|[ \t]*$)/i', $allText, $m)) {
            $data->visibleAuthority->value = trim($m[1]);
        }

        // Profession (Togolese passport sometimes has it)
        if (preg_match('/PROFESSION[ \t]*:?[ \t]*([A-Z ]{2,})/i', $allText, $m)) {
            $data->visibleProfession->value = trim($m[1]);
        }

        // --- MRZ parsing (TD3 format: 2 lines, 44 chars, starts with P<) ---
        $mrzLines = [];
        foreach ($text as $line) {
            $trimmed = trim($line);
            $noSpaces = str_replace(' ', '', $trimmed);
            if (str_starts_with($noSpaces, 'P<') || preg_match('/^[A-Z0-9<]{44}$/', $noSpaces)) {
                $mrzLines[] = $noSpaces;
            }
        }

        // If we found at least 2 lines that might be MRZ
        if (count($mrzLines) >= 2) {
            // The first line should start with P
            $line1 = $mrzLines[0];
            $line2 = $mrzLines[1];

            // If line1 doesn't start with P but line2 does, swap
            if (!str_starts_with($line1, 'P') && str_starts_with($line2, 'P')) {
                $line1 = $mrzLines[1];
                $line2 = $mrzLines[0];
            }

            if (str_starts_with($line1, 'P')) {
                $this->parseMrzLine1($line1, $data);
                $this->parseMrzLine2($line2, $data);
            }
        }

        // Fallback: if MRZ was found at the end of lines after non-MRZ content
        if ($data->passportNumber->value === null) {
            $mrzCandidates = [];
            foreach ($text as $line) {
                $trimmed = trim(str_replace(' ', '', $line));
                if (preg_match('/[A-Z0-9<]{20,}/', $trimmed)) {
                    $mrzCandidates[] = $trimmed;
                }
            }
            if (count($mrzCandidates) >= 2) {
                // Last two heavily chevroned lines are likely MRZ
                $mrzCandidates = array_filter($mrzCandidates, fn($l) => substr_count($l, '<') > 5);
                $mrzCandidates = array_values($mrzCandidates);
                $lastIdx = count($mrzCandidates) - 1;
                if ($lastIdx >= 1) {
                    $this->parseMrzLine1($mrzCandidates[$lastIdx - 1], $data);
                    $this->parseMrzLine2($mrzCandidates[$lastIdx], $data);
                }
            }
        }

        // Prefer visible text over MRZ (more reliable OCR)
        if ($data->visibleLastName->value !== null) {
            $data->lastName->value = $data->visibleLastName->value;
        }
        if ($data->visibleFirstName->value !== null) {
            $data->firstName->value = $data->visibleFirstName->value;
        }
        if ($data->visibleBirthDate->value !== null) {
            $data->birthDate->value = $data->visibleBirthDate->value;
        }
        if ($data->visibleExpiryDate->value !== null) {
            $data->expiryDate->value = $data->visibleExpiryDate->value;
        }

        return $data;
    }

    private function parseMrzLine1(string $line, PassportData $data): void
    {
        // TD3 Line 1: P<TGOBATO<<TAREKPESSOU... (44 chars)
        // Position 0: Type (P)
        // Position 1: < filler
        // Position 2-4: Issuing country (TGO)
        // Position 5-43: Name (surname << given names, padded with <)

        $data->issuingCountry->value = substr($line, 2, 3);

        // Extract name portion
        $nameField = substr($line, 5);

        // Split by << to get surname and given names
        $nameParts = preg_split('/<{2,}/', $nameField);
        if (isset($nameParts[0]) && $nameParts[0] !== '') {
            $surname = str_replace('<', '', $nameParts[0]);
            $data->lastName->value = $surname;
        }
        if (isset($nameParts[1]) && $nameParts[1] !== '') {
            $givenNames = str_replace('<', ' ', $nameParts[1]);
            $givenNames = trim(preg_replace('/\s+/', ' ', $givenNames));
            $data->firstName->value = $givenNames;
        }
    }

    private function parseMrzLine2(string $line, PassportData $data): void
    {
        $line = str_replace(['O', 'S'], ['0', '5'], $line);

        // Passport number: positions 0-8 (9 chars, right-padded with <)
        $passportNumRaw = substr($line, 0, 9);
        $passportNum = rtrim($passportNumRaw, '<');
        $data->passportNumber->value = $passportNum;

        // Check digit: always at position 9
        $checkChar = substr($line, 9, 1);
        $data->passportNumber->stat = MrzParser::verifyCheckDigit(
            $passportNum,
            $checkChar
        );

        // Remaining part after doc number + check digit
        // Nationality is first alpha chars, then YYMMDD digits, then check digit, then M/F, then YYMMDD, etc.
        $rest = substr($line, 10);
        // $rest looks like: "TG00010160M2807317<<<<<<<<<<<<<<08"

        // Scan for the sex marker (M/F) to anchor positions
        $sexPos = null;
        for ($i = 0; $i < strlen($rest); $i++) {
            if (in_array($rest[$i], ['M', 'F'], true) && $i >= 5) {
                // Verify: should be preceded by a digit (check digit) and followed by digits (expiry)
                if ($i > 0 && ctype_digit($rest[$i - 1] ?? '0')) {
                    $sexPos = $i;
                    break;
                }
            }
        }

        if ($sexPos === null) {
            return; // Can't reliably parse, MRZ format unexpected
        }

        // Nationality: everything from start to where digits begin, excluding the filler
        $nationality = '';
        for ($i = 0; $i < $sexPos; $i++) {
            if (ctype_alpha($rest[$i])) {
                $nationality .= $rest[$i];
            }
        }
        $data->nationality->value = $nationality;

        // Birth date: 6 digits before the check digit that precedes sex
        $birthRaw = substr($rest, $sexPos - 7, 6);
        $birthCheck = substr($rest, $sexPos - 1, 1) ?: '';

        if (strlen($birthRaw) === 6 && ctype_digit($birthRaw)) {
            $birthDate = MrzParser::parseDate(
                substr($birthRaw, 0, 2),
                substr($birthRaw, 2, 2),
                substr($birthRaw, 4, 2),
                30
            );
            if ($birthDate !== null) {
                $data->birthDate->value = $birthDate;
            }
            $data->birthDate->stat = MrzParser::verifyCheckDigit($birthRaw, $birthCheck);
        }

        // Sex
        $data->sex->value = $rest[$sexPos] ?? null;

        // Expiry date: 6 digits after sex
        $expRaw = substr($rest, $sexPos + 1, 6);
        $expCheck = substr($rest, $sexPos + 7, 1) ?: '';

        if (strlen($expRaw) === 6 && ctype_digit($expRaw)) {
            $expDate = MrzParser::parseDate(
                substr($expRaw, 0, 2),
                substr($expRaw, 2, 2),
                substr($expRaw, 4, 2),
                30
            );
            if ($expDate !== null) {
                $data->expiryDate->value = $expDate;
            }
            $data->expiryDate->stat = MrzParser::verifyCheckDigit($expRaw, $expCheck);
        }
    }
}
