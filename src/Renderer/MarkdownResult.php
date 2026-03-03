<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Renderer;

final readonly class MarkdownResult
{
    /**
     * @param  array<string, mixed>  $frontMatter
     */
    public function __construct(
        public string $html,
        public string $tocHtml = '',
        public array $frontMatter = [],
    ) {}
}
