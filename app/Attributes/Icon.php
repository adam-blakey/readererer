<?php

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Icon
{
    /**
     * At class level, `$for` names the Eloquent attribute the icon is for, so
     * columns need no real property, which would shadow the attribute.
     */
    public function __construct(public string $name, public ?string $for = null) {}
}
