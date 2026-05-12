<?php

namespace TgIdProcessor\Contracts;

use TgIdProcessor\Models\Back;

interface BackProcessorInterface
{
    public function processBack(Back $backInfo, array $text): array;
}
