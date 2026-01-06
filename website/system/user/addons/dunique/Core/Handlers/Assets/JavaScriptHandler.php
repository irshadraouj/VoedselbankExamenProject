<?php

namespace Dunique\AntwanVanBoheemen\Core\Handlers\Assets;

use Dunique\AntwanVanBoheemen\Core\Contracts\AssetHandlerInterface;

class JavaScriptHandler implements AssetHandlerInterface
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
            ? "<script type=\"module\" src=\"{$this->base}:{$this->port}/{$this->src}\" defer></script>"
            : "<script type=\"module\" src=\"/{$path}\" defer></script>";
    }
}
