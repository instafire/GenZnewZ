<ul {!! $options !!}>
    @foreach ($menu_nodes as $key => $row)
        <li @class(['menu-item-has-children' => $row->has_child, $row->css_class, 'current' => $row->active])>
            <a href="{{ url($row->url) }}" @if ($row->target !== '_self') target="{{ $row->target }}" @endif>
                {!! $row->icon_html !!} <span>{{ $row->title }}</span>
            </a>
            @if ($row->has_child)
                {!! Menu::generateMenu([
                    'menu' => $menu,
                    'menu_nodes' => $row->child,
                    'view' => 'menu',
                ]) !!}
            @endif
        </li>
    @endforeach
</ul>
