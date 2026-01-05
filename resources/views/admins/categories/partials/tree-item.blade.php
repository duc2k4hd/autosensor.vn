<li class="tree-item" data-category-id="{{ $item['id'] }}">
    <span class="tree-toggle">
        @if(!empty($item['children']))
            ▶
        @else
            &nbsp;
        @endif
    </span>
    <span style="flex:1;">{{ $item['name'] }}</span>
    <span class="badge" style="background:#e0e7ff;color:#4338ca;font-size:10px;">
        {{ $item['children_count'] ?? 0 }}
    </span>
</li>
@if(!empty($item['children']))
    <ul class="tree-children">
        @foreach($item['children'] as $child)
            @include('admins.categories.partials.tree-item', ['item' => $child, 'level' => $level + 1])
        @endforeach
    </ul>
@endif

