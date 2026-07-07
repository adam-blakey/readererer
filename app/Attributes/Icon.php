<?php

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Icon
{
    /**
     * On a property or method the icon applies to that property/method and
     * `$for` is not needed. At class level `$for` names the attribute the icon
     * applies to; this lets Eloquent attributes (database columns, timestamps)
     * carry icons without declaring real properties for them, which would
     * shadow the model's magic attribute handling.
     */
    public function __construct(public string $name, public ?string $for = null) {
    }
}
