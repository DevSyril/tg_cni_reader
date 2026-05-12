<?php

namespace TgIdProcessor\Contracts;

use TgIdProcessor\Models\Card;

interface CniProcessInterface
{
    public function processCNI(string $frontPath, ?string $backPath = null): Card;

    public function processCNIPdf(string $filePath): Card;
}
