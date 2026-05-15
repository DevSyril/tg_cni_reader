<?php

namespace TgDocumentProcessor\Drivers\DriverLicense;

use TgDocumentProcessor\Contracts\DocumentProcessorInterface;
use TgDocumentProcessor\Contracts\ImageReaderInterface;
use TgDocumentProcessor\Contracts\PdfConverterInterface;
use TgDocumentProcessor\Drivers\DriverLicense\Parsers\LicenseOcrParser;
use TgDocumentProcessor\Drivers\DriverLicense\Validators\LicenseValidator;
use TgDocumentProcessor\Models\DocumentResult;
use TgDocumentProcessor\Models\DocumentType;

class LicenseProcessor implements DocumentProcessorInterface
{
    private ImageReaderInterface $imageReader;
    private PdfConverterInterface $pdfConverter;
    private LicenseOcrParser $parser;
    private LicenseValidator $validator;

    public function __construct(
        ImageReaderInterface $imageReader,
        PdfConverterInterface $pdfConverter,
        ?LicenseOcrParser $parser = null,
        ?LicenseValidator $validator = null
    ) {
        $this->imageReader = $imageReader;
        $this->pdfConverter = $pdfConverter;
        $this->parser = $parser ?? new LicenseOcrParser();
        $this->validator = $validator ?? new LicenseValidator();
    }

    public function getType(): DocumentType
    {
        return DocumentType::DRIVER_LICENSE;
    }

    public function expectedInputCount(): int
    {
        return 2;
    }

    public function process(string ...$paths): DocumentResult
    {
        $count = count($paths);

        if ($count === 1) {
            $filePath = $paths[0];
            $pathInfo = pathinfo($filePath);
            $extension = strtolower($pathInfo['extension'] ?? '');

            if (in_array($extension, ['pdf'], true)) {
                $imagePath = $this->pdfConverter->transformPdfToImage($filePath);
                return $this->processFromImages($imagePath, $imagePath);
            }

            return $this->processFromImages($filePath, $filePath);
        }

        if ($count >= 2) {
            $frontPath = $paths[0];
            $backPath = $paths[1];

            $frontExt = strtolower(pathinfo($frontPath, PATHINFO_EXTENSION));
            $backExt = strtolower(pathinfo($backPath, PATHINFO_EXTENSION));

            if (in_array($frontExt, ['pdf'], true)) {
                $frontPath = $this->pdfConverter->transformPdfToImage($frontPath);
            }
            if (in_array($backExt, ['pdf'], true)) {
                $backPath = $this->pdfConverter->transformPdfToImage($backPath);
            }

            return $this->processFromImages($frontPath, $backPath);
        }

        throw new \InvalidArgumentException('Driver license processor expects at least 1 file path.');
    }

    private function processFromImages(string $frontPath, string $backPath): DocumentResult
    {
        $frontText = $this->imageReader->readTextOnImage($frontPath);
        $backText = $this->imageReader->readTextOnImage($backPath);

        $data = $this->parser->parse($frontText, $backText);

        return $this->validator->validate($data);
    }
}
