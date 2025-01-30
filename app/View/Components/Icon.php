<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Facades\File;

class Icon extends Component
{
    public string $name;
    private static array $icons;
    private static string $icon_base_path = 'node_modules/@tabler/icons';

    /**
     * Create a new component instance.
     */
    public function __construct(string $name)
    {
        if (!isset(self::$icons)) {
            self::$icons = json_decode(File::get(base_path(self::$icon_base_path . '/tags.json')), true);
        }

        $this->name = $name;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        if (!isset(self::$icons[$this->name])) {
            return '';
        }

        $icon_path = base_path(self::$icon_base_path . "/icons/{$this->name}.svg");

        if (!File::exists($icon_path)) {
            return '';
        }

        return File::get($icon_path);
    }
}
