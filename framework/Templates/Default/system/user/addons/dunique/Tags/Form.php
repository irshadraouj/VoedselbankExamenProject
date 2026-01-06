<?php

namespace Dunique\AntwanVanBoheemen\Tags;

use ExpressionEngine\Service\Addon\Controllers\Tag\AbstractRoute;

class Form extends AbstractRoute {
  public  function process() {
    
    $form = ee('dunique:Form')->make(ee()->TMPL->tagdata, ee()->TMPL->tagparams, 'Post');

    $variables = ee('dunique:Form')->variables($form);
    // var_dump($form);
    // dd($form);
    // // Add honeypot (malicious) fields
    // $honeypotFieldNames = ['malicious_name','malicious_email'];

    // $honeypotFields = '';
    // ee()->load->helper('form');
    // foreach ($honeypotFieldNames as $name) {
    //   $honeypotFields.= form_input($name, '', "style='opacity:0;position:absolute;top:0;left:0;height:0;width:0;z-index:-1'");
    // }

    // $form_open = ee()->functions->form_declaration($form_details);
    // $form_open .= $honeypotFields;
    // $tagdata = ee()->TMPL->tagdata;
    // $form_close = form_close();
    // $tagdata = $form_open . $tagdata . $form_close;
    return ee()->TMPL->parse_variables( (ee()->TMPL->fetch_param('output') == 'no') ? ee()->TMPL->tagdata : $form['tagdata'], [$variables]);
  }
}
