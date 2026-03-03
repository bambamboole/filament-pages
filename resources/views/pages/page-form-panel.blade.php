<div class="fp-form-wrapper">
    <div class="fp-form-header">
        <div class="fp-form-header-left">
            <h2 class="fp-form-title">
                {{ $this->formMode === 'create' ? 'New Page' : 'Edit Page' }}
            </h2>
        </div>
        <div class="fp-form-header-actions">
            @if($this->formMode === 'edit' && $this->activePageId)
                @php
                    $activeRecord = $this->getPageModel()::find($this->activePageId);
                @endphp
                @if(Bambamboole\FilamentPages\FilamentPagesPlugin::get()->isPreviewEnabled())
                    <x-filament::button
                        size="sm"
                        color="gray"
                        wire:click="previewPage"
                    >
                        {{ __('filament-peek::ui.preview-action-label') }}
                    </x-filament::button>
                @endif
                @if($activeRecord?->isPublished())
                    <x-filament::button
                        tag="a"
                        size="sm"
                        color="gray"
                        icon="heroicon-m-arrow-top-right-on-square"
                        href="{{ $activeRecord->frontendUrl() }}"
                        target="_blank"
                    >
                        Visit Page
                    </x-filament::button>
                @endif
            @elseif($this->formMode === 'create' && Bambamboole\FilamentPages\FilamentPagesPlugin::get()->isPreviewEnabled())
                <x-filament::button
                    size="sm"
                    color="gray"
                    wire:click="previewPage"
                >
                    {{ __('filament-peek::ui.preview-action-label') }}
                </x-filament::button>
            @endif
        </div>
    </div>

    <form wire:submit="savePage">
        {{ $this->pageForm }}

        <div class="fp-form-footer">
            <x-filament::button type="submit" wire:loading.attr="disabled">
                {{ $this->formMode === 'create' ? 'Create Page' : 'Save Changes' }}
            </x-filament::button>
            <x-filament::button
                color="gray"
                wire:click="deselectPage"
                type="button"
            >
                Cancel
            </x-filament::button>
        </div>
    </form>
</div>
