<div class="fp-split-layout">
    <div class="fp-tree-panel">
        @include('filament-pages::pages.page-tree')
    </div>

    <div class="fp-form-panel">
        @if($this->formMode)
            @include('filament-pages::pages.page-form-panel')
        @else
            @include('filament-pages::pages.page-empty-state')
        @endif
    </div>
</div>
