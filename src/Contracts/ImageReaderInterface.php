<?php

namespace TgDocumentProcessor\Contracts;

interface ImageReaderInterface
{
    public function readTextOnImage(string $path): array;
}
