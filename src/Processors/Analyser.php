<?php

namespace TgIdProcessor\Processors;

use TgIdProcessor\Contracts\AnalyserInterface;
use TgIdProcessor\Dictionnaries\Dics;
use TgIdProcessor\Models\Front;
use TgIdProcessor\Models\Back;
use TgIdProcessor\Models\Card;

class Analyser implements AnalyserInterface
{
    public function compare(Front $front, Back $back): Card
    {
        $front->firstName->value = str_replace('-', ' ', $front->firstName->value);
        $firstNameIsCorrect = $front->firstName->value === $back->mrzFirstName->value;

        if (strlen($front->firstName->value) >= 13) {
            $firstNameIsCorrect = substr($front->firstName->value, 0, 13) === substr($back->mrzFirstName->value, 0, 13);
        }

        if (strlen($front->firstName->value) < strlen($back->mrzFirstName->value) && substr($back->mrzFirstName->value, -1) === 'K') {
            $firstNameIsCorrect = str_contains($back->mrzFirstName->value, $front->firstName->value);
            if ($firstNameIsCorrect) {
                $back->mrzFirstName->value = substr($back->mrzFirstName->value, 0, -1);
            }
        }
        $front->firstName->stat = $back->mrzFirstName->stat = $firstNameIsCorrect;

        $lastNameIsCorrect = $front->lastName->value === $back->mrzLastName->value;
        if (!$lastNameIsCorrect && strlen($front->lastName->value) >= 12) {
            $lastNameIsCorrect = substr($front->lastName->value, 0, 12) === substr($back->mrzLastName->value, 0, 12);
        }
        $front->lastName->stat = $back->mrzLastName->stat = $lastNameIsCorrect;

        if ($back->mrzLastName->stat === true) {
            if ($back->fatherLastName->value === $back->mrzLastName->value) {
                $back->fatherLastName->stat = true;
            }
            if ($back->motherLastName->value === $back->mrzLastName->value) {
                $back->motherLastName->stat = true;
            }
        }

        $front->birthDate->stat = $back->mrzBirthDate->stat = ($front->birthDate->value === $back->mrzBirthDate->value);
        $front->sex->stat = $back->mrzSex->stat = ($front->sex->value === $back->mrzSex->value);
        $front->expiryDate->stat = $back->mrzExpiryDate->stat = ($front->expiryDate->value === $back->mrzExpiryDate->value);

        if ($back->mrzExpiryDate->stat === true && $front->issueDate->value !== null) {
            $expiryDate = \DateTimeImmutable::createFromFormat('d/m/Y', $back->mrzExpiryDate->value);
            $issueDate = \DateTimeImmutable::createFromFormat('d/m/Y', $front->issueDate->value);

            if ($front->issueDate->value === null) {
                if ($back->mrzExpiryDate->stat === true && $expiryDate) {
                    $front->issueDate->value = $expiryDate->modify('-5 years')->modify('+1 day')->format('d/m/Y');
                    $front->issueDate->stat = true;
                }
            } elseif ($expiryDate && $issueDate) {
                $fiveYearsBeforeExpiry = $expiryDate->modify('-5 years');
                $front->issueDate->stat = $fiveYearsBeforeExpiry < $issueDate->modify('+1 day');
            }
        }

        if ($back->documentNumber->stat !== true) {
            $back->documentNumber->stat = $back->mrzDocumentNumber->stat = ($back->documentNumber->value === $back->mrzDocumentNumber->value);
        }

        if ($back->documentNumber->value !== null) {
            $back->documentNumber->value = $back->mrzDocumentNumber->value;
            $back->documentNumber->stat = $back->mrzDocumentNumber->stat = true;
        }

        $back->bloodType->stat = in_array($back->bloodType->value, Dics::BLOOD_TYPES, true);
        $back->country->stat = $back->country->value === Dics::COUNTRY;

        $back->tel->stat = $this->checkTel($back->tel->value);
        $back->personToContactTel->stat = $this->checkTel($back->personToContactTel->value);

        if (!empty($back->size->value)) {
            $back->size->stat = true;
            $size = (float) str_replace(',', '.', $back->size->value);
            if ($size > 2.55) {
                $back->size->stat = false;
            }
            if ($size < 1) {
                $birthDate = \DateTimeImmutable::createFromFormat('d/m/Y', $back->mrzBirthDate->value);
                $issueDate = \DateTimeImmutable::createFromFormat('d/m/Y', $front->issueDate->value);
                if ($birthDate && $issueDate && ($issueDate->format('Y') - $birthDate->format('Y') < 5)) {
                    $back->size->stat = false;
                }
            }
        }

        $back->particularSign->stat = in_array($back->particularSign->value, Dics::PARTICULAR_SIGNS, true);

        $front->birthLocation->stat = Dics::localExist((string) $front->birthLocation->value);
        $front->birthPrefecture->stat = Dics::localExist((string) $front->birthPrefecture->value);
        $back->address->stat = Dics::localExist((string) $back->address->value);
        $back->personToContactAddress->stat = Dics::localExist((string) $back->personToContactAddress->value);

        $cardNumberStr = $front->cardNumber->value ?? '';
        $front->cardNumber->stat = substr_count($cardNumberStr, '-') === 2 && strlen(str_replace('-', '', $cardNumberStr)) === 11;

        $card = new Card();
        $card->front = $front;
        $card->back = $back;

        if (!empty($back->mrzExpiryDate->value)) {
            $expiry = \DateTimeImmutable::createFromFormat('d/m/Y', $back->mrzExpiryDate->value);
            $card->isExpired = $expiry && $expiry < new \DateTimeImmutable();
        }

        return $card;
    }

    private function checkTel(?string $tel): bool
    {
        if ($tel === null || $tel === '') {
            return false;
        }
        if (strlen($tel) === 8 && preg_match('/^[0-9]+$/', $tel)) {
            $isCorrect = in_array(substr($tel, 0, 2), Dics::PHONE_NUMBER_STARTERS, true);
            foreach (str_split($tel) as $i) {
                if (substr_count($tel, $i) > 4) {
                    $isCorrect = false;
                }
                break;
            }
            return $isCorrect;
        }
        return false;
    }
}
