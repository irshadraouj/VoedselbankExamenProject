<?php

namespace Dunique\AntwanVanBoheemen\Actions;

use Dunique\AntwanVanBoheemen\Core\Traits\HasMiddleware;
use ExpressionEngine\Service\Addon\Controllers\Action\AbstractRoute;
use Dunique\AntwanVanBoheemen\Core\Middleware\FormValidationMiddleware;
use Dunique\AntwanVanBoheemen\Core\Middleware\ApiValidationMiddleware;
use Dunique\AntwanVanBoheemen\Core\Controllers\PostController;

class Post extends AbstractRoute
{
  use HasMiddleware;
  protected $middleware = [
    ApiValidationMiddleware::class,
    FormValidationMiddleware::class,
  ];
  
  public function process() {
    // Process middleware and handle request
    return $this->handle(ee('dunique:Request'), function ($request) 
      {
        $controller = new PostController();

        if ($request->isPost()) {
          $controller->store($request);
        }
        
        return ee()->functions->redirect($request->input('return_url') ?? ee()->functions->form_backtrack('-1'));
      }
    );
    
  } 
}
