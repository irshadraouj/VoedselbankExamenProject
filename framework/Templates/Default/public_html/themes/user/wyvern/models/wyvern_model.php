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

class Wyvern_model
{

    /**
     * Get all templates as selected array
     *
     * @param array $template_ids
     * @param array $group_ids
     * @return array
     */
    public function get_templates($template_ids = array(), $group_ids = array())
    {
        // we are using raw sql as fetching template
        // using the native models we are facing with also file sync that trigger a lot of queries

        $template_list = array();

        $template_groups_query = ee()->db->select('group_id, group_name')->from('template_groups');

        if (!empty($template_ids)) {
            $template_groups_query->where('template_id', 'IN', $template_ids);
        }

        if (!empty($group_ids)) {
            $template_groups_query->where('group_id', 'IN', $group_ids);
        }

        $template_groups = $template_groups_query->get();

        if ($template_groups->num_rows() > 0) {
            foreach ($template_groups->result() as $group) {
                $template_query = ee()->db
                    ->select('template_name, template_id')
                    ->from('templates')
                    ->where('group_id', $group->group_id)
                    ->get();

                if ($template_query->num_rows()) {
                    foreach ($template_query->result() as $template) {
                        $template_name = $template->template_name !== 'index' ? '/' . $template->template_name : '';
                        $template_list[$template->template_id] = $group->group_name . $template_name;
                    }
                }
            }
        }

        return $template_list;
    }

    //------------------------------------------------------------

    /**
     * Get all templates as selected array
     *
     * @return array
     */
    public function get_template_groups()
    {
        $group_list = array();

        $groups = ee('Model')->get('TemplateGroup')->all();

        if ($groups->count()) {
            foreach ($groups as $group) {
                $group_list[$group->group_id] = $group->group_name;
            }
        }

        return $group_list;
    }

    //------------------------------------------------------------

    /**
     * Create a list of templates bases on the post values of the settings
     * @return array
     */
    public function get_posted_templates()
    {
        $selected_templates = array();

        if ($_POST['linkable_dialog_template_type'] == 'show_all') {
            $selected_templates = $this->get_templates();
        } else {
            if ($_POST['linkable_dialog_template_type'] == 'show_group') {
                $selected_templates = $this->get_templates(array(), $_POST['linkable_dialog_template_groups']);
            } else {
                if ($_POST['linkable_dialog_template_type'] == 'show_templates') {
                    $selected_templates = $this->get_templates($_POST['linkable_dialog_template_templates']);
                }
            }
        }

        return $selected_templates;
    }

    // ----------------------------------------------------------------------

    /**
     * Get all membergroups
     *
     * @access public
     */
    public function get_membergroups()
    {
        $return = array();

        if (ee(WYVERN_MAP . ':Version')->isGteEE6()) {
            $roles = ee('Model')
                ->get('Role')
                ->filter('role_id', '!=', 2)
                ->filter('role_id', '!=', 3)
                ->filter('role_id', '!=', 4);
            foreach ($roles->all() as $role) {
                $return[$role->role_id] = $role->name;
            }
        } else {
            $groups = ee('Model')
                ->get('MemberGroup')
                ->filter('group_id', '!=', 2)
                ->filter('group_id', '!=', 3)
                ->filter('group_id', '!=', 4);
            foreach ($groups->all() as $group) {
                $return[$group->group_id] = $group->group_title;
            }
        }

        return $return;
    }

    // ----------------------------------------------------------------------

    /**
     * Get all membergroups
     *
     * @access public
     * @param bool $excludeEmpty
     * @return array
     */
    public function get_toolbars($excludeEmpty = true)
    {
        $return = array();

        $groups = ee('Model')->get('wyvern:Toolbar');

        if ($excludeEmpty) {
            $groups->filter('toolbar_name', '!=', 'empty');
        }

        foreach ($groups->all() as $group) {
            $return[$group->toolbar_id] = $group->toolbar_name;
        }

        return $return;
    }

    public function getUploadPrefs()
    {
        $return = array(
            'all' => lang('all')
        );

        $groups = ee('Model')->get('UploadDestination')->filter('module_id', 0);
        foreach ($groups->all() as $group) {
            $return[$group->id] = $group->name;
        }

        return $return;
    }


} // END CLASS
