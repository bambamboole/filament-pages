<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Blocks;

use Illuminate\Support\Facades\Vite;

class BlockAssetBag
{
    /** @var array<string, true> */
    private array $registeredTypes = [];

    /** @var array<string, BlockAsset> */
    private array $cssAssets = [];

    /** @var array<string, BlockAsset> */
    private array $jsAssets = [];

    public function registerBlock(string $type, PageBlock $block): void
    {
        if (isset($this->registeredTypes[$type])) {
            return;
        }

        $this->registeredTypes[$type] = true;

        $assets = $block->assets();

        foreach ($assets['css'] ?? [] as $asset) {
            $this->cssAssets[$asset->content] = $asset;
        }

        foreach ($assets['js'] ?? [] as $asset) {
            $this->jsAssets[$asset->content] = $asset;
        }
    }

    public function renderStyles(): string
    {
        $nonce = $this->nonceAttribute();
        $html = '';

        foreach ($this->cssAssets as $asset) {
            if ($asset->inline) {
                $html .= '<style'.$nonce.'>'.$asset->content.'</style>';
            } else {
                $html .= '<link rel="stylesheet" href="'.e($asset->content).'">';
            }
        }

        return $html;
    }

    public function renderScripts(): string
    {
        $nonce = $this->nonceAttribute();
        $html = '';

        foreach ($this->jsAssets as $asset) {
            if ($asset->inline) {
                $html .= '<script'.$nonce.'>'.$asset->content.'</script>';
            } else {
                $html .= '<script src="'.e($asset->content).'"></script>';
            }
        }

        return $html;
    }

    private function nonceAttribute(): string
    {
        $nonce = Vite::cspNonce();

        return $nonce ? ' nonce="'.$nonce.'"' : '';
    }
}
