<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Blocks;

use Attribute;
use Illuminate\Support\Str;

#[Attribute(Attribute::TARGET_CLASS)]
class IsBlock
{
    public function __construct(
        public string $type,
        public ?string $label = null,
        public bool $translateLabel = false,
    ) {}

    public function resolvedLabel(): string
    {
        if (!$this->label) {
            return Str::headline($this->type);
        }

        return $this->translateLabel ? __($this->label) : $this->label;
    }
}
