<?php

namespace TgDocumentProcessor\Drivers\DriverLicense\Models;

use TgDocumentProcessor\Models\ValueStat;

class LicenseData
{
    public ValueStat $licenseNumber;
    public ValueStat $lastName;
    public ValueStat $firstName;
    public ValueStat $birthDate;
    public ValueStat $sex;
    public ValueStat $birthPlace;
    public ValueStat $issueDate;
    public ValueStat $expiryDate;
    public ValueStat $categories;
    public ValueStat $genre;
    public ValueStat $bloodType;
    public ValueStat $nationality;
    public ValueStat $address;
    public ValueStat $restrictions;
    public ValueStat $cniOrPassportNumber;

    public function __construct()
    {
        $this->licenseNumber = new ValueStat();
        $this->lastName = new ValueStat();
        $this->firstName = new ValueStat();
        $this->birthDate = new ValueStat();
        $this->sex = new ValueStat();
        $this->birthPlace = new ValueStat();
        $this->issueDate = new ValueStat();
        $this->expiryDate = new ValueStat();
        $this->categories = new ValueStat();
        $this->genre = new ValueStat();
        $this->bloodType = new ValueStat();
        $this->nationality = new ValueStat();
        $this->address = new ValueStat();
        $this->restrictions = new ValueStat();
        $this->cniOrPassportNumber = new ValueStat();
    }
}
