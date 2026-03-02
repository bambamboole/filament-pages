<?php

declare(strict_types=1);

namespace Bambamboole\FilamentPages\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Provides filament-menu Linkable compatibility without requiring the package.
 * When bambamboole/filament-menu is installed, the Page model can implement
 * the Linkable interface by extending this class and adding `implements Linkable`.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasLinkable
{
    /** @return Builder<static> */
    public static function getLinkableQuery(): Builder
    {
        return static::query();
    }

    public static function getNameColumn(): string
    {
        return 'title';
    }

    public function getLink(): string
    {
        return url($this->slug_path);
    }
}
