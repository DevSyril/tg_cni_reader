<?php

namespace TgIdProcessor\Contracts;

interface PdfConverterInterface
{
    public function transformPdfToImage(string $pdfPath): string;
}
