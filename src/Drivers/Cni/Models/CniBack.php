<?php

namespace TgDocumentProcessor\Drivers\Cni\Models;

use TgDocumentProcessor\Models\ValueStat;

class CniBack
{
    public ValueStat $size;
    public ValueStat $bloodType;
    public ValueStat $address;
    public ValueStat $tel;
    public ValueStat $particularSign;
    public ValueStat $documentNumber;
    public ValueStat $fatherFirstName;
    public ValueStat $fatherLastName;
    public ValueStat $motherFirstName;
    public ValueStat $motherLastName;
    public ValueStat $personToContactName;
    public ValueStat $personToContactAddress;
    public ValueStat $personToContactTel;
    public ValueStat $country;
    public ValueStat $mrzDocumentNumber;
    public ValueStat $mrzBirthDate;
    public ValueStat $mrzSex;
    public ValueStat $mrzExpiryDate;
    public ValueStat $mrzLastName;
    public ValueStat $mrzFirstName;

    public function __construct()
    {
        $this->size = new ValueStat();
        $this->bloodType = new ValueStat();
        $this->address = new ValueStat();
        $this->tel = new ValueStat();
        $this->particularSign = new ValueStat();
        $this->documentNumber = new ValueStat();
        $this->fatherFirstName = new ValueStat();
        $this->fatherLastName = new ValueStat();
        $this->motherFirstName = new ValueStat();
        $this->motherLastName = new ValueStat();
        $this->personToContactName = new ValueStat();
        $this->personToContactAddress = new ValueStat();
        $this->personToContactTel = new ValueStat();
        $this->country = new ValueStat();
        $this->mrzDocumentNumber = new ValueStat();
        $this->mrzBirthDate = new ValueStat();
        $this->mrzSex = new ValueStat();
        $this->mrzExpiryDate = new ValueStat();
        $this->mrzLastName = new ValueStat();
        $this->mrzFirstName = new ValueStat();
    }
}
