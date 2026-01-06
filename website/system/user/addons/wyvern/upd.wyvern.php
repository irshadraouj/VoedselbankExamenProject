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

class Wyvern_upd
{
    public $version = WYVERN_VERSION;

    /**
     * Constructor
     */
    public function __construct()
    {
        //load the updater class
        ee()->load->library(WYVERN_MAP . '_installer');
    }

    // ----------------------------------------------------------------

    /**
     * Installation Method
     *
     * @return    boolean    TRUE
     */
    public function install()
    {
        //set the module data
        $mod_data = array(
            'module_name' => WYVERN_CLASS,
            'module_version' => WYVERN_VERSION,
            'has_cp_backend' => "y",
            'has_publish_fields' => 'n'
        );

        //insert the module
        ee()->db->insert('modules', $mod_data);

        //install module specific
        ee()->wyvern_installer->install();

        //load the helper
        ee()->load->library(WYVERN_MAP . '_lib');

        //insert the settings data
        ee()->wyvern_settings->first_import_settings();

        return true;
    }

    // ----------------------------------------------------------------

    /**
     * Uninstall
     *
     * @return    boolean    TRUE
     */
    public function uninstall()
    {
        //delete the module
        ee()->db->where('module_name', WYVERN_CLASS);
        ee()->db->delete('modules');

        //uninstall module specific
        ee()->wyvern_installer->uninstall();

        //remove actions
        ee()->db->where('class', WYVERN_CLASS);
        ee()->db->delete('actions');

        //remove the extension
        ee()->db->where('class', WYVERN_CLASS . '_ext');
        ee()->db->delete('extensions');

        //delete tabs
        ee()->load->library('layout');
        ee()->layout->delete_layout_tabs(ee()->wyvern_installer->tabs(), WYVERN_MAP);

        return true;
    }

    // ----------------------------------------------------------------

    /**
     * Module Updater
     *
     * @return    boolean    TRUE
     */
    public function update($current = '')
    {
        if ($current == '' or $current == $this->version) {
            return false;
        }

        //loop through the updates and install them.
        if (!empty(\Wyvern_config::updates())) {
            foreach (\Wyvern_config::updates() as $version) {
                if (version_compare($current, $version, '<')) {
                    ee()->wyvern_installer->init_update($version);
                }
            }

            //fix for updating a fieldtype???
            ee()->db->where('name', WYVERN_CLASS);
            ee()->db->update(
                'fieldtypes',
                array(
                    'version' => WYVERN_VERSION
                )
            );
        }

        return true;
    }
}
