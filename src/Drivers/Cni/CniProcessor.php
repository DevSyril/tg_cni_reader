<?php

namespace TgDocumentProcessor\Drivers\Cni;

use TgDocumentProcessor\Contracts\DocumentProcessorInterface;
use TgDocumentProcessor\Contracts\ImageReaderInterface;
use TgDocumentProcessor\Contracts\PdfConverterInterface;
use TgDocumentProcessor\Drivers\Cni\Models\CniBack;
use TgDocumentProcessor\Drivers\Cni\Models\CniFront;
use TgDocumentProcessor\Drivers\Cni\Parsers\CniBackParser;
use TgDocumentProcessor\Drivers\Cni\Parsers\CniFrontParser;
use TgDocumentProcessor\Drivers\Cni\Validators\CniValidator;
use TgDocumentProcessor\Models\DocumentResult;
use TgDocumentProcessor\Models\DocumentType;

class CniProcessor implements DocumentProcessorInterface
{
    private ImageReaderInterface $imageReader;
    private PdfConverterInterface $pdfConverter;
    private CniFrontParser $frontParser;
    private CniBackParser $backParser;
    private CniValidator $validator;

    public function __construct(
        ImageReaderInterface $imageReader,
        PdfConverterInterface $pdfConverter,
        ?CniFrontParser $frontParser = null,
        ?CniBackParser $backParser = null,
        ?CniValidator $validator = null
    ) {
        $this->imageReader = $imageReader;
        $this->pdfConverter = $pdfConverter;
        $this->frontParser = $frontParser ?? new CniFrontParser();
        $this->backParser = $backParser ?? new CniBackParser();
        $this->validator = $validator ?? new CniValidator();
    }

    public function getType(): DocumentType
    {
        return DocumentType::CNI;
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

        throw new \InvalidArgumentException('CNI processor expects at least 1 file path.');
    }

    private function processFromImages(string $frontPath, string $backPath): DocumentResult
    {
        $backText = $this->imageReader->readTextOnImage($backPath);
        $frontText = $this->imageReader->readTextOnImage($frontPath);

        $backResult = new CniBack();
        $frontResult = new CniFront();

        [$backResult, $check] = $this->backParser->parse($backResult, $backText);
        $frontResult = $this->frontParser->parse($frontResult, $frontText);

        return $this->validator->validate($frontResult, $backResult, $check);
    }
}
