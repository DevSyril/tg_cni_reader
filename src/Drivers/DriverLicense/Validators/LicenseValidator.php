<?php

namespace TgDocumentProcessor\Drivers\DriverLicense\Validators;

use TgDocumentProcessor\Dictionnaries\Dics;
use TgDocumentProcessor\Drivers\DriverLicense\Models\LicenseData;
use TgDocumentProcessor\Models\DocumentResult;
use TgDocumentProcessor\Models\DocumentType;

class LicenseValidator
{
    public function validate(LicenseData $data): DocumentResult
    {
        $result = new DocumentResult(DocumentType::DRIVER_LICENSE);

        $result->isValid = true;

        if (!empty($data->expiryDate->value)) {
            $expiry = \DateTimeImmutable::createFromFormat('d/m/Y', $data->expiryDate->value);
            $result->isExpired = $expiry && $expiry < new \DateTimeImmutable();
        }

        if ($data->bloodType->value !== null) {
            $data->bloodType->stat = in_array($data->bloodType->value, Dics::BLOOD_TYPES, true);
        }

        $result->addField('last_name', $data->lastName->value)
            ->addField('first_name', $data->firstName->value)
            ->addField('birth_date', $data->birthDate->value)
            ->addField('sex', $data->sex->value)
            ->addField('birth_place', $data->birthPlace->value)
            ->addField('issue_date', $data->issueDate->value)
            ->addField('expiry_date', $data->expiryDate->value)
            ->addField('license_number', $data->licenseNumber->value)
            ->addField('categories', $data->categories->value)
            ->addField('genre', $data->genre->value)
            ->addField('blood_type', $data->bloodType->value, $data->bloodType->stat)
            ->addField('nationality', $data->nationality->value)
            ->addField('address', $data->address->value)
            ->addField('restrictions', $data->restrictions->value)
            ->addField('cni_or_passport_number', $data->cniOrPassportNumber->value);

        return $result;
    }
}
