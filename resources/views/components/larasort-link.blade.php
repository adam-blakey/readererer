@props(['name', 'display_name' => null])

<a class="table-sort {{ (isset($_GET['orderby']) and $_GET['orderby'] == $name) ? $_GET['order'] : '' }}" href="{{ \SDamian\Larasort\LarasortLink::getUrl($name) }}">{{ $display_name == null ? ucfirst($name) : $display_name }}</a>
