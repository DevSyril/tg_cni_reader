<?php

namespace TgDocumentProcessor\Drivers\Passport\Models;

use TgDocumentProcessor\Models\ValueStat;

class PassportData
{
    public ValueStat $passportNumber;
    public ValueStat $lastName;
    public ValueStat $firstName;
    public ValueStat $nationality;
    public ValueStat $birthDate;
    public ValueStat $sex;
    public ValueStat $expiryDate;
    public ValueStat $issuingCountry;
    public ValueStat $personalNumber;

    // Fields from visible OCR text (when available)
    public ValueStat $visibleLastName;
    public ValueStat $visibleFirstName;
    public ValueStat $visibleBirthDate;
    public ValueStat $visibleBirthPlace;
    public ValueStat $visibleIssueDate;
    public ValueStat $visibleExpiryDate;
    public ValueStat $visibleProfession;
    public ValueStat $visibleAuthority;

    public function __construct()
    {
        $this->passportNumber = new ValueStat();
        $this->lastName = new ValueStat();
        $this->firstName = new ValueStat();
        $this->nationality = new ValueStat();
        $this->birthDate = new ValueStat();
        $this->sex = new ValueStat();
        $this->expiryDate = new ValueStat();
        $this->issuingCountry = new ValueStat();
        $this->personalNumber = new ValueStat();

        $this->visibleLastName = new ValueStat();
        $this->visibleFirstName = new ValueStat();
        $this->visibleBirthDate = new ValueStat();
        $this->visibleBirthPlace = new ValueStat();
        $this->visibleIssueDate = new ValueStat();
        $this->visibleExpiryDate = new ValueStat();
        $this->visibleProfession = new ValueStat();
        $this->visibleAuthority = new ValueStat();
    }
}
