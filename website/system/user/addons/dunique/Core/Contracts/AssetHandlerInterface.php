<?php

namespace Dunique\AntwanVanBoheemen\Core\Contracts;

interface AssetHandlerInterface
{
    public function generate(string $path): string;
}

// EOF