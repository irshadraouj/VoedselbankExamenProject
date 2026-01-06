<?php

namespace Dunique\AntwanVanBoheemen\Service;

use Dunique\AntwanVanBoheemen\Core\Http\Request;

/**
 * Add-on Service
 */
class FormService
{
  private $addon_name = 'dunique';

  public function make($tagdata, $params, $action): array
  {
    $form = [];
    // Get Form ID
    $formID = $this->generateId();
    $form['form_id'] = $formID;

    $form['is_send'] = ee()->session->flashdata($this->addon_name . '_validation_success_' . $formID);
    $form['errors'] = ee()->session->flashdata($this->addon_name . '_validation_errors_' . $formID);
    $form['old_values'] = ee()->session->flashdata($this->addon_name . '_old_values_' . $formID);

    $actionUrl = ee($this->addon_name . ':Helper')->action($action);
    $form_declaration = $params;
    $form_declaration['action'] = $actionUrl ? $actionUrl : $action;
    $form_declaration['secure'] = TRUE;
    $form_declaration['id'] = $formID;
    $form_declaration['secure:id'] = $form_declaration['id'];
    $form_declaration['hidden_fields'] = $this->prepareHiddenFields($form_declaration);
    $form['form_declaration'] = $form_declaration;

    $form_open = ee()->functions->form_declaration($form['form_declaration']);
    $honeypot = $this->honeypot();
    $form_close = form_close();
    $form['tagdata'] = $form_open . $honeypot . $tagdata . $form_close;

    return $form;
  }
  public function sanitize($post): array
  {
    if (is_object($post)) {
      $post = (array) $post;
    }
    try {
      // Sanitize input
      foreach ($post as $key => $value) {
        if (!empty($value) && is_array($value)) {
          // Handle nested arrays recursively by converting to an object
          $post[$key] = $this->sanitize($value);
        } else {
          // $value = ee()->input->post($key);
          try {
            // Attempt to decode encrypted values
            if (@ee('Encrypt')->decode($value)) {
              $value = @ee('Encrypt')->decode($value);
            }
          } catch (\Throwable $e) {
            // Handle exception if needed
          }

          if (ee('dunique:Helper')->isSerialized($value)) {
            $value = unserialize($value);
          }

          if (is_string($value)) {
            $value = ee('Security/XSS')->clean($value);
            // Check if the cleaned value contains '[removed]'
            if (strpos($value, '[removed]') !== false) {
                // empty the value
                $value = '';
            }
          }

          // Assign the sanitized value back to the object
          $post[$key] = $value;

          // Remove values that start with '{' and end with '}'
          if (is_string($value) && strpos($value, '{') === 0 && strrpos($value, '}') === strlen($value) - 1) {
            unset($post->$key);
          }
        }
      }

      // Check if the 'data' property exists and is an array
      if (isset($post['_data']) && is_array($post['_data'])) {
        // Loop through 'data' and assign its key-value pairs to the top level
        foreach ($post['_data'] as $key => $value) {
          if ($key == 'rules') {
            $value = unserialize(base64_decode($value));
          }
          $post['_data'][$key] = $value;
        }
      }
    } catch (\Exception $e) {
      ee()->logger->developer('Error sanitizing POST data: ' . $e->getMessage());
    }
    return $post;
  }

  public function id(Request $request): string
  {
    $post = $this->sanitize($request->data());
    
    if (empty($post['_data'])) return false;
    $data = $post['_data'];
    $id = $data['id'];
    return $id;
  }
  public function handleErrors(Request $request, string $id, array $errors)
  {
    $sanitized = $this->sanitize($request->data());

    $oldValues = $this->filteredInput($sanitized);
    ee()->session->set_flashdata($this->addon_name . '_old_values_' . $id, $oldValues);

    if (!empty($errors)) {
      $errorReturnUrl = isset($sanitized['error_url']) ? $sanitized['error_url'] : ee()->functions->form_backtrack('-1');
      ee()->session->set_flashdata($this->addon_name . '_validation_errors_' . $id, $errors);

      

      if ($request->isAjax()) {
        return ee('dunique:Response')->error($errors);
      }
      
      return ee()->functions->redirect($errorReturnUrl);
    }
  }
  public function setSuccessful(string $id)
  {
    ee()->session->set_flashdata($this->addon_name . '_validation_success_' . $id, true);
  }
  private function generateId()
  {
    $formCount = ee()->session->cache($this->addon_name, 'form_id_counter') ? ee()->session->cache($this->addon_name, 'form_id_counter') : 0;
    $formCount++;
    ee()->session->set_cache($this->addon_name, 'form_id_counter', $formCount);
    $formID = empty(ee()->TMPL->form_id) ? $this->addon_name . '_form_' . $formCount : ee()->TMPL->form_id;
    return $formID;
  }

