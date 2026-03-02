<div class="page-item" data-id="{{ $page->id }}" wire:key="page-item-{{ $page->id }}">
    <div class="flex justify-between items-center rounded-lg bg-white border border-gray-200 shadow-sm pr-2 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center">
            <div class="border-r border-gray-200 dark:border-gray-700 cursor-grab">
                <x-filament::icon
                    icon="heroicon-m-bars-2"
                    class="w-5 h-5 m-2 handle text-gray-400"
                />
            </div>
            <div class="ml-2 flex items-center gap-2">
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $page->title }}</span>
                @if($page->isPublished() && $page->frontendUrl())
                    <a
                        href="{{ $page->frontendUrl() }}"
                        target="_blank"
                        class="text-xs text-gray-500 hover:text-primary-500 hover:underline dark:text-gray-400 dark:hover:text-primary-400"
                    >{{ $page->slug_path }}</a>
                @else
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $page->slug_path }}</span>
                @endif
            </div>
        </div>
        <div class="flex gap-1 items-center">
            @if($page->isPublished())
                <x-filament::badge color="success" size="sm">
                    Published
                </x-filament::badge>
            @elseif($page->isScheduled())
                <x-filament::badge
                    tag="button"
                    color="warning"
                    size="sm"
                    x-on:click="$wire.mountAction('updatePublishedAt', { pageId: {{ $page->id }} })"
                >
                    Scheduled
                </x-filament::badge>
            @else
                <x-filament::badge
                    tag="button"
                    color="gray"
                    size="sm"
                    x-on:click="$wire.mountAction('updatePublishedAt', { pageId: {{ $page->id }} })"
                >
                    Draft
                </x-filament::badge>
            @endif
            <button
                type="button"
                class="text-gray-400 hover:text-primary-500 p-1"
                x-on:click="$wire.mountAction('editPage', { pageId: {{ $page->id }} })"
            >
                <x-filament::icon icon="heroicon-m-pencil-square" class="h-4 w-4" />
            </button>
            @if($page->children->isEmpty())
                <button
                    type="button"
                    class="text-gray-400 hover:text-danger-500 p-1"
                    x-on:click="$wire.mountAction('deletePage', { pageId: {{ $page->id }} })"
                >
                    <x-filament::icon icon="heroicon-m-trash" class="h-4 w-4" />
                </button>
            @else
                <x-filament::icon-button
                    icon="heroicon-m-trash"
                    color="gray"
                    size="sm"
                    disabled
                    tooltip="Cannot delete — page has children"
                />
            @endif
            @foreach($this->getExtraTreeItemActions() as $extraAction)
                <button
                    type="button"
                    class="text-gray-400 hover:text-primary-500 p-1"
                    x-on:click="$wire.mountAction('{{ $extraAction->getName() }}', { pageId: {{ $page->id }} })"
                >
                    <x-filament::icon :icon="$extraAction->getIcon() ?? 'heroicon-m-ellipsis-horizontal'" class="h-4 w-4" />
                </button>
            @endforeach
        </div>
    </div>

    <div
        class="nested mt-2 space-y-2" style="margin-left: 1.5rem"
        data-id="{{ $page->id }}"
        x-data="{
            init() {
                new Sortable(this.$el, {
                    handle: '.handle',
                    group: 'nested',
                    animation: 150,
                    fallbackOnBody: true,
                    swapThreshold: 0.65,
                    onEnd: (evt) => {
                        const data = this.getDataStructure(document.getElementById('pageTreeRoot'));
                        $wire.call('reorderTree', data);
                    }
                })
            },
        }"
    >
        @foreach($page->children as $child)
            @include('filament-pages::pages.page-tree-item', ['page' => $child])
        @endforeach
    </div>
</div>
