<?php

namespace TgDocumentProcessor\Contracts;

interface PdfConverterInterface
{
    public function transformPdfToImage(string $pdfPath): string;
}
