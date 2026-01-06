<?php

namespace Dunique\AntwanVanBoheemen\Core\Contracts;

use Dunique\AntwanVanBoheemen\Core\Http\Request;
use Dunique\AntwanVanBoheemen\Core\Http\Response;

interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response;
    
}

// EOF