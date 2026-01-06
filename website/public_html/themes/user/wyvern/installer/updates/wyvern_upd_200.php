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

class Wyvern_upd_200
{
    /**
     * Construct method
     */
    public function __construct()
    {
        //load the classes
        ee()->load->dbforge();
    }

    /**
     * Run the update
     *
     * @return      boolean         TRUE
     */
    public function run_update()
    {
        // add config tables
        ee()->dbforge->drop_table(WYVERN_MAP . '_settings');
        $fields = array(
            'settings_id' => array(
                'type' => 'int',
                'constraint' => 7,
                'unsigned' => true,
                'null' => false,
                'auto_increment' => true
            ),
            'site_id' => array(
                'type' => 'int',
                'constraint' => 7,
                'unsigned' => true,
                'null' => false,
                'default' => 0
            ),
            'var' => array(
                'type' => 'varchar',
                'constraint' => '200',
                'null' => false,
                'default' => ''
            ),
            'value' => array(
                'type' => 'text'
            ),
        );
        ee()->dbforge->add_field($fields);
        ee()->dbforge->add_key('settings_id', true);
        ee()->dbforge->create_table(WYVERN_MAP . '_settings', true);

        // add the toolbar
        ee()->dbforge->drop_table(WYVERN_MAP . '_toolbars');
        $fields = array(
            'toolbar_id' => array(
                'type' => 'int',
                'constraint' => 7,
                'unsigned' => true,
                'null' => false,
                'auto_increment' => true
            ),
            'site_id' => array(
                'type' => 'int',
                'constraint' => 7,
                'unsigned' => true,
                'null' => false,
                'default' => 0
            ),
            'toolbar_name' => array(
                'type' => 'varchar',
                'constraint' => '200',
                'null' => false,
                'default' => ''
            ),
            'toolbar_settings' => array(
                'type' => 'text'
            ),
        );
        ee()->dbforge->add_field($fields);
        ee()->dbforge->add_key('toolbar_id', true);
        ee()->dbforge->create_table(WYVERN_MAP . '_toolbars', false);

        //delete old actions
        ee()->db->where('class', WYVERN_CLASS);
        ee()->db->where('method', 'save_toolbar');
        ee()->db->delete('actions');

        ee()->db->where('class', WYVERN_CLASS);
        ee()->db->where('method', 'load_toolbar');
        ee()->db->delete('actions');

        ee()->db->where('class', WYVERN_CLASS);
        ee()->db->where('method', 'load_pages');
        ee()->db->delete('actions');

        ee()->db->where('class', WYVERN_CLASS);
        ee()->db->where('method', 'load_templates');
        ee()->db->delete('actions');

        ee()->db->where('class', WYVERN_CLASS);
        ee()->db->where('method', 'delete_toolbar');
        ee()->db->delete('actions');

        //delete old extenstions
        ee()->db->where('class', WYVERN_CLASS . '_ext');
        ee()->db->where('method', 'sessions_start');
        ee()->db->delete('extensions');

        ee()->db->where('class', WYVERN_CLASS . '_ext');
        ee()->db->where('method', 'sessions_end');
        ee()->db->delete('extensions');

        ee()->db->where('class', WYVERN_CLASS . '_ext');
        ee()->db->where('method', 'submit_new_entry_start');
        ee()->db->delete('extensions');

        //remove the submit_new_entry_start hook
        ee()->db->where('class', WYVERN_CLASS . '_ext');
        ee()->db->where('method', 'channel_entries_tagdata_end');
        ee()->db->delete('extensions');

        //add new hooks
        ee()->db->insert(
            'extensions',
            array(
                'class' => WYVERN_CLASS . '_ext',
                'method' => 'sessions_start',
                'hook' => 'sessions_start',
                'settings' => '',
                'priority' => 10,
                'version' => WYVERN_VERSION,
                'enabled' => 'y'
            )
        );

        ee()->db->insert(
            'extensions',
            array(
                'class' => WYVERN_CLASS . '_ext',
                'method' => 'channel_entries_tagdata_end',
                'hook' => 'channel_entries_tagdata_end',
                'settings' => '',
                'priority' => 10,
                'version' => WYVERN_VERSION,
                'enabled' => 'y'
            )
        );

        //insert new data in the toolbar
        ee()->db->insert(
            'wyvern_toolbars',
            array(
                'toolbar_name' => 'Full',
                'toolbar_settings' => serialize(\Wyvern_config::toolbar()->full),
                'site_id' => ee()->config->item('site_id')
            )
        );
        ee()->db->insert(
            'wyvern_toolbars',
            array(
                'toolbar_name' => 'Basic',
                'toolbar_settings' => serialize(\Wyvern_config::toolbar()->basic),
                'site_id' => ee()->config->item('site_id')
            )
        );
        ee()->db->insert(
            'wyvern_toolbars',
            array(
                'toolbar_name' => 'Lite',
                'toolbar_settings' => serialize(\Wyvern_config::toolbar()->lite),
                'site_id' => ee()->config->item('site_id')
            )
        );

        // add the actions
        ee()->db->insert(
            'actions',
            array(
                'class' => WYVERN_CLASS,
                'method' => 'act_route'
            )
        );
    }

}
