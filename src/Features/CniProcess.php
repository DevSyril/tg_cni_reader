<?php

namespace TgIdProcessor\Features;

use TgIdProcessor\Contracts\CniProcessInterface;
use TgIdProcessor\Contracts\ImageReaderInterface;
use TgIdProcessor\Contracts\ProcessorInterface;
use TgIdProcessor\Contracts\AnalyserInterface;
use TgIdProcessor\Contracts\PdfConverterInterface;
use TgIdProcessor\Models\Card;
use TgIdProcessor\Models\Front;
use TgIdProcessor\Models\Back;

class CniProcess implements CniProcessInterface
{
    private ImageReaderInterface $imageReader;
    private PdfConverterInterface $pdfConverter;
    private ProcessorInterface $processor;
    private AnalyserInterface $analyser;

    public function __construct(
        ImageReaderInterface $imageReader,
        PdfConverterInterface $pdfConverter,
        ProcessorInterface $processor,
        AnalyserInterface $analyser
    ) {
        $this->imageReader = $imageReader;
        $this->pdfConverter = $pdfConverter;
        $this->processor = $processor;
        $this->analyser = $analyser;
    }

    public function processCNI(string $frontPath, ?string $backPath = null): Card
    {
        if ($backPath === null) {
            $backPath = $frontPath;
        }

        $backText = $this->imageReader->readTextOnImage($backPath);
        $frontText = $this->imageReader->readTextOnImage($frontPath);

        $backResult = new Back();
        $frontResult = new Front();

        [$backResult, $check] = $this->processor->processBack($backResult, $backText);
        $frontResult = $this->processor->processFront($frontResult, $frontText);

        $cardInfo = $this->analyser->compare($frontResult, $backResult);
        $cardInfo->isInvalid = !$check;

        return $cardInfo;
    }

    public function processCNIPdf(string $filePath): Card
    {
        $imagePath = $this->pdfConverter->transformPdfToImage($filePath);
        return $this->processCNI($imagePath, $imagePath);
    }
}
