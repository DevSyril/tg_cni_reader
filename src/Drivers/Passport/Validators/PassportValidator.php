<?php

namespace TgDocumentProcessor\Drivers\Passport\Validators;

use TgDocumentProcessor\Dictionnaries\Dics;
use TgDocumentProcessor\Drivers\Passport\Models\PassportData;
use TgDocumentProcessor\Models\DocumentResult;
use TgDocumentProcessor\Models\DocumentType;

class PassportValidator
{
    public function validate(PassportData $data): DocumentResult
    {
        $result = new DocumentResult(DocumentType::PASSPORT);

        if ($data->passportNumber->value === null) {
            $result->isValid = false;
            $result->errors[] = 'Numéro de passeport non trouvé';
        } else {
            $result->isValid = $data->passportNumber->stat;
        }

        if (!empty($data->expiryDate->value)) {
            $expiry = \DateTimeImmutable::createFromFormat('d/m/Y', $data->expiryDate->value);
            $result->isExpired = $expiry && $expiry < new \DateTimeImmutable();
        }

        $result->addField('passport_number', $data->passportNumber->value, $data->passportNumber->stat)
            ->addField('last_name', $data->lastName->value)
            ->addField('first_name', $data->firstName->value)
            ->addField('nationality', $data->nationality->value)
            ->addField('birth_date', $data->birthDate->value, $data->birthDate->stat)
            ->addField('sex', $data->sex->value)
            ->addField('expiry_date', $data->expiryDate->value, $data->expiryDate->stat)
            ->addField('issuing_country', $data->issuingCountry->value)
            ->addField('personal_number', $data->personalNumber->value)

            ->addField('full_name_visible', $data->visibleLastName->value)
            ->addField('given_names_visible', $data->visibleFirstName->value)
            ->addField('birth_date_visible', $data->visibleBirthDate->value)
            ->addField('birth_place_visible', $data->visibleBirthPlace->value)
            ->addField('issue_date_visible', $data->visibleIssueDate->value)
            ->addField('expiry_date_visible', $data->visibleExpiryDate->value)
            ->addField('profession_visible', $data->visibleProfession->value)
            ->addField('authority_visible', $data->visibleAuthority->value);

        return $result;
    }
}
