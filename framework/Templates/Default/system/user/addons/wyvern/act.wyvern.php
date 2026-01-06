<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @author        Rein de Vries <support@reinos.nl>
 * @link        http://addons.reinos.nl
 * @copyright    Copyright (c) 2011 - 2025 Reinos.nl Internet Media
 * @license     http://addons.reinos.nl/commercial-license
 *
 * Copyright (c) 2011 - 2025 Reinos.nl Internet Media
 * All rights reserved.
 *
 * This source is commercial software. Use of this software requires a
 * site license for each domain it is used on. Use of this software or any
 * of its source code without express written permission in the form of
 * a purchased commercial or other license is prohibited.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE.
 *
 * As part of the license agreement for this software, all modifications
 * to this source must be submitted to the original author for review and
 * possible inclusion in future releases. No compensation will be provided
 * for patches, although where possible we will attribute each contribution
 * in file revision notes. Submitting such modifications constitutes
 * assignment of copyright to the original author (Rein de Vries and
 * Reinos.nl Internet Media) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
 */

require_once(PATH_THIRD . 'wyvern/config.php');

class Wyvern_ACT
{
    /**
     * Constructor
     *
     */
    function __construct()
    {
        //Load the lib
        ee()->load->library(WYVERN_MAP . '_lib');
        ee()->lang->loadfile(WYVERN_MAP);
    }

    // ----------------------------------------------------------------------------------

    /**
     * dispatch the actions via ajax or so.
     *
     * @return unknown_type
     */
    function init()
    {
        //get the method
        $method = ee()->input->get_post('method');

        //call the method if exists
        if (method_exists($this, $method)) {
            echo $this->{$method}();

            die();
        }

        echo 'no_method';
        exit;
    }

    //-------------------------------------------------

    /**
     * Add the CP css
     */
    public function cp_css()
    {
        header("Content-type: text/css; charset: UTF-8");
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0, false');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Pragma: no-cache');
        echo file_get_contents(PATH_THIRD . '/' . WYVERN_MAP . '/assets/styles/cp.css');
        exit;
    }

    //----------------------------------------------------------------------------------------

    /**
     * set a JS the site pages
     */
    public function get_pages()
    {
        header('Content-type: application/javascript');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0, false');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Pragma: no-cache');
        $uris = ee()->wyvern_lib->get_pages(false, true, true);

        echo "REINOS_WYVERN.config.sitePages = " . json_encode($uris);
    }

    //----------------------------------------------------------------------------------------

    /**
     * Generate a JS var for the site templates
     */
    public function get_templates()
    {
        header('Content-type: application/javascript');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0, false');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Pragma: no-cache');
        $templates = ee()->wyvern_settings->item('linkable_dialog_templates');

        $new_templates = array(array(''));
        if (!empty($templates) || $templates != '') {
            foreach ($templates as $template_id => $template_path) {
                $url = '{template_url:' . $template_id . '}';
                $new_templates[] = array($template_path, $url);
            }
        }

        echo "REINOS_WYVERN.config.siteTemplates = " . json_encode($new_templates);
    }

    //----------------------------------------------------------------------------------------

    /**
     * Generate a JS var for the site templates
     */
    public function get_presets()
    {
        $url = ee()->input->get_post('file_url');
        $file_parts = explode('/', $url);
        $file_name = array_pop($file_parts);
        $selected_preset_name = ltrim(array_pop($file_parts), '_');

        $presets = array();
        $preset_names = array();

        $uploadModels = ee('Model')->get('UploadDestination');
        if ($uploadModels->count() > 0) {
            foreach ($uploadModels->all() as $uploadModel) {
                $preset_url = rtrim($uploadModel->url, '/');
                $preset_url_parts = explode('/', $preset_url);
                $founded_preset_name = array_pop($preset_url_parts);

                if ($selected_preset_name == $founded_preset_name) {
                    $fileModel = ee('Model')->get('File')->filter('file_name', $file_name);
                    if ($fileModel->count() > 0) {
                        $file = $fileModel->first();

                        $presets[] = array(
                            'original',
                            rtrim($file->UploadDestination->url, '/') . '/' . rawurlencode($file->file_name)
                        );
                        $preset_names[] = 'original';

                        if ($uploadModel->FileDimensions->count() > 0) {
                            foreach ($uploadModel->FileDimensions->toArray() as $dimension) {
                                $name = $dimension['short_name'];

                                $name_extra = $dimension['resize_type'] . ': ';
                                if ($dimension['width'] != 0) {
                                    $name_extra .= $dimension['width'] . 'px wide';
                                }
                                if ($dimension['height'] != 0) {
                                    $name_extra .= $dimension['height'] . 'px tall';
                                }

                                $file_url = rtrim(
                                        $file->UploadDestination->url,
                                        '/'
                                    ) . '/_' . $dimension['short_name'] . '/' . rawurlencode($file->file_name);
                                $presets[] = array($name . ' (' . $name_extra . ')', $file_url);
                                $preset_names[] = $name;
                            }
                        }
                    }
                }
            }
        }

        //get the selected one
        $selected_preset = $url;
        $preset_index = array_search($selected_preset_name, $preset_names);
        if ($preset_index !== false) {
            if (isset($presets[$preset_index])) {
                $selected_preset = $presets[$preset_index][1];
            }
        }


        echo json_encode(array($selected_preset, $presets));

        exit;
    }

    public function getPerFieldCss()
    {
        header("Content-type: text/css; charset: UTF-8");

        $field_id = ee()->input->get_post('field_id');
        $fieldModel = ee('Model')->get('ChannelField')->filter('field_id', $field_id);

        if ($fieldModel->count() > 0) {
            $field = $fieldModel->first();
            $settings = $field->field_settings;

            if (isset($settings['wyvern_css'])) {
                echo $settings['wyvern_css'];
                exit;
            }
        }

        exit;
    }
}
