<div class="fp-split-layout" x-data="{ treeCollapsed: false }">
    <div class="fp-tree-panel" x-bind:class="{ 'fp-tree-panel-collapsed': treeCollapsed }">
        <div class="fp-tree-panel-header">
            <button type="button" x-on:click="treeCollapsed = !treeCollapsed" class="fp-tree-toggle">
                <x-filament::icon icon="heroicon-m-chevron-left" class="h-4 w-4 transition-transform duration-200" x-bind:class="{ 'rotate-180': treeCollapsed }" />
            </button>
        </div>
        <div x-show="!treeCollapsed">
            @include('filament-pages::pages.tree')
        </div>
    </div>

    <div class="fp-form-panel">
        @if($this->formMode)
            @include('filament-pages::pages.form-panel')
        @else
            @include('filament-pages::pages.empty-state')
        @endif
    </div>
</div>
