<?php

namespace TgIdProcessor\Contracts;

use TgIdProcessor\Models\Front;

interface FrontProcessorInterface
{
    public function processFront(Front $frontInfo, array $text): Front;
}
