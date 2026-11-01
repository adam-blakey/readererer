<?php

namespace App\Translation;

use Illuminate\Contracts\Translation\Loader;

// Renames words in the JSON catalogue before placeholders are filled, so names such as "Concert Band" keep their wording.
class TerminologyLoader implements Loader
{
    public function __construct(private Loader $loader) {}

    public function load($locale, $group, $namespace = null)
    {
        $lines = $this->loader->load($locale, $group, $namespace);

        if ($group !== '*' || $namespace !== '*') {
            return $lines;
        }

        $terminology = config('app.readererer_terminology', []);

        return array_map(fn ($line) => is_string($line) ? apply_terminology($line, $terminology) : $line, $lines);
    }

    public function addNamespace($namespace, $hint)
    {
        $this->loader->addNamespace($namespace, $hint);
    }

    public function addJsonPath($path)
    {
        $this->loader->addJsonPath($path);
    }

    public function namespaces()
    {
        return $this->loader->namespaces();
    }
}
