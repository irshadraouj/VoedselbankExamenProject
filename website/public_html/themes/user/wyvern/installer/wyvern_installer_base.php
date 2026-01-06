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

class Wyvern_installer_base
{

    public function __construct()
    {
        //load the classes
        ee()->load->dbforge();
    }

    // ----------------------------------------------------------------

    /**
     * Add the tables for the module
     *
     * @param string $table_name
     */
    public function create_tables($table_name = '')
    {
        foreach (\Wyvern_config::mysql_table_data() as $table => $data) {
            if ($table_name != '' && $table != $table_name) {
                continue;
            }

            //table exists?
            if (!ee()->db->table_exists(WYVERN_MAP . '_' . $table)) {
                //add the fields
                if (isset($data['fields'])) {
                    ee()->dbforge->add_field($data['fields']);
                }

                //add the keys
                if (isset($data['keys'])) {
                    foreach ($data['keys'] as $key) {
                        $primary = isset($key[1]) && $key[1] ? true : false;
                        ee()->dbforge->add_key($key[0], $primary);
                    }
                }

                ee()->dbforge->create_table(WYVERN_MAP . '_' . $table, true);
            }
        }
    }

    // ----------------------------------------------------------------

    /**
     * Install a hook for the extension
     *
     * @param $hook
     * @param null $method
     * @param int $priority
     * @return bool TRUE
     */
    public function register_hook($hook, $method = null, $priority = 10)
    {
        if (is_null($method)) {
            $method = $hook;
        }

        if (ee()->db->where('class', WYVERN_CLASS . '_ext')
                ->where('hook', $hook)
                ->count_all_results('extensions') == 0) {
            ee()->db->insert(
                'extensions',
                array(
                    'class' => WYVERN_CLASS . '_ext',
                    'method' => $method,
                    'hook' => $hook,
                    'settings' => '',
                    'priority' => $priority,
                    'version' => WYVERN_VERSION,
                    'enabled' => 'y'
                )
            );
        }
    }

    // ----------------------------------------------------------------

    /**
     * Create a action
     *
     * @param $method
     * @param int $csrf_exempt
     * @return bool TRUE
     */
    public function register_action($method, $csrf_exempt = 0)
    {
        if (ee()->db->where('class', WYVERN_CLASS)
                ->where('method', $method)
                ->count_all_results('actions') == 0) {
            ee()->db->insert(
                'actions',
                array(
                    'class' => WYVERN_CLASS,
                    'method' => $method,
                    'csrf_exempt' => $csrf_exempt
                )
            );
        }
    }


    // ----------------------------------------------------------------

    /**
     * Run a update from a file
     *
     * @param $version
     * @param string $data
     * @return bool TRUE
     */

    public function init_update($version, $data = '')
    {
        // run the update file
        $class_name = WYVERN_CLASS . '_upd_' . str_replace('.', '', $version);
        require_once(PATH_THIRD . WYVERN_MAP . '/installer/updates/' . strtolower($class_name) . '.php');
        $updater = new $class_name($data);
        return $updater->run_update();
    }


} // END CLASS
