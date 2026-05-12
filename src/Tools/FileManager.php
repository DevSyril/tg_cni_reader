<?php

namespace TgIdProcessor\Tools;

use Ramsey\Uuid\Uuid;
use Intervention\Image\ImageManager;

class FileManager
{
    private string $dataDir;

    private ImageManager $imageManager;

    public function __construct(string $dataDir = '')
    {
        $this->dataDir = $dataDir ?: __DIR__ . '/../../data';
        $this->imageManager = new ImageManager(['driver' => 'gd']);
    }

    public function saveFile(string $sourcePath, ?string $fileName = null): string
    {
        $fileName = $fileName ?? Uuid::uuid4()->toString() . '.' . pathinfo($sourcePath, PATHINFO_EXTENSION);
        $destPath = $this->dataDir . '/' . $fileName;

        copy($sourcePath, $destPath);

        $image = $this->imageManager->make($destPath);
        $image->resize($image->width() + 300, $image->height() + 300);
        $image->save($destPath);
        $image->destroy();

        return $destPath;
    }

    public function deleteFile(string $path): void
    {
        try {
            if (file_exists($path)) {
                unlink($path);
            }
        } catch (\Throwable $e) {
            error_log("Erreur lors de la suppression du fichier : {$e->getMessage()}");
        }
    }
}
