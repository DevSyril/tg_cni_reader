<?php

namespace TgIdProcessor\Models;

class Front
{
    public ValueStat $cardNumber;
    public ValueStat $lastName;
    public ValueStat $firstName;
    public ValueStat $birthDate;
    public ValueStat $sex;
    public ValueStat $birthLocation;
    public ValueStat $birthPrefecture;
    public ValueStat $profession;
    public ValueStat $issueDate;
    public ValueStat $policeOfficeNumber;
    public ValueStat $expiryDate;

    public function __construct()
    {
        $this->cardNumber = new ValueStat();
        $this->lastName = new ValueStat();
        $this->firstName = new ValueStat();
        $this->birthDate = new ValueStat();
        $this->sex = new ValueStat();
        $this->birthLocation = new ValueStat();
        $this->birthPrefecture = new ValueStat();
        $this->profession = new ValueStat();
        $this->issueDate = new ValueStat();
        $this->policeOfficeNumber = new ValueStat();
        $this->expiryDate = new ValueStat();
    }
}
