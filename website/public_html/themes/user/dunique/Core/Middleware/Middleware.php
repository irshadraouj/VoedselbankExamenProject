<?php

namespace Dunique\AntwanVanBoheemen\Core\Middleware;

use Dunique\AntwanVanBoheemen\Core\Contracts\MiddlewareInterface;
class Middleware
{
  protected $middleware = [];

  public function add(MiddlewareInterface $middleware)
  {
    $this->middleware[] = $middleware;
  }

  public function handle($request, callable $final)
  {
    $pipeline = array_reduce(
      array_reverse($this->middleware),
      fn($next, $middleware) => fn($request) => $middleware->handle($request, $next),
      $final
    );

    return $pipeline($request);
  }
}