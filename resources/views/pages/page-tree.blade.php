<div
    x-data="{
        getDataStructure(parentNode) {
            const items = Array.from(parentNode.children).filter((item) => {
                return item.classList.contains('page-item');
            });

            return items.map((item) => {
                const id = parseInt(item.getAttribute('data-id'));
                const nestedContainer = item.querySelector(':scope > .nested');
                const children = nestedContainer ? this.getDataStructure(nestedContainer) : [];

                return { id, children };
            });
        }
    }"
>
    @if($this->getTreeItems()->count() > 0)
        <div
            id="pageTreeRoot"
            class="nested space-y-2"
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
            @foreach($this->getTreeItems() as $page)
                @include('filament-pages::pages.page-tree-item', ['page' => $page])
            @endforeach
        </div>
    @else
        <div class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
            No pages yet. Create your first page using the button above.
        </div>
    @endif
</div>
