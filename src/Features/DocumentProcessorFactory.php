<?php

namespace TgDocumentProcessor\Features;

use TgDocumentProcessor\Contracts\DocumentProcessorInterface;
use TgDocumentProcessor\Contracts\ImageReaderInterface;
use TgDocumentProcessor\Contracts\PdfConverterInterface;
use TgDocumentProcessor\Drivers\Cni\CniProcessor;
use TgDocumentProcessor\Drivers\DriverLicense\LicenseProcessor;
use TgDocumentProcessor\Drivers\Passport\PassportProcessor;
use TgDocumentProcessor\Models\DocumentType;
use TgDocumentProcessor\Tools\ImageReader;
use TgDocumentProcessor\Tools\PdfConverter;

class DocumentProcessorFactory
{
    private ImageReaderInterface $imageReader;
    private PdfConverterInterface $pdfConverter;

    public function __construct(
        ?ImageReaderInterface $imageReader = null,
        ?PdfConverterInterface $pdfConverter = null
    ) {
        $this->imageReader = $imageReader ?? new ImageReader();
        $this->pdfConverter = $pdfConverter ?? new PdfConverter();
    }

    public function create(DocumentType $type): DocumentProcessorInterface
    {
        return match ($type) {
            DocumentType::CNI => new CniProcessor($this->imageReader, $this->pdfConverter),
            DocumentType::PASSPORT => new PassportProcessor($this->imageReader, $this->pdfConverter),
            DocumentType::DRIVER_LICENSE => new LicenseProcessor($this->imageReader, $this->pdfConverter),
        };
    }

    public function autoDetect(string $filePath): DocumentProcessorInterface
    {
        $ocrText = $this->imageReader->readTextOnImage($filePath);
        $allText = implode("\n", $ocrText);

        if (preg_match('/PERMIS\s+DE\s+CONDUIRE/i', $allText)) {
            return $this->create(DocumentType::DRIVER_LICENSE);
        }
        if (preg_match('/PASSEPORT\s*(TOGOLAIS|TOGOLAISE)?/i', $allText) || str_contains($allText, "P<TGO")) {
            return $this->create(DocumentType::PASSPORT);
        }
        if (preg_match('/CARTE\s+NATIONALE\s+D\'IDENTITE/i', $allText) || str_contains($allText, 'I<TG') || str_contains($allText, 'I<TGO')) {
            return $this->create(DocumentType::CNI);
        }

        throw new \RuntimeException("Impossible de détecter le type de document depuis l'image fournie.");
    }
}
