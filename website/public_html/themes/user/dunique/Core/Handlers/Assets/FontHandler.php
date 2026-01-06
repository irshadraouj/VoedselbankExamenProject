<?php

namespace Dunique\AntwanVanBoheemen\Core\Handlers\Assets;

use Dunique\AntwanVanBoheemen\Core\Contracts\AssetHandlerInterface;

class FontHandler implements AssetHandlerInterface
{
    private string $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }
    public function generate(string $path): string
    {
        return $this->type == 'eot'
            ? "<link rel=\"preload\" href=\"/{$path}\" as=\"font\" crossorigin=\"anonymous\" />"
            : "<link rel=\"preload\" href=\"/{$path}\" as=\"font\" type=\"font/{$this->type}\" crossorigin=\"anonymous\" />";
    }
}
