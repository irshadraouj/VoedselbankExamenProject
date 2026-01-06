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
require_once(PATH_THIRD . 'wyvern/config.php');

/**
 * Include helper
 */
require_once(PATH_THIRD . 'wyvern/libraries/wyvern_helper.php');

class Wyvern_settings
{

    private $_config_items = array();

    private $_config_defaults = array();

    public $act_url;
    public $default_settings;
    public $settings;

    /**
     * Constructor
     */
    public function __construct()
    {
        //fix document_root
        if (substr($_SERVER['DOCUMENT_ROOT'], -1) != '/') {
            $path = $_SERVER['DOCUMENT_ROOT'] . '/';
        } else {
            $path = $_SERVER['DOCUMENT_ROOT'];
        }

        //load string helper
        ee()->load->helper('string');

        //set the default settings
        $this->default_settings = array(
            'module_dir' => PATH_THIRD . WYVERN_MAP . '/',
            'theme_dir' => PATH_THIRD_THEMES . WYVERN_MAP . '/',
            'theme_url' => URL_THIRD_THEMES . WYVERN_MAP . '/',
            'site_id' => ee()->config->item('site_id'),
            'site_url' => reduce_double_slashes(
                ee()->config->item('site_url') . '/' . ee()->config->item('site_index') . '/'
            ),
            'base_dir' => $path,
            'cache_path' => (ee()->config->item('cache_path') ? ee()->config->item(
                    'cache_path'
                ) : PATH_CACHE) . WYVERN_MAP
        );

        //create a tmp dir
        if (!@is_dir($this->default_settings['cache_path'])) {
            @mkdir($this->default_settings['cache_path'], 0777);
        }
        @chmod($this->default_settings['cache_path'], 0777);

        // DB and BASE dependend
        if (REQ == 'CP' && isset(ee()->db)) {
            //is the BASE constant defined?
            if (!defined('BASE')) {
                $s = '';

                if (ee()->config->item('admin_session_type') != 'c') {
                    if (isset(ee()->session)) {
                        $s = ee()->session->userdata('session_id', 0);
                    }
                }

                //lets define the BASE
                define('BASE', SELF . '?S=' . $s . '&amp;D=cp');
            }

            $this->default_settings['base_url'] = ee('CP/URL', 'addons/settings/' . WYVERN_MAP);
            $this->default_settings['base_url_js'] = str_replace(
                '&amp;',
                '&',
                ee('CP/URL', 'addons/settings/' . WYVERN_MAP)
            );
        }

        //Custom (override) Config vars
        if (!empty(\Wyvern_config::overide_settings())) {
            foreach (\Wyvern_config::overide_settings() as $key => $val) {
                $this->default_settings[$key] = ee()->config->item($key) != '' ? ee()->config->item($key) : str_replace(
                    array('[theme_dir]', '[theme_url]'),
                    array($this->default_settings['theme_dir'], $this->default_settings['theme_url']),
                    $val
                );
            }
        }

        $installed = ee()->db->where('module_name', 'Wyvern')->from('modules')->get();
        if($installed->num_rows()) {
            //check if all default settings are present
            //e.g. for MSM we must recreate the settings for the other site_id
            $this->check_db_settings();

            //get the settings
            $this->settings = $this->load_settings();
        }
    }

    // ----------------------------------------------------------------

    /**
     * Insert the settings to the database
     *
     * @param none
     * @return void
     */
    public function first_import_settings()
    {
        foreach (\Wyvern_config::default_post() as $key => $val) {
            $data[] = array(
                'site_id' => $this->default_settings['site_id'],
                'var' => $key,
                'value' => $val,
            );
        }

        //insert into db
        ee()->db->insert_batch(WYVERN_MAP . '_settings', $data);

        //clear data
        unset($data);
    }

    // ----------------------------------------------------------------

    /**
     * check if all default settings are present
     * e.g. for MSM we must recreate the settings for the other site_id
     *
     * @param none
     * @return void
     */
    public function check_db_settings()
    {
        if (ee()->db->table_exists(WYVERN_MAP . '_settings')) {
            //check if there is any result
            $check = ee()->db->from(WYVERN_MAP . '_settings')->get();
            if ($check->num_rows() > 0) {
                //get the site IDS
                $sites = $this->get_sites();

                $cache_result = array();

                //cache result
                foreach ($check->result() as $row) {
                    $cache_result[$row->site_id][$row->var] = $row->value;
                }

                //loop over the sites
                foreach ($sites as $site_id) {
                    foreach (\Wyvern_config::default_post() as $key => $val) {
                        //check the setting
                        //ee()->db->where('var', $key);
                        //ee()->db->where('site_id', $site_id);
                        //$q = ee()->db->get(WYVERN_MAP.'_settings');

                        //if the setting not presents, we have to create this one
                        if (!isset($cache_result[$site_id][$key])) {
                            $data = array(
                                'site_id' => $site_id,
                                'var' => $key,
                                'value' => ($site_id != 1 ? $this->get_db_settings(1, $key) : $val),
                            );

                            //insert into db
                            ee()->db->insert(WYVERN_MAP . '_settings', $data);
                        }
                    }
                }
            } else {
                $this->first_import_settings();
            }
        }
    }

