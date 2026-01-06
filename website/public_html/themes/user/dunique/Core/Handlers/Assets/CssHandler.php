<?php

namespace Dunique\AntwanVanBoheemen\Core\Handlers\Assets;

use Dunique\AntwanVanBoheemen\Core\Contracts\AssetHandlerInterface;

class CssHandler implements AssetHandlerInterface
{
    private bool $isDev;
    private string $base;
    private string $src;

    private $port;

    public function __construct(string $base, bool $isDev = false, $src = '', $port = null)
    {
        $this->base = $base;
        $this->isDev = $isDev;
        $this->src = $src;
        $this->port = $port;
    }
    public function generate(string $path): string
    {
        return $this->isDev
            ? "<link rel=\"stylesheet\" href=\"{$this->base}:{$this->port}/{$this->src}\" />"
            : "<link rel=\"stylesheet\" href=\"/{$path}\" />";
    }
}
