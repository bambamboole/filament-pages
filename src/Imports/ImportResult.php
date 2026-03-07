<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Imports;

enum ImportResult
{
    case Created;
    case Updated;
    case Unchanged;
}
