<?php

namespace TgDocumentProcessor\Contracts;

use TgDocumentProcessor\Models\DocumentResult;
use TgDocumentProcessor\Models\DocumentType;

interface DocumentProcessorInterface
{
    public function process(string ...$paths): DocumentResult;

    public function getType(): DocumentType;

    public function expectedInputCount(): int;
}
