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
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $page->slug_path }}</span>
            </div>
        </div>
        <div class="flex gap-1 items-center">
            <x-filament::badge :color="$page->isPublished() ? 'success' : 'gray'" size="sm">
                {{ $page->isPublished() ? 'Published' : 'Draft' }}
            </x-filament::badge>
            <button
                type="button"
                class="text-gray-400 hover:text-primary-500 p-1"
                x-on:click="$wire.mountAction('editPage', { pageId: {{ $page->id }} })"
            >
                <x-filament::icon icon="heroicon-m-pencil-square" class="h-4 w-4" />
            </button>
            <button
                type="button"
                class="text-gray-400 hover:text-danger-500 p-1"
                x-on:click="$wire.mountAction('deletePage', { pageId: {{ $page->id }} })"
            >
                <x-filament::icon icon="heroicon-m-trash" class="h-4 w-4" />
            </button>
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
