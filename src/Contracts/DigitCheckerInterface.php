<?php

namespace TgIdProcessor\Contracts;

use TgIdProcessor\Models\Back;

interface DigitCheckerInterface
{
    public function check(array $backText, Back $backData): bool;
}
