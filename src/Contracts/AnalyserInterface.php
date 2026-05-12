<?php

namespace TgIdProcessor\Contracts;

use TgIdProcessor\Models\Front;
use TgIdProcessor\Models\Back;
use TgIdProcessor\Models\Card;

interface AnalyserInterface
{
    public function compare(Front $front, Back $back): Card;
}
