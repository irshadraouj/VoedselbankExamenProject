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

class Wyvern_upd_220
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
        //update the fields settings to add a wyvern_upload_dir_files and wyvern_upload_dir_images with the value
        //of wyvern_upload_dir and the remove wyvern_upload_dir

        //first for the channel fields
        $fields = ee('Model')->get('ChannelField')->filter('field_type', 'wyvern');

        if ($fields->count() > 0) {
            foreach ($fields->all() as $field) {
                $settings = $field->field_settings;

                //set the new fields like
                $new_upload_dir = isset($settings['wyvern_upload_dir']) ? $settings['wyvern_upload_dir'] : 'all';

                //set the new dirs
                $settings['wyvern_upload_dir_files'] = $new_upload_dir;
                $settings['wyvern_upload_dir_images'] = $new_upload_dir;

                //remove the old setting
                if (isset($settings['wyvern_upload_dir'])) {
                    unset($settings['wyvern_upload_dir']);
                }

                //set it again
                $field->field_settings = $settings;

                //save it with the new settings
                $field->save();
            }
        }

        //now for the grid fields
        $q = ee()->db
            ->select('col_settings, col_id')
            ->from('grid_columns')
            ->where('col_type', 'wyvern')
            ->get();

        if ($q->num_rows() > 0) {
            foreach ($q->result() as $row) {
                $setting = json_decode($row->col_settings);

                //set the new fields like
                $new_upload_dir = isset($setting->wyvern_upload_dir) ? $setting->wyvern_upload_dir : 'all';

                //set the new dirs
                $setting->wyvern_upload_dir_files = $new_upload_dir;
                $setting->wyvern_upload_dir_images = $new_upload_dir;

                //remove the old setting
                if (isset($setting->wyvern_upload_dir)) {
                    unset($setting->wyvern_upload_dir);
                }

                //resave it with the new settings
                ee()->db->where('col_id', $row->col_id)->update(
                    'grid_columns',
                    array(
                        'col_settings' => json_encode($setting)
                    )
                );
            }
        }
    }

}
