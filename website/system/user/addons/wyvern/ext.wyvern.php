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

/**
 * Include the config file
 */
require_once PATH_THIRD . 'wyvern/config.php';

class Wyvern_ext
{

    public $name = WYVERN_NAME;
    public $description = WYVERN_DESCRIPTION;
    public $version = WYVERN_VERSION;
    public $settings = array();
    public $docs_url = WYVERN_DOCS;
    public $settings_exist = 'n';
    //public $required_by 	= array(WYVERN_NAME);

    /**
     * Constructor
     *
     * @param mixed    Settings array or empty string if none exist.
     */
    public function __construct($settings = '')
    {
    }

    // ----------------------------------------------------------------------

    /**
     * @return string
     */
    function cp_js_end()
    {
        $js = '';

        // play nice with other extensions on this hook
        if (isset(ee()->extensions->last_call) && ee()->extensions->last_call) {
            $js = ee()->extensions->last_call;
        }

        //get the license status
        $js = $js . ' EE.' . WYVERN_MAP . '_license_valid = ' . (ee(WYVERN_MAP . ':License')->hasValidLicense(
            ) ? 'true' : 'false') . ';' . "\n";
        $js = $js . ' EE.' . WYVERN_MAP . '_license_entered = ' . (ee(WYVERN_MAP . ':License')->getLicense(
            ) == '' ? 'false' : 'true') . ';' . "\n";
        $js = $js . ' EE.' . WYVERN_MAP . '_license= "' . ee(WYVERN_MAP . ':License')->getLicense() . '"' . "\n";

        //attach js
        $js = $js . file_get_contents(PATH_THIRD . '/' . WYVERN_MAP . '/assets/javascript/cp_js_end.js');

        return $js;
    }

    // ----------------------------------------------------------------------

    /**
     * default to set the action url
     *
     * @param mixed    Settings array or empty string if none exist.
     */
    public function sessions_start($session)
    {
        if (REQ == 'PAGE' || REQ == 'ACTION') {
            //set the session
            if (!isset(ee()->session)) {
                ee()->set('session', $session);
            }

            ee()->load->library(WYVERN_MAP . '_lib');

            //get the action id
            ee()->config->_global_vars[WYVERN_MAP . '_action_url'] = '?ACT=' . wyvern_helper::fetch_action_id(
                    WYVERN_CLASS,
                    'act_route'
                );

            //create page_urls global vars
            ee()->wyvern_lib->create_gb_page_urls();
            ee()->wyvern_lib->create_gb_template_urls();

            //weird fix for session on the loader error
            ee()->remove('session');
            //dumper(ee()->config->_global_vars);
        }
    }

    public function channel_search_modify_search_query($sql, $hash)
    {
        if (ee('wyvern:Version')->isGteEE4()) {
            //special chars
            $special_chars = array(
                'Æ' => '&AElig;',
                'æ' => '&aelig;',
                'Å' => '&Aring;',
                'å' => '&aring;',
                'Ä' => '&Auml;',
                'ä' => '&auml;',
                'Ð' => '&ETH;',
                'ð' => '&eth;',
                'Ø' => '&Oslash;',
                'ø' => '&oslash;',
                'Ö' => '&Ouml;',
                'ö' => '&ouml;',
                'Þ' => '&THORN;',
                'þ' => '&thorn;',
            );

            $field_ids = array();

            //first get all wyvern fields from the native fields
            $q = ee()->db->from('channel_fields')->where('field_type', 'wyvern')->get();
            if ($q->num_rows() > 0) {
                foreach ($q->result() as $field) {
                    $field_ids[$field->field_id] = $field->field_id;
                }
            }

            //get all wyvern fields from the Grid fields
            $q = ee()->db->from('grid_columns')->where('col_type', 'wyvern')->get();
            if ($q->num_rows() > 0) {
                foreach ($q->result() as $field) {
                    $field_ids[$field->field_id] = $field->field_id;
                }
            }

            //get all wyvern fields from the Fluid fields
            $q = ee()->db
                ->from('channel_fields')
                ->where('field_type', 'wyvern')
                ->join('fluid_field_data', 'fluid_field_data.field_id = channel_fields.field_id')
                ->get();
            if ($q->num_rows() > 0) {
                foreach ($q->result() as $field) {
                    $field_ids[$field->field_id] = $field->fluid_field_id;
                }
            }

            foreach ($field_ids as $field_id) {
                if (preg_match(
                        "/\(exp_channel_data_field_" . $field_id . ".field_id_" . $field_id . "(.*?)\)/s",
                        $sql,
                        $match
                    ) != 0) {
                    //get the raw query
                    $_query_raw = str_replace(array('(', ')'), '', $match[0]);

                    //repace the special chars
                    $_query_duplicate = str_replace(array_keys($special_chars), $special_chars, $_query_raw);


                    $sql = str_replace($match[0], '(' . $_query_raw . ' OR ' . $_query_duplicate . ')', $sql);
                }
            }
        }


        return $sql;
    }