    // ----------------------------------------------------------------------

    /**
     * Get the Settings
     *
     * @param $all_sites
     * @return mixed array
     */
    public function load_settings($all_sites = true)
    {
        if (ee()->db->table_exists(WYVERN_MAP . '_settings')) {
            //get the settings from the database
            $get_setting = ee()->db->get_where(
                WYVERN_MAP . '_settings',
                array(
                    'site_id' => $this->default_settings['site_id']
                )
            );

            //load helper
            ee()->load->helper('string');

            //set the settings
            $settings = array();
            foreach ($get_setting->result() as $row) {
                //is serialized?
                if (call_user_func(array(WYVERN_MAP . '_helper', 'is_serialized'), $row->value)) {
                    $settings[$row->var] = @unserialize($row->value);
                } //default value
                else {
                    $settings[$row->var] = $row->value;
                }
            }

            //clear data
            unset($get_setting);

            //return the settings
            return array_merge($this->default_settings, $settings);
        } else {
            return $this->default_settings;
        }
    }

    // ----------------------------------------------------------------------

    /**
     * Get specific setting
     *
     * @param $all_sites
     * @return mixed array
     */
    public function get_settings($setting_name, $def_value = '')
    {
        if (isset($this->settings[$setting_name])) {
            //empty, return defualt value
            if ($this->settings[$setting_name] == '') {
                return $def_value;
            }

            return $this->settings[$setting_name];
        }
        //nothing, return default value
        return $def_value;
    }

    //alias
    public function get_setting($setting_name, $def_value = '')
    {
        return $this->get_settings($setting_name, $def_value);
    }

    //alias
    public function item($setting_name, $def_value = '')
    {
        return $this->get_settings($setting_name, $def_value);
    }

    // ----------------------------------------------------------------------

    /**
     * Get specific setting from the db instead of the array
     *
     * @param $all_sites
     * @return mixed array
     */
    public function get_db_settings($site_id = '', $setting_name = '')
    {
        ee()->db->where('site_id', $site_id);
        ee()->db->where('var', $setting_name);
        ee()->db->from(WYVERN_MAP . '_settings');
        $q = ee()->db->get();

        if ($q->num_rows() > 0) {
            return $q->row()->value;
        }
        return '';
    }

    // ----------------------------------------------------------------

    /**
     * Prepare settings to save
     *
     * @return    DB object
     */
    public function save_post_settings($redirect_uri = '')
    {
        if (isset($_POST)) {
            //remove submit value
            unset($_POST['submit']);

            //loop through the post values
            foreach (\Wyvern_config::default_post() as $key => $val) {
                if (isset($_POST[$key])) {
                    $val = $_POST[$key];
                    $this->save_setting($key, $val);
                }
            }
        }

        //set a message
        $alert = ee('CP/Alert')->makeInline(WYVERN_MAP . '_settings')
            ->asSuccess()
            ->withTitle(lang('success'))
            ->addToBody(ee()->lang->line('preferences_updated'));

        $alert->defer();

        //redirect
        ee()->functions->redirect(ee('CP/URL', 'addons/settings/' . WYVERN_MAP . $redirect_uri));
    }

    // ----------------------------------------------------------------

    /**
     * Prepare settings to save
     *
     * @return    DB object
     */
    public function save_setting($key = '', $val = '')
    {
        if ($key != '') {
            //set the where clause
            ee()->db->where('var', $key);
            ee()->db->where('site_id', $this->item('site_id'));

            //is this a array?
            if (is_array($val)) {
                $val = serialize($val);
            }

            //update the record
            ee()->db->update(
                WYVERN_MAP . '_settings',
                array(
                    'value' => $val
                )
            );
        }
    }

    // ----------------------------------------------------------------

    /**
     * set a static setting
     *
     * @return    DB object
     */
    public function set_setting($key = '', $val = '')
    {
        $this->settings[$key] = $val;
    }

    // ----------------------------------------------------------------

    /**
     * Get the sites
     *
     * @return    DB object
     */
    public function get_sites()
    {
        ee()->db->select('site_id');
        $sites = ee()->db->get('sites');

        $return = array();

        if ($sites->num_rows() > 0) {
            foreach ($sites->result() as $site) {
                $return[] = $site->site_id;
            }
        }

        return $return;
    }

}
