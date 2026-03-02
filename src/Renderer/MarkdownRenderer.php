<?php

declare(strict_types=1);

namespace Bambamboole\FilamentPages\Renderer;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\MarkdownConverter;
use RyanChandler\CommonmarkBladeBlock\BladeExtension;
use Torchlight\Commonmark\V2\TorchlightExtension;

class MarkdownRenderer
{
    public function convert(string $markdown, bool $withToc = false): MarkdownResult
    {
        $config = $this->buildConfig($withToc);

        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new HeadingPermalinkExtension);
        $environment->addExtension(new FrontMatterExtension);

        if ($withToc) {
            $environment->addExtension(new TableOfContentsExtension);
        }

        if (config('filament-pages.markdown.blade_blocks', true)) {
            $environment->addExtension(new BladeExtension);
        }

        if (config('filament-pages.markdown.torchlight', false)) {
            $environment->addExtension(new TorchlightExtension);
        }

        $converter = new MarkdownConverter($environment);
        $result = $converter->convert($markdown);

        $frontMatter = $result instanceof RenderedContentWithFrontMatter
            ? ($result->getFrontMatter() ?? [])
            : [];

        $html = $result->getContent();
        $tocHtml = '';

        if ($withToc) {
            $tocClass = config('filament-pages.markdown.table_of_contents.html_class', 'table-of-contents');
            $tocHtml = $this->extractToc($html, $tocClass);
        }

        return new MarkdownResult(
            html: $html,
            tocHtml: $tocHtml,
            frontMatter: $frontMatter,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildConfig(bool $withToc): array
    {
        $permalinkConfig = config('filament-pages.markdown.heading_permalink', []);
        $tocConfig = config('filament-pages.markdown.table_of_contents', []);

        $config = [
            'heading_permalink' => [
                'html_class' => $permalinkConfig['html_class'] ?? 'heading-permalink',
                'symbol' => $permalinkConfig['symbol'] ?? '#',
                'insert' => $permalinkConfig['insert'] ?? 'after',
            ],
        ];

        if ($withToc) {
            $config['table_of_contents'] = [
                'html_class' => $tocConfig['html_class'] ?? 'table-of-contents',
                'position' => 'top',
                'style' => $tocConfig['style'] ?? 'bullet',
                'normalize' => $tocConfig['normalize'] ?? 'relative',
                'min_heading_level' => $tocConfig['min_heading_level'] ?? 2,
                'max_heading_level' => $tocConfig['max_heading_level'] ?? 6,
            ];
        }

        return $config;
    }

    private function extractToc(string &$html, string $tocClass): string
    {
        $openTag = '<ul class="' . $tocClass . '">';
        $startPos = strpos($html, $openTag);

        if ($startPos === false) {
            return '';
        }

        // Walk through the HTML tracking nested <ul> depth to find the matching </ul>
        $depth = 0;
        $pos = $startPos;
        $len = strlen($html);

        while ($pos < $len) {
            $nextOpen = strpos($html, '<ul', $pos + 1);
            $nextClose = strpos($html, '</ul>', $pos + 1);

            if ($nextClose === false) {
                break;
            }

            if ($nextOpen !== false && $nextOpen < $nextClose) {
                $depth++;
                $pos = $nextOpen;
            } else {
                if ($depth === 0) {
                    $endPos = $nextClose + strlen('</ul>');
                    $tocHtml = substr($html, $startPos, $endPos - $startPos);
                    $html = substr($html, 0, $startPos) . substr($html, $endPos);

                    return $tocHtml;
                }
                $depth--;
                $pos = $nextClose;
            }
        }

        return '';
    }
}
