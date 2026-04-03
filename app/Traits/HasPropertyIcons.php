<?php

namespace App\Traits;

use App\Attributes\Icon;
use ReflectionMethod;
use ReflectionProperty;

trait HasPropertyIcons
{
    public function getIconForAttribute(string $attributeName): ?string
    {
        if (property_exists($this, $attributeName)) {
            return $this->getIconForProperty($attributeName);
        }

        if (method_exists($this, $attributeName)) {
            return $this->getIconForMethod($attributeName);
        }

        return null;
    }

    private function getIconForProperty(string $propertyName): ?string
    {
        try {
            $reflection = new ReflectionProperty($this, $propertyName);
        } catch (\ReflectionException $e) {
            return null;
        }

        return $this->getIconName($reflection?->getAttributes(Icon::class));
    }

    private function getIconForMethod(string $methodName): ?string
    {
        try {
            $reflection = new ReflectionMethod($this, $methodName);
        } catch (\ReflectionException $e) {
            return null;
        }

        return $this->getIconName($reflection?->getAttributes(Icon::class));
    }

    private function getIconName(array $attributes): ?string
    {
        if (empty($attributes)) {
            return null;
        }

        $iconAttribute = $attributes[0]->newInstance();

        return $iconAttribute->name;
    }
}
