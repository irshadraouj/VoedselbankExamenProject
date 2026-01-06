<?php

namespace Dunique\AntwanVanBoheemen\Core\Controllers;

use Dunique\AntwanVanBoheemen\Core\Http\Request;
class PostController {

  // index($request) (GET): Show a list of resources.
  // create($request) (GET): Show a form for creating a new resource.
  // store($request) (POST): Handle the creation of a new resource.
  // show($request, $id) (GET): Show a specific resource by its identifier.
  // edit($request, $id) (GET): Show a form for editing an existing resource.
  // update($request, $id) (PUT/PATCH): Update a specific resource.
  // destroy($request, $id) (DELETE): Delete a specific resource.

  public function store(Request $request) {
    return ee('dunique:Form')->setSuccessful($request->data()->form_id);;
  }
}