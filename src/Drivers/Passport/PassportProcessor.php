<?php

namespace TgDocumentProcessor\Drivers\Passport;

use TgDocumentProcessor\Contracts\DocumentProcessorInterface;
use TgDocumentProcessor\Contracts\ImageReaderInterface;
use TgDocumentProcessor\Contracts\PdfConverterInterface;
use TgDocumentProcessor\Drivers\Passport\Parsers\PassportOcrParser;
use TgDocumentProcessor\Drivers\Passport\Validators\PassportValidator;
use TgDocumentProcessor\Models\DocumentResult;
use TgDocumentProcessor\Models\DocumentType;

class PassportProcessor implements DocumentProcessorInterface
{
    private ImageReaderInterface $imageReader;
    private PdfConverterInterface $pdfConverter;
    private PassportOcrParser $parser;
    private PassportValidator $validator;

    public function __construct(
        ImageReaderInterface $imageReader,
        PdfConverterInterface $pdfConverter,
        ?PassportOcrParser $parser = null,
        ?PassportValidator $validator = null
    ) {
        $this->imageReader = $imageReader;
        $this->pdfConverter = $pdfConverter;
        $this->parser = $parser ?? new PassportOcrParser();
        $this->validator = $validator ?? new PassportValidator();
    }

    public function getType(): DocumentType
    {
        return DocumentType::PASSPORT;
    }

    public function expectedInputCount(): int
    {
        return 1;
    }

    public function process(string ...$paths): DocumentResult
    {
        $count = count($paths);

        if ($count < 1) {
            throw new \InvalidArgumentException('Passport processor expects at least 1 file path.');
        }

        $filePath = $paths[0];
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($extension, ['pdf'], true)) {
            $imagePath = $this->pdfConverter->transformPdfToImage($filePath);
            return $this->processFromImage($imagePath);
        }

        return $this->processFromImage($filePath);
    }

    private function processFromImage(string $imagePath): DocumentResult
    {
        $text = $this->imageReader->readTextOnImage($imagePath);
        $data = $this->parser->parse($text);
        return $this->validator->validate($data);
    }
}
