<li>
    <a href="/{{ $category->slug }}">{{ $category->name }}</a>
    @if ($category->children->isNotEmpty())
        <ul class="xanhworld_header_main_nav_category_lists_items_item_list_sub">
            @foreach ($category->children as $child)
                @include('clients.templates.partials.category-tree-item', ['category' => $child])
            @endforeach
        </ul>
    @endif
</li>

