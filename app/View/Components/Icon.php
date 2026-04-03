<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;

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
        if (config('app.env') === 'production') {
            $icon_path = public_path(self::$icon_base_path . "/outline/{$this->name}.svg");
        }
        else {
            $icon_path = public_path(self::$icon_base_path . "/{$this->name}.svg");
        }

        if (!File::exists($icon_path)) {
            return '';
        }

        return File::get($icon_path);
    }
}