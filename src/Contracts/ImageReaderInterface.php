<?php

namespace TgIdProcessor\Contracts;

interface ImageReaderInterface
{
    public function readTextOnImage(string $path): array;
}