    // ----------------------------------------------------------------------

    /**
     * D'oh! global vars can not be used as custom field data because it isn't parsed at the correct time, lets fix that.
     * This will only work if you know the variables name or pattern.
     * If we added a loop, we could go through the entire _global_vars array and replace everything, but we don't need to for this extension.
     */
    public function template_post_parse($final_template, $is_partial, $site_id)
    {
        // has this hook already been called?
        if (ee()->extensions->last_call) {
            $final_template = ee()->extensions->last_call;
        }

        // find {page_url:n} and {template_url:n}
        foreach (array('page_url', 'template_url') as $var_name) {
            if (preg_match_all("/{" . $var_name . ":(\d+)}/", $final_template, $matches)) {
                foreach ($matches[1] as $match => $mval) {
                    // If the page ID exists, replace the tag accordingly
                    if (isset(ee()->config->_global_vars[$var_name . ':' . $mval])) {
                        $url = reduce_double_slashes(
                            str_replace(
                                '{base_url}',
                                ee()->config->item('base_url'),
                                ee()->config->_global_vars[$var_name . ':' . $mval]
                            )
                        );
                        $final_template = preg_replace("/{" . $var_name . ":$mval}/", $url, $final_template);
                    } // If not, then replace the tag with a blank string, we don't want the tag itself to be rendered
                    else {
                        $final_template = preg_replace("/{" . $var_name . ":$mval}/", '', $final_template);
                    }
                }
            }
        }

        $final_template = str_replace('{base_url}', ee()->config->item('base_url'), $final_template);

        return $final_template;
    }

    public function cacheTemplateList()
    {
        $cachedTemplates = [];

        ee()->load->library(WYVERN_MAP . '_lib');
        $urls = ee()->wyvern_model->get_templates();

        if (!empty($urls)) {
            foreach ($urls as $key => $val) {
                $cachedTemplates[$key] = $val;
            }
        }

        ee()->wyvern_settings->save_setting('templates', serialize($cachedTemplates));
    }

    // ----------------------------------------------------------------------

    /**
     * Activate Extension
     *
     * This function enters the extension into the exp_extensions table
     *
     * @see http://codeigniter.com/user_guide/database/index.html for
     * more information on the db class.
     *
     * @return void
     */
    public function activate_extension()
    {
        //the module will install the extension if needed
        return true;
    }

    // ----------------------------------------------------------------------

    /**
     * Disable Extension
     *
     * This method removes information from the exp_extensions table
     *
     * @return void
     */
    public function disable_extension()
    {
        //the module will disable the extension if needed
        return true;
    }

    // ----------------------------------------------------------------------

    /**
     * Update Extension
     *
     * This function performs any necessary db updates when the extension
     * page is visited
     *
     * @return    mixed    void on update / false if none
     */
    public function update_extension($current = '')
    {
        //the module will update the extension if needed
        return true;
    }

    // ----------------------------------------------------------------------
}
