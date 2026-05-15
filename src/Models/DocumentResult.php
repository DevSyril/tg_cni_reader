<?php

namespace TgDocumentProcessor\Models;

class DocumentResult
{
    public DocumentType $type;

    /** @var array<string, ValueStat> */
    public array $fields = [];

    public ?bool $isExpired = null;

    public ?bool $isValid = null;

    /** @var string[] */
    public array $errors = [];

    public function __construct(DocumentType $type)
    {
        $this->type = $type;
    }

    public function addField(string $name, ?string $value = null, ?bool $stat = null): self
    {
        $this->fields[$name] = new ValueStat($value, $stat);
        return $this;
    }

    public function get(string $name): ?ValueStat
    {
        return $this->fields[$name] ?? null;
    }

    public function getValue(string $name): ?string
    {
        return $this->fields[$name]->value ?? null;
    }
}