  public function filteredInput($data)
  {
    $reserved = ['site_id', 'return_url', 'error_url', 'csrf_token'];
    
    $filtered = array_filter($data, function ($key) use ($reserved) {
      // Exclude keys that are not in the reserved list
      if (in_array($key, $reserved)) {
        return false;
      };
      // Exclude keys that start with '_' and are not array
      return strpos($key, '_') !== 0; // Keep keys that do not start with '_'
    }, ARRAY_FILTER_USE_KEY);

    // Remove any harmfull values from the array
    array_walk_recursive($filtered, function (&$value) {
      $value = strip_tags($value);
    });
    
    return $filtered;
  }

  public function variables($form)
  {
    $variables = [];

    // Validation variables
    $variables['is_send'] = $form['is_send'];
    $variables['has_errors'] = !empty($form['errors']);

    // Format errors to variables
    if ($form['errors']) {
      foreach ($form['errors'] as $error => $message) {
        $variables['errors'][] = [
          'errors:field' => $error,
          'errors:message' => $message
        ];
        $variables['error:'.$error] = $message;
      }
    }
    // Format hidden field variables
    foreach ($form['form_declaration']['hidden_fields'] as $name => $value) {
      $variables['hidden:'.$name] = $value;
    }

    $variables['action_url'] = $form['form_declaration']['action'];

    // Format old values to variables if its not send
    if ($form['old_values'] && !$variables['is_send']) {
      foreach ($form['old_values'] as $field => $value) {
        $variables['old:'.$field] = $value;
      }
    }
    // Parse structure tags in variables
    array_walk_recursive($variables, function (&$value) {
      $value = preg_replace_callback(
          "({structure:page_url_for:(\d{1,})})",
          [$this, '_parse_structure_url'], // Callback for replacement
          $value
      );
    });

    return $variables;
  }

  // Callback for parsing structure URLs
  private function _parse_structure_url($matches)
  {
      $entryId = $matches[1];
      
      $url = ee()->db->select('structure_url_title')
          ->from('structure')
          ->where('entry_id', $entryId)
          ->get()
          ->row('structure_url_title');

      return ee()->functions->create_url($url);
  }

  private function prepareHiddenFields($form_details)
  {
    $fields = [];
    if (!empty($form_details)) {
      // Set Hidden Fields
      $hidden_fields = [];
      $rules = [];
      $secure = [];
      $attachments = [];

      foreach ($form_details as $key => $value) {
        if (strpos($key, 'hidden:') === 0) {
          $hidden_fields[substr($key, strlen('hidden:'))] = $value;
        }
        if (strpos($key, 'rules:') === 0) {
          $rules[substr($key, strlen('rules:'))] = $value;
        }
        if (strpos($key, 'secure:') === 0) {
          $secure[substr($key, strlen('secure:'))] = $value;
        }
        if (strpos($key, 'secure_storage:') === 0) {
          $secure['storage'][substr($key, strlen('secure_storage:'))] = $value;
        }
        if (strpos($key, 'attachment') === 0) {
          $attachments = explode("|", $value);
        }
      }

      $hidden_fields = array_filter($hidden_fields);
      $rules = array_filter($rules);
      $data = [];

      $fields = $hidden_fields;
      if (!empty($secure)) {
        $data = $secure;
      }

      if (!empty($rules)) {
        $data['rules'] = base64_encode(serialize($rules));
      }

      if (isset($form_details['return_url'])) {
        $fields['return_url'] = $form_details['return_url'];
      }
      if (isset($form_details['error_url'])) {
        $fields['error_url'] = $form_details['error_url'];
      }
      if (!empty($attachments)) {
        $attachmentsInfo = [];
        foreach ($attachments as $attachment) {
          $info = ee($this->addon_name . ':Helper')->getFileInfoFromURL($attachment);
          if ($info) {
            $attachmentsInfo[] = $info;
          }
        }
        $data['attachments'] = $attachmentsInfo;
      }
      // // Generate a signature
      // $data['signature'] = $this->generateSignature(serialize($data), $form_details['id']);

      // Encrypt the data payload
      $fields['_data'] = ee('Encrypt')->encode(serialize($data));
    }

    return $fields;
  }

  private function honeypot()
  {
    $honeypotFieldNames = ['_malicious_name', '_malicious_email'];

    $honeypotFields = '';
    ee()->load->helper('form');
    foreach ($honeypotFieldNames as $name) {
      $honeypotFields .= form_input($name, '', "style='opacity:0;position:absolute;top:0;left:0;height:0;width:0;z-index:-1'");
    }

    return $honeypotFields;
  }
}

// EOF
