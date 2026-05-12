<?php

namespace TgIdProcessor\Models;

class CardData
{
    public ?Owner $owner;
    public ?string $cardNumber;
    public ?string $policeOfficeNumber;
    public ?string $country;
    public ?string $issueDate;
    public ?string $expiryDate;
    public ?string $documentNumber;

    public function __construct()
    {
        $this->owner = new Owner();
    }
}
