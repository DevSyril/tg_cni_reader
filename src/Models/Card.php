<?php

namespace TgIdProcessor\Models;

class Card
{
    public Front $front;
    public Back $back;
    public ?bool $isExpired;
    public ?bool $isInvalid;
}
