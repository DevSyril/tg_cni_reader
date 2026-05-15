<?php

namespace TgDocumentProcessor\Tools;

use TgDocumentProcessor\Contracts\PdfConverterInterface;
use Spatie\PdfToImage\Pdf;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

class PdfConverter implements PdfConverterInterface
{
    private string $outputDir;

    private ?ImageManager $imageManager = null;

    private int $resolution;

    public function __construct(string $outputDir = '', int $resolution = 200)
    {
        $this->outputDir = $outputDir ?: __DIR__ . '/../../data';
        $this->resolution = $resolution;
    }

    private function getImageManager(): ImageManager
    {
        if ($this->imageManager === null) {
            $this->imageManager = new ImageManager(new GdDriver());
        }
        return $this->imageManager;
    }

    public function transformPdfToImage(string $pdfPath): string
    {
        $imageName = uniqid('pdf_', true) . '.png';
        $imagePath = $this->outputDir . '/' . $imageName;

        $pdf = new Pdf($pdfPath);
        $pdf->setResolution($this->resolution);
        $pdf->setOutputFormat('png');

        if ($pdf->getNumberOfPages() > 1) {
            throw new \RuntimeException('Le fichier PDF doit contenir une seule page avec les deux faces de la carte');
        }

        $pdf->saveImage($imagePath);

        $image = $this->getImageManager()->make($imagePath);
        $bounds = $this->findNonWhiteBounds($image);
        if ($bounds !== null) {
            $image->crop($bounds['width'], $bounds['height'], $bounds['x'], $bounds['y']);
        }
        $image->resize($image->width() + 700, $image->height() + 700);
        $image->save($imagePath);
        $image->destroy();

        return realpath($imagePath) ?: $imagePath;
    }

    private function findNonWhiteBounds($image): ?array
    {
        $width = $image->width();
        $height = $image->height();
        $whiteThreshold = 240;

        $minX = $width;
        $maxX = 0;
        $minY = $height;
        $maxY = 0;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $pixel = $image->pickColor($x, $y);
                $r = $pixel[0];
                $g = $pixel[1];
                $b = $pixel[2];
                if ($r < $whiteThreshold || $g < $whiteThreshold || $b < $whiteThreshold) {
                    if ($x < $minX) {
                        $minX = $x;
                    }
                    if ($x > $maxX) {
                        $maxX = $x;
                    }
                    if ($y < $minY) {
                        $minY = $y;
                    }
                    if ($y > $maxY) {
                        $maxY = $y;
                    }
                }
            }
        }

        if ($minX <= $maxX && $minY <= $maxY) {
            return [
                'x' => $minX,
                'y' => $minY,
                'width' => $maxX - $minX + 1,
                'height' => $maxY - $minY + 1,
            ];
        }

        return null;
    }
}
