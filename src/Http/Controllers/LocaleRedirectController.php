<?php

declare(strict_types=1);
namespace Bambamboole\FilamentPages\Http\Controllers;

use Bambamboole\FilamentPages\Facades\FilamentPages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleRedirectController
{
    public function __invoke(Request $request, ?string $path = null): RedirectResponse
    {
        $localeKeys = array_keys(FilamentPages::locales());
        $preferred = $request->getPreferredLanguage($localeKeys) ?? FilamentPages::defaultLocale();
        $target = rtrim('/'.$preferred.'/'.ltrim($path ?? '', '/'), '/');

        return redirect()->to($target);
    }
}
