<?php

namespace TgDocumentProcessor\Models;

class ValueStat
{
    public ?string $value;
    public ?bool $stat;

    public function __construct(?string $value = null, ?bool $stat = null)
    {
        $this->value = $value;
        $this->stat = $stat;
    }
}
