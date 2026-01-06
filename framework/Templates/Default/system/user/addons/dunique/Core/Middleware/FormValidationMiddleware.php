<?php

namespace Dunique\AntwanVanBoheemen\Core\Middleware;

use Dunique\AntwanVanBoheemen\Core\Http\Request;
use Dunique\AntwanVanBoheemen\Core\Http\Response;

use Dunique\AntwanVanBoheemen\Core\Validator\FormValidator;
use Dunique\AntwanVanBoheemen\Core\Validator\RequestValidator;
use Dunique\AntwanVanBoheemen\Core\Contracts\MiddlewareInterface;

class FormValidationMiddleware implements MiddlewareInterface
{

  public function handle(Request $request, callable $next): Response
  {
      $id = ee('dunique:Form')->id($request);
      
      // $errors = RequestValidator::validate($request);
      // ee('dunique:Form')->handleErrors($request, $id, $errors);
      
      $errors = FormValidator::validate($request);
      
      ee('dunique:Form')->handleErrors($request, $id, $errors);
 
      $request->set('form_id', $id);
      
      // Pass the request to the next middleware or handler
      return $next($request);
  }
}