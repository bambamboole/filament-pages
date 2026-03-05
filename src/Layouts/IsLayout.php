<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Layouts;

use Attribute;
use Illuminate\Support\Str;

#[Attribute(Attribute::TARGET_CLASS)]
class IsLayout
{
    public function __construct(
        public string $key,
        public ?string $label = null,
        public bool $translateLabel = false,
    ) {}

    public function resolvedLabel(): string
    {
        if (!$this->label) {
            return Str::headline($this->key);
        }

        return $this->translateLabel ? __($this->label) : $this->label;
    }
}
