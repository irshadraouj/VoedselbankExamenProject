<?php

namespace Dunique\AntwanVanBoheemen\Core\Middleware;

use Dunique\AntwanVanBoheemen\Core\Http\Request;
use Dunique\AntwanVanBoheemen\Core\Http\Response;

use Dunique\AntwanVanBoheemen\Core\Contracts\MiddlewareInterface;

class ApiValidationMiddleware implements MiddlewareInterface
{

  public function handle(Request $request, callable $next): Response
  {
      if ($request->isAjax()) {
        return ee('dunique:Response')->notFound();
      }
      return $next($request);
  }
}