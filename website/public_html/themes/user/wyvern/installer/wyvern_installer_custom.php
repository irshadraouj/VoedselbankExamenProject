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
require_once(PATH_THIRD . 'wyvern/installer/wyvern_installer_base.php');

class Wyvern_installer_custom extends Wyvern_installer_base
{

    public function __construct()
    {
        parent::__construct();
    }

    // ----------------------------------------------------------------

    public function install()
    {
        //Default action route
        $this->register_action('act_route');

        //install the extension
        $this->register_hook('sessions_start');
        $this->register_hook('template_post_parse');
        $this->register_hook('cp_js_end');
        $this->register_hook('channel_search_modify_search_query');
        // used for building the dynamic list of templates
        $this->register_hook('after_template_save', 'cacheTemplateList');
        $this->register_hook('after_template_delete', 'cacheTemplateList');
        $this->register_hook('after_template_group_save', 'cacheTemplateList');
        $this->register_hook('after_template_group_delete', 'cacheTemplateList');

        //create the Login backup tables
        $this->create_tables();

        //insert the toolbar data
        ee('Model')->make('wyvern:Toolbar')->set(
            array(
                'site_id' => ee()->config->item('site_id'),
                'toolbar_name' => 'empty',
                'toolbar_settings' => \Wyvern_config::toolbar()->empty,
            )
        )->save();
        ee('Model')->make('wyvern:Toolbar')->set(
            array(
                'site_id' => ee()->config->item('site_id'),
                'toolbar_name' => 'Basic',
                'toolbar_settings' => \Wyvern_config::toolbar()->basic,
            )
        )->save();
        ee('Model')->make('wyvern:Toolbar')->set(
            array(
                'site_id' => ee()->config->item('site_id'),
                'toolbar_name' => 'Lite',
                'toolbar_settings' => \Wyvern_config::toolbar()->lite,
            )
        )->save();
        ee('Model')->make('wyvern:Toolbar')->set(
            array(
                'site_id' => ee()->config->item('site_id'),
                'toolbar_name' => 'Full',
                'toolbar_settings' => \Wyvern_config::toolbar()->full,
            )
        )->save();
    }

    public function uninstall()
    {
        //remove databases
        foreach (\Wyvern_config::mysql_table_data() as $table => $data) {
            ee()->dbforge->drop_table(WYVERN_MAP . '_' . $table);
        }
    }

    // ----------------------------------------------------------------

    /**
     * Create a tab
     *
     * @return    boolean    TRUE
     */
    public function tabs()
    {
        $tabs['tab_name'] = array(
            'field_name_one' => array(
                'visible' => 'true',
                'collapse' => 'false',
                'htmlbuttons' => 'true',
                'width' => '100%'
            ),
            'field_name_two' => array(
                'visible' => 'true',
                'collapse' => 'false',
                'htmlbuttons' => 'true',
                'width' => '100%'
            ),
        );

        return $tabs;
    }


} // END CLASS
