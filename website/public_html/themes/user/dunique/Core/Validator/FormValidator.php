<?php

namespace  Dunique\AntwanVanBoheemen\Core\Validator;

use Dunique\AntwanVanBoheemen\Core\Http\Request;

class FormValidator
{
    /**
     * Detects malicious activity based on the request's headers and URI.
     * Checks for suspicious patterns in User-Agent, Referrer, Accept-Language, and query strings.
     *
     * @param Request $request The incoming request to analyze.
     * @return array An array of errors or an empty array if no issues are found.
     */
    public static function validate(Request $request):array
    {
      $errors = [];
      
      $post = ee('dunique:Form')->sanitize($request->data());
      $data = $post['_data'];
      $rules = (array) ($data['rules'] ?? []);
      $validation = ee('Validation')->make($rules)->validate($post);

      if ($validation->isNotValid()) {
          foreach ($validation->getAllErrors() as $field => $error) {
              $errors[$field] = reset($error); // Use the first error message
          }
      }

      return $errors;
    }

    
}
