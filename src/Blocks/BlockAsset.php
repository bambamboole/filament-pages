<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Blocks;

final class BlockAsset
{
    private function __construct(
        public readonly string $content,
        public readonly bool $inline,
    ) {}

    public static function url(string $url): self
    {
        return new self($url, inline: false);
    }

    public static function inline(string $content): self
    {
        return new self($content, inline: true);
    }
}
