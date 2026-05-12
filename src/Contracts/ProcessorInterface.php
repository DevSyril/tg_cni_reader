<?php

namespace TgIdProcessor\Contracts;

use TgIdProcessor\Models\Front;
use TgIdProcessor\Models\Back;

interface ProcessorInterface
{
    public function processBack(Back $back, array $text): array;

    public function processFront(Front $front, array $text): Front;
}
