<?php

namespace TgIdProcessor\Processors;

use TgIdProcessor\Contracts\ProcessorInterface;
use TgIdProcessor\Contracts\FrontProcessorInterface;
use TgIdProcessor\Contracts\BackProcessorInterface;
use TgIdProcessor\Models\Front;
use TgIdProcessor\Models\Back;

class Processor implements ProcessorInterface
{
    private FrontProcessorInterface $frontProcessor;
    private BackProcessorInterface $backProcessor;

    public function __construct(FrontProcessorInterface $frontProcessor, BackProcessorInterface $backProcessor)
    {
        $this->frontProcessor = $frontProcessor;
        $this->backProcessor = $backProcessor;
    }

    public function processBack(Back $back, array $text): array
    {
        return $this->backProcessor->processBack($back, $text);
    }

    public function processFront(Front $front, array $text): Front
    {
        return $this->frontProcessor->processFront($front, $text);
    }
}
