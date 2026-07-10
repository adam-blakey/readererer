<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Facades\File;

class Icon extends Component
{
    public string $name;
    private static string $icon_base_path = 'build/icons';

    /**
     * Create a new component instance.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $icon_path = public_path(self::$icon_base_path . "/{$this->name}.svg");

        if (!File::exists($icon_path)) {
            return '';
        }

        return File::get($icon_path);
    }
}