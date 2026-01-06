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

use EllisLab\ExpressionEngine\Library\CP\Table;

/**
 * Include the config file
 */
require_once PATH_THIRD . 'wyvern/config.php';

class Wyvern_mcp
{

    public $return_data;
    public $settings;

    private $show_per_page = 25;
    private $base_url;
    private $error_msg;

    /**
     * Constructor
     */
    public function __construct()
    {
        //load the library`s
        ee()->load->library(WYVERN_MAP . '_lib');

        $this->base_url = ee('CP/URL', 'addons/settings/' . WYVERN_MAP);

        //add cp css
        wyvern_helper::mcp_meta_parser('css_custom_path', ee()->wyvern_lib->act_url . '&method=cp_css');

        //if there is no valid license, redirect to the license page
        if (!ee(WYVERN_MAP . ':License')->hasValidLicense() && ee()->uri->segment(5) != 'license') {
            ee()->functions->redirect(ee('CP/URL', 'cp/addons/settings/' . WYVERN_MAP . '/license/'));
        }
    }


    // ----------------------------------------------------------------

    /**
     * Index Function
     *
     * @return    void
     */
    public function index()
    {
        ///show error if needed
        if ($this->error_msg != '') {
            return $this->error_msg;
        }

        //load the view
        return $this->settings();
    }

    // ----------------------------------------------------------------

    /**
     * Overview Function
     *
     * @return    void
     */
    public function toolbar($id = 0, $clone = null)
    {
        //no id?
        if ($id == 0) {
            $toolbar = ee('Model')->make('wyvern:Toolbar');
            $toolbar->toolbar_name = '';
            $toolbar->toolbar_settings = array();
        } else {
            //get the submission data
            $toolbar = ee('Model')->get('wyvern:Toolbar')->filter('toolbar_id', $id)->first();

            if ($clone === 'clone') {
                $clone = ee('Model')->make('wyvern:Toolbar')->set(
                    [
                        'site_id' => $toolbar->site_id,
                        'toolbar_name' => 'COPY - ' . $toolbar->toolbar_name,
                        'toolbar_settings' => $toolbar->toolbar_settings,
                    ]
                );
                $clone->save();

                ee('CP/Alert')->makeInline(WYVERN_MAP . '_notice')
                    ->asSuccess()
                    ->withTitle(lang('success'))
                    ->addToBody('Toolbar cloned')
                    ->defer();

                ee()->functions->redirect(ee('CP/URL', 'cp/addons/settings/' . WYVERN_MAP . '/toolbars/'));
            }
        }

        //is there some data tot save?
        if (isset($_POST) && !empty($_POST)) {
            //validate the form
            $formValidationResult = ee('Validation')
                ->make(
                    array(
                        'toolbar_name' => 'required',
                    )
                )
                ->validate($_POST);

            //assign to vars
            $vars['errors'] = $formValidationResult;


            //save only when it is valid
            if ($formValidationResult->isValid()) {
                //save
                $toolbar->toolbar_settings = isset($_POST['toolbar_preset']) ? explode(
                    ':',
                    ee()->input->post(
                        'toolbar_preset'
                    )
                ) : [];
                $toolbar->toolbar_name = ee()->input->post('toolbar_name');
                $toolbar->site_id = ee()->config->item('site_id');
                $toolbar->save();

                ee('CP/Alert')->makeInline(WYVERN_MAP . '_notice')
                    ->asSuccess()
                    ->withTitle(lang('success'))
                    ->addToBody(ee()->lang->line('preferences_updated'))
                    ->defer();

                ee()->functions->redirect(ee('CP/URL', 'cp/addons/settings/' . WYVERN_MAP . '/toolbars/'));
            }
        }

        $icons = [];
        foreach (\Wyvern_config::toolbar()->buttons as $key => $val) {
            $icon = $val[1] != '' ? $val[1] : strtolower($key);
            $icons[] = '<option value="' . $key . ':' . $icon . '">' . $val[0] . '</option>';
        }

        $selected = [];
        if (!empty($toolbar->toolbar_settings)) {
            foreach ($toolbar->toolbar_settings as $val) {
                if (isset(\Wyvern_config::toolbar()->buttons[$val])) {
                    $button = \Wyvern_config::toolbar()->buttons[$val];
                    $selected[] = [
                        $button[1] != '' ? $button[1] : strtolower($val),
                        $val,
                    ];
                }
            }
        }

        $toolbarConfigurator = ee('View')->make(WYVERN_MAP . ':toolbar')->render(
            [
                'selected' => $selected
            ]
        );


        //set the settings form
        $vars['sections'] = array(
            array(
                array(
                    'title' => WYVERN_MAP . '_toolbar_name',
                    'fields' => array(
                        'toolbar_name' => array(
                            'type' => 'text',
                            'value' => $toolbar->toolbar_name,
                            'required' => true
                        )
                    )
                ),
                array(
                    'title' => WYVERN_MAP . '_toolbar_buttons',
                    'desc' => WYVERN_MAP . '_toolbar_buttons_desc',
                    'wide' => true,
                    'grid' => true,
                    'fields' => array(
                        'toolbar_buttons' => array(
                            'type' => 'html',
                            'content' => '<textarea name="toolbar_preset" style="display: none;" id="settingsEditor"></textarea>' . $toolbarConfigurator . '<br><select id="choose-button">' . implode(
                                    '',
                                    $icons
                                ) . '</select><a id="add-button" class="btn action" href="#">Add</a>'
                        )
                    )
                ),
            ),
        );

        // Final view variables we need to render the form
        $vars += array(
            'base_url' => ee('CP/URL', 'cp/addons/settings/' . WYVERN_MAP . '/toolbar/' . $id),
            'cp_page_title' => lang('general_settings'),
            'save_btn_text' => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving',
            'alerts_name' => WYVERN_MAP . '_settings'
        );

        $buildVersion = ee()->wyvern_settings->item('debug_mode') === 'y' ? 'development' : 'production';
        wyvern_helper::mcp_meta_parser(
            'css_custom_path',
            ee()->wyvern_settings->get_setting('theme_url') . 'dist/wyvern.bundle.css'
        );
        wyvern_helper::mcp_meta_parser(
            'css_custom_path',
            ee()->wyvern_settings->get_setting(
                'theme_url'
            ) . 'ckeditor/skins/moono-lisa/editor.css'
        );
        wyvern_helper::mcp_meta_parser(
            'js_custom_path',
            ee()->wyvern_settings->get_setting(
                'theme_url'
            ) . 'dist/wyvern.' . $buildVersion . '.bundle.js'
        );
        wyvern_helper::mcp_meta_parser('js_inline', 'REINOS_WYVERN_PREVIEW.init("' . WYVERN_VERSION . '")');

        return $this->output('form', $vars, 'settings');
    }

    // ----------------------------------------------------------------

    /**
     * Overview Function
     *
     * @return    void
     */
    public function toolbars()
    {
        //delete action
        if (isset($_POST['delete']) && !empty($_POST['delete'])) {
            //do your delete stuff here
            ee('Model')->get('wyvern:Toolbar')->filter('toolbar_id', ee()->input->post('delete'))->delete();

            //set a message
            ee('CP/Alert')->makeInline(WYVERN_MAP . '_notice')
                ->asSuccess()
                ->withTitle(lang('success'))
                ->addToBody('ID #' . ee()->input->post('delete') . " deleted")
                ->defer();

            ee()->functions->redirect(ee('CP/URL', 'cp/addons/settings/' . WYVERN_MAP . '/' . __FUNCTION__));
            exit;
        }

        //--------------------------
        //custom settings
        $title_page = 'Toolbar overview';
        $action_buttons = array(
            WYVERN_MAP . '_add_toolbar' => ee('CP/URL', 'addons/settings/' . WYVERN_MAP . '/toolbar')
        );
        $per_page = 25;
        $sort_col = ee()->input->get('sort_col') ?: 'column_toolbar_id';
        $sort_dir = ee()->input->get('sort_dir') ?: 'desc';
        //end custom settings
        //-------------------------

        // Specify other options
        $table = ee(
            'CP/Table',
            array(
                'sort_col' => $sort_col,
                'sort_dir' => $sort_dir
            )
        );

        //set the columns
        $table->setColumns(
            array(
                'column_toolbar_name',
                'manage' => array(
                    'type' => Table::COL_TOOLBAR
                )
            )
        );

        //set a no result text
        $table->setNoResultsText('No toolbars available');

        //get all data
        $cur_page = ((int)ee()->input->get('page')) ?: 1;
        $offset = ($cur_page - 1) * $per_page; // Offset is 0 indexed

        $results = ee('Model')->get('wyvern:Toolbar')
            ->filter('toolbar_name', '!=', 'empty')
            ->order(str_replace('column_', '', $table->config['sort_col']), $table->config['sort_dir'])
            ->limit($per_page)
            ->offset($offset);

        //format the data
        $data = array();
        $ids = array();
        foreach ($results->all() as $result) {
            //save IDS for the delete confirm dialog
            $ids[] = array(
                'id' => $result->toolbar_id,
                'msg' => 'ID:'
            );

            //set the data
            $data[] = array(
                $result->toolbar_name,
                array(
                    'toolbar_items' => array(
                        'edit' => array(
                            'href' => ee('CP/URL', 'addons/settings/' . WYVERN_MAP . '/toolbar/' . $result->toolbar_id),
                            'title' => lang('edit')
                        ),
                        'duplicate' => array(
                            'href' => ee(
                                'CP/URL',
                                'addons/settings/' . WYVERN_MAP . '/toolbar/' . $result->toolbar_id . '/clone'
                            ),
                            'title' => lang('clone'),
                        ),
                        'remove' => array(
                            'href' => '',
                            'title' => lang('remove'),
                            'rel' => "modal-confirm-" . $result->toolbar_id,
                            'class' => 'm-link'
                        )
                    )
                )
            );
        }

        //set the data
        $table->setData($data);

        // Pass in a base URL to create sorting links
        $base_url = ee('CP/URL', 'addons/settings/' . WYVERN_MAP . '/toolbars');
        $vars['table'] = $table->viewData($base_url);
        $vars['base_url'] = $vars['table']['base_url'];
        $vars['action_url'] = $base_url;
        $vars['ids'] = $ids;

        //create the paging
        $vars['pagination'] = ee('CP/Pagination', ee('Model')->get('wyvern:Toolbar')->count())
            ->perPage($per_page)
            ->currentPage($cur_page)
            ->render($base_url);

        //Set the title
        $vars['title_page'] = $title_page;

        //set the buttons
        $vars['action_buttons'] = $action_buttons;

        return $this->output('overview', $vars, $title_page);
    }

    // ----------------------------------------------------------------

    /**
     * Settings Function
     *
     * @return    void
     */
    public function settings()
    {
        //is there some data tot save?
        if (isset($_POST) && !empty($_POST)) {
            //validate the form
            $formValidationResult = ee('Validation')
                ->make(array())
                ->validate($_POST);

            //assign to vars
            $vars['errors'] = $formValidationResult;

            //save only when it is valid
            if ($formValidationResult->isValid()) {
                //build up the template list of selected templates based on the settings
                $_POST['linkable_dialog_templates'] = ee()->wyvern_model->get_posted_templates();

                //save the settings
                ee()->wyvern_settings->save_post_settings();
            }
        }

        //get the installed modules
        ee()->cp->get_installed_modules();

        //set the options
        $filemanager_options = array(
            'default' => lang('EE File Manager'),
            'assets' => lang(WYVERN_MAP . '_assets'),
        );
//        $default_link_options = array(
//            'site_pages' => lang(WYVERN_MAP.'_site_pages'),
//            'url' => lang(WYVERN_MAP.'_url'),
//            'assets' => lang(WYVERN_MAP.'_assets'),
//            'template' => lang(WYVERN_MAP.'_template'),
//            'anchor' => lang(WYVERN_MAP.'_anchor'),
//            'email' => lang(WYVERN_MAP.'_email'),
//        );

        //remove some options based on the installed module
        if (!isset(ee()->cp->installed_modules['assets'])) {
//            unset($default_link_options['assets']);
            unset($filemanager_options['assets']);
        }

        //set the settings form
        $vars['sections'] = array(
            WYVERN_MAP . '_log_settings' => array(
                array(
                    'title' => WYVERN_MAP . '_log',
                    'desc' => 'What should we log?',
                    'fields' => array(
                        'log_severity' => array(
                            'type' => 'radio',
                            'value' => ee()->wyvern_settings->item('log_severity'),
                            'choices' => array(
                                'errors' => 'only errors',
                                'all' => 'Everything',
                                'none' => 'Nothing',
                            ),
                            'required' => true
                        )
                    )
                ),
            ),
            'wyvern_path_settings' => array(
                array(
                    'title' => WYVERN_MAP . '_css_path',
                    'desc' => WYVERN_MAP . '_css_path_desc',
                    'fields' => array(
                        'css_path' => array(
                            'type' => 'text',
                            'value' => ee()->wyvern_settings->item('css_path'),
                            'required' => true
                        )
                    )
                ),
                array(
                    'title' => WYVERN_MAP . '_js_path',
                    'desc' => WYVERN_MAP . '_js_path_desc',
                    'fields' => array(
                        'js_path' => array(
                            'type' => 'text',
                            'value' => ee()->wyvern_settings->item('js_path'),
                            'required' => true
                        )
                    )
                ),
            ),
//            'wyvern_font_settings' => array(
//                array(
//                    'title' => WYVERN_MAP.'_google_font',
//                    'desc' => WYVERN_MAP.'_google_font_desc',
//                    'fields' => array(
//                        'google_font' => array(
//                            'type' => 'text',
//                            'value' => ee()->wyvern_settings->item('google_font'),
//                            'required' => false
//                        )
//                    )
//                ),
//                array(
//                    'title' => WYVERN_MAP.'_typekit',
//                    'desc' => WYVERN_MAP.'_typekit_desc',
//                    'fields' => array(
//                        'typekit' => array(
//                            'type' => 'text',
//                            'value' => ee()->wyvern_settings->item('typekit'),
//                            'required' => false
//                        )
//                    )
//                ),
//            ),
            'wyvern_parse_settings' => array(
//                array(
//                    'title' => WYVERN_MAP.'_parse_ee_tags',
//                    'desc' => WYVERN_MAP.'_parse_ee_tags_desc',
//                    'fields' => array(
//                        'parse_ee_tags' => array(
//                            'type' => 'inline_radio',
//                            'value' => ee()->wyvern_settings->item('parse_ee_tags'),
//                            'choices' => array(
//                                '1' => 'Yes',
//                                '0' => 'No'
//                            )
//                        )
//                    )
//                ),
                array(
                    'title' => WYVERN_MAP . '_obfuscate_email',
                    'desc' => WYVERN_MAP . '_obfuscate_email_desc',
                    'fields' => array(
                        'obfuscate_email' => array(
                            'type' => 'inline_radio',
                            'value' => ee()->wyvern_settings->item('obfuscate_email'),
                            'choices' => array(
                                '1' => 'Yes',
                                '0' => 'No'
                            )
                        )
                    )
                ),
            ),
            'wyvern_image_settings' => array(
                array(
                    'title' => WYVERN_MAP . '_image_class',
                    'desc' => WYVERN_MAP . '_image_class_desc',
                    'fields' => array(
                        'image_class' => array(
                            'type' => 'text',
                            'value' => ee()->wyvern_settings->item('image_class'),
                            'required' => true
                        )
                    )
                ),
            ),
            'wyvern_ckeditor_settings' => array(
                array(
                    'title' => WYVERN_MAP . '_display_block',
                    'desc' => WYVERN_MAP . '_display_block_desc',
                    'fields' => array(
                        'display_block' => array(
                            'type' => 'yes_no',
                            'value' => ee()->wyvern_settings->item('display_block'),
                            'required' => false,
                        )
                    )
                ),
                array(
                    'title' => WYVERN_MAP . '_extra_config',
                    'desc' => WYVERN_MAP . '_extra_config_desc',
                    'fields' => array(
                        'extra_config' => array(
                            'type' => 'textarea',
                            'value' => ee()->wyvern_settings->item('extra_config'),
                            'required' => false
                        )
                    )
                ),
//
//                array(
//                    'title' => WYVERN_MAP.'_default_link_type',
//                    'desc' => WYVERN_MAP.'_default_link_type_desc',
//                    'fields' => array(
//                        'default_link_type' => array(
//                            'type' => 'select',
//                            'value' => ee()->wyvern_settings->item('default_link_type'),
//                            'choices' => $default_link_options
//                        )
//                    )
//                ),

                array(
                    'title' => WYVERN_MAP . '_file_manager',
                    'desc' => WYVERN_MAP . '_file_manager_desc',
                    'fields' => array(
                        'file_manager' => array(
                            'type' => 'select',
                            'value' => ee()->wyvern_settings->item('file_manager'),
                            'choices' => $filemanager_options
                        )
                    )
                ),

                array(
                    'title' => WYVERN_MAP . '_linkable_dialog_template_type',
                    'desc' => WYVERN_MAP . '_linkable_dialog_template_type_desc',
                    'fields' => array(
                        'linkable_dialog_template_type' => array(
                            'type' => 'select',
                            'value' => ee()->wyvern_settings->item('linkable_dialog_template_type'),
                            'choices' => array(
                                'show_all' => lang(WYVERN_MAP . '_show_all_templates'),
                                'show_group' => lang(WYVERN_MAP . '_select_template_groups'),
                                'show_templates' => lang(WYVERN_MAP . '_select_templates'),
                            ),
                            'group_toggle' => array(
                                'show_group' => 'template_group_options',
                                'show_templates' => 'template_options'
                            ),
                        )
                    )
                ),

                array(
                    'title' => WYVERN_MAP . '_linkable_dialog_template_groups',
                    'group' => 'template_group_options',
                    'fields' => array(
                        'linkable_dialog_template_groups[]' => array(
                            'attrs' => 'multiple="multiple"',
                            'type' => 'select',
                            'value' => ee()->wyvern_settings->item('linkable_dialog_template_groups'),
                            'choices' => ee()->wyvern_model->get_template_groups()
                        )
                    )
                ),

                array(
                    'title' => WYVERN_MAP . '_linkable_dialog_template_templates',
                    'group' => 'template_options',
                    'fields' => array(
                        'linkable_dialog_template_templates[]' => array(
                            'attrs' => 'multiple="multiple"',
                            'type' => 'select',
                            'value' => ee()->wyvern_settings->item('linkable_dialog_template_templates'),
                            'choices' => ee()->wyvern_model->get_templates()
                        )
                    )
                ),
            ),
            'Debug' => array(
                array(
                    'title' => WYVERN_MAP . '_debug_mode',
                    'desc' => 'Enter debug mode to load the unminified JS/CSS version.',
                    'fields' => array(
                        'debug_mode' => array(
                            'type' => 'yes_no',
                            'value' => ee()->wyvern_settings->item('debug_mode'),
                            'required' => false,
                        )
                    )
                ),
            ),

            'Toolbar' => array(
                array(
                    'title' => WYVERN_MAP . '_default_toolbar',
                    'desc' => 'Set a default toolbar that will be used when no toolbar is selected, or when a guest is posting via Channel Form',
                    'fields' => array(
                        'default_toolbar' => array(
                            'type' => 'select',
                            'choices' => ee()->wyvern_model->get_toolbars(false),
                            'value' => ee()->wyvern_settings->item('default_toolbar'),
                            'required' => false,
                        )
                    )
                ),
            ),

        );

        //add group js
        ee()->cp->add_js_script(
            array(
                'file' => array('cp/form_group'),
            )
        );

        // Final view variables we need to render the form
        $vars += array(
            'base_url' => ee('CP/URL', 'cp/addons/settings/' . WYVERN_MAP),
            'cp_page_title' => lang('general_settings'),
            'save_btn_text' => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving',
            'extra_alerts' => array(
                WYVERN_MAP . '_settings'
            )
        );

        return $this->output('form', $vars, 'settings');
    }

    // ----------------------------------------------------------------

    /**
     * License Function
     *
     * @return array
     */
    public function license()
    {
        //redirect to the login page of reinos
        if (isset($_GET['auth']) && ee()->input->get('auth') == 'yes') {
            $ret = str_replace('&auth=yes', '', reduce_double_slashes(wyvern_helper::currentUrl()));
            $data = json_encode(
                array(
                    'return' => $ret,
                    'license_key' => ee()->wyvern_settings->item('license_key'),
                    'module_name' => WYVERN_MAP
                )
            );
            ee()->functions->redirect('https://addons.reinos.nl/auth?data=' . base64_encode($data));
        }

        //login successfull from addons.reinos.nl
        if (isset($_GET['auth']) && (ee()->input->get('auth') == 'success' || ee()->input->get('auth') == 'failed')) {
            //save the member ID
            ee()->wyvern_settings->save_setting('license_reinos_member_id', ee()->input->get('member_id'));
            ee()->wyvern_settings->set_setting('license_reinos_member_id', ee()->input->get('member_id'));

            //check the license with the server
            $license_check = ee(WYVERN_MAP . ':License')->checkLicense(
                ee()->wyvern_settings->item('license_key'),
                ee()->wyvern_settings->item(
                    'license_reinos_member_id'
                )
            );

            //License check okay?
            if ($license_check->success) {
                //set a message
                ee('CP/Alert')->makeInline(WYVERN_MAP . '_settings')
                    ->addToBody(ee()->lang->line('preferences_updated'))
                    ->asSuccess()
                    ->withTitle(lang('success'))
                    ->addToBody($license_check->message)
                    ->now();
            }
        }

        //is there some data tot save?
        if (isset($_POST) && !empty($_POST)) {
            //validate the form
            $formValidationResult = ee('Validation')
                ->make(
                    array(
                        'license_key' => 'required'
                    )
                )
                ->validate($_POST);

            //assign to vars
            $vars['errors'] = $formValidationResult;

            //save only when it is valid
            if ($formValidationResult->isValid()) {
                //save the settings
                ee()->wyvern_settings->save_post_settings('/license&auth=yes');
            }
        }

        //License not correct?
        if (!ee(WYVERN_MAP . ':License')->hasValidLicense()) {
            //set a message
            ee('CP/Alert')->makeInline(WYVERN_MAP . '_license')
                ->asIssue()
                ->withTitle(lang('error'))
                ->addToBody('You have an incorrect license. Enter a valid license in order to activate the addon.')
                ->now();
        }

        //set the settings form
        $vars['sections'] = array(
            array(
                array(
                    'title' => WYVERN_MAP . '_license_key',
                    'desc' => 'Your license key',
                    'fields' => array(
                        'license_key' => array(
                            'type' => 'text',
                            'value' => ee()->wyvern_settings->item('license_key'),
                            'required' => true
                        )
                    )
                ),
                array(
                    'title' => '',
                    'fields' => array(
                        'info' => array(
                            'type' => 'html',
                            'content' => '
                                <div class="reinos-license_status">
                                    <div class="reinos-license-info" id="' . WYVERN_MAP . '_license_status">
                                        <span class="st-closed invalid_license" style="display: none;">Invalid license</span>
                                        <span class="st-closed unlicensed" style="display: none;">Unlicensed</span>
                                        <span class="st-info valid_license" style="display: none;">Valid license</span>
                                    </div>
                                   
                                    <h4>License info</h4>
                                    <ul>
                                        <li>Every valid license will work on every local development <i>(*.local, *.test, *.dev or *.localhost)</i>. </li>
                                        <li>
                                            In order to let your license work on your domain, <strong>you have to set the production url</strong> and/or test url. This can be done in you account on
                                            <a href="http://addons.reinos.nl/profile/licenses" target="_blank">addons.reinos.nl</a>.
                                        </li>
                                        <li>
                                            If you run a MSM site, you have to enter all your MSM sites in your account on <a href="http://addons.reinos.nl/profile/licenses" target="_blank">addons.reinos.nl</a>.
                                        </li>
                                        <li>
                                            Running with an invalid license, will <strong>cease the addon to work</strong>.
                                        </li>
                                        <li>If you bought a license in the EE store, it will transferred also to your account on <a href="http://addons.reinos.nl/profile/licenses" target="_blank">addons.reinos.nl</a></li>
                                        <li>By using this software, you agree to the <a href="https://addons.reinos.nl/commercial-license" target="_blank">Add-on License Agreement.</a></li>
                                    </ul>
                                </div>
                                '
                        )
                    )
                )
            )
        );

        // Final view variables we need to render the form
        $vars += array(
            'base_url' => ee('CP/URL', 'cp/addons/settings/' . WYVERN_MAP . '/license'),
            'cp_page_title' => lang(WYVERN_MAP . '_license_settings'),
            'save_btn_text' => 'save and auth license',
            'save_btn_text_working' => 'btn_saving',
            'extra_alerts' => array(
                WYVERN_MAP . '_settings',
                WYVERN_MAP . '_license',
            )
        );

        return $this->output('form', $vars, 'settings');
    }

    // ----------------------------------------------------------------

    /**
     * Overview Function
     *
     * @return    void
     */
    public function logs()
    {
        //delete action
        if (isset($_POST['delete']) && !empty($_POST['delete'])) {
            //do your delete stuff here
            ee('Model')->get(WYVERN_MAP . ':Log')->filter('log_id', ee()->input->post('delete'))->delete();

            //set a message
            ee('CP/Alert')->makeInline(WYVERN_MAP . '_notice')
                ->asSuccess()
                ->withTitle(lang('success'))
                ->addToBody('Log ID #' . ee()->input->post('delete') . " deleted")
                ->defer();

            ee()->functions->redirect(ee('CP/URL', 'cp/addons/settings/' . WYVERN_MAP . '/' . __FUNCTION__));
            exit;
        }

        //--------------------------
        //custom settings
        $title_page = 'Log overview';
        $action_buttons = array(
            WYVERN_MAP . '_delete_all_logs' => ee('CP/URL', 'cp/addons/settings/' . WYVERN_MAP . '/delete_all_logs')
        );
        $per_page = 25;
        $sort_col = ee()->input->get('sort_col') ?: 'column_log_id';
        $sort_dir = ee()->input->get('sort_dir') ?: 'desc';
        //end custom settings
        //-------------------------

        // Specify other options
        $table = ee(
            'CP/Table',
            array(
                'sort_col' => $sort_col,
                'sort_dir' => $sort_dir
            )
        );

        //set the columns
        $table->setColumns(
            array(
                'column_log_id',
                'column_severity',
                'column_time',
                'column_message',
                'manage' => array(
                    'type' => Table::COL_TOOLBAR
                )
            )
        );

        //set a no result text
        $table->setNoResultsText('No logs available');

        //get all data
        $cur_page = ((int)ee()->input->get('page')) ?: 1;
        $offset = ($cur_page - 1) * $per_page; // Offset is 0 indexed

        $results = ee('Model')->get(WYVERN_MAP . ':Log')
            ->order(str_replace('column_', '', $table->config['sort_col']), $table->config['sort_dir'])
            ->limit($per_page)
            ->filter('site_id', ee()->config->item('site_id'))
            ->offset($offset);

        //format the data
        $data = array();
        $ids = array();
        foreach ($results->all() as $result) {
            //save IDS for the delete confirm dialog
            $ids[] = array(
                'id' => $result->log_id,
                'msg' => 'ID:'
            );

            //set the data
            $data[] = array(
                $result->log_id,
                $result->severity,
                ee()->localize->human_time($result->time),
                $result->message,
                array(
                    'toolbar_items' => array(
                        'remove' => array(
                            'href' => '',
                            'title' => lang('remove'),
                            'rel' => "modal-confirm-" . $result->log_id,
                            'class' => 'm-link'
                        )
                    )
                )
            );
        }

        //set the data
        $table->setData($data);

        // Pass in a base URL to create sorting links
        $base_url = ee('CP/URL', 'addons/settings/' . WYVERN_MAP . '/logs');
        $vars['table'] = $table->viewData($base_url);
        $vars['base_url'] = $vars['table']['base_url'];
        $vars['action_url'] = $base_url;
        $vars['ids'] = $ids;

        //create the paging
        $vars['pagination'] = ee('CP/Pagination', $results->count())
            ->perPage($per_page)
            ->currentPage($cur_page)
            ->render($base_url);

        //Set the title
        $vars['title_page'] = $title_page;

        //set the buttons
        $vars['action_buttons'] = $action_buttons;

        return $this->output('overview', $vars, $title_page);
    }

    /**
     * @return array
     */
    public function delete_all_logs()
    {
        //delete member
        if (ee()->input->post('confirm') == 'ok') {
            $result = ee('Model')->get(WYVERN_MAP . ':Log')->all();
            $result->delete();

            //set a message
            ee('CP/Alert')->makeInline(WYVERN_MAP . '_notice')
                ->asSuccess()
                ->withTitle(lang('success'))
                ->addToBody('Logs deleted')
                ->defer();

            ee()->functions->redirect(ee('CP/URL', 'cp/addons/settings/' . WYVERN_MAP . '/logs/'));
        }

        $vars = array();
        $vars['title_page'] = 'Delete all Logs';
        $vars['form_url'] = ee('CP/URL', 'cp/addons/settings/' . WYVERN_MAP . '/delete_all_logs/');

        return $this->output('delete', $vars, $vars['title_page']);
    }

    // ----------------------------------------------------------------

    /**
     * Run a specific update file
     */
    public function update()
    {
        //load the updater class
        ee()->load->library(WYVERN_MAP . '_installer');

        if (in_array(ee()->input->get('version'), \Wyvern_config::updates())) {
            ee()->wyvern_installer->init_update(ee()->input->get('version'));
            die('updater ' . ee()->input->get('version') . ' executed');
        }

        die('Cannot run updater ' . ee()->input->get('version'));
    }

    // ----------------------------------------------------------------

    /**
     * call any act route from the cp
     */
    public function ajax()
    {
        include 'mod.' . WYVERN_MAP . '.php';
        $mod = new wyvern();
        $mod->act_route();
    }

    // ----------------------------------------------------------------

    /**
     * Create acustom url to uninstall just the addon
     */
    public function uninstall()
    {
        require_once PATH_THIRD . 'wyvern/upd.wyvern.php';
        $upd = new Wyvern_upd();
        $upd->uninstall();
        ee()->functions->redirect(ee('CP/URL', 'cp/addons'));
        exit;
    }

    // ----------------------------------------------------------------

    /**
     * Delete Dorm
     *
     * @param int $form_id
     * @return array
     */
//	public function delete_form($id = 0)
//	{
//		//delete member
//		if(ee()->input->post('confirm') == 'ok')
//		{
//			$form = ee('Model')->get('email_form:Form')->filter('form_id', $form_id)->first();
//			$form->delete();
//
//			//set a message
//			ee('CP/Alert')->makeInline(WYVERN_MAP.'_notice')
//				->asSuccess()
//				->withTitle(lang('success'))
//				->addToBody('Form deleted')
//				->defer();
//
//			ee()->functions->redirect(ee('CP/URL', 'cp/addons/settings/'.WYVERN_MAP.'/forms/'));
//		}
//
//		$vars = array();
//		$vars['form_id'] = $id;
//		$vars['title_page'] = 'Delete form';
//
//		return $this->output('delete', $vars, $vars['title_page']);
//	}

    // ----------------------------------------------------------------

    private function output($template, $vars, $heading = '')
    {
        //support for sidebar?
        $sidebar = ee('CP/Sidebar')->make();

        $sidebar->addHeader(
            lang(WYVERN_MAP . '_settings'),
            ee('CP/URL', 'addons/settings/' . WYVERN_MAP . '/settings')
        );
        $sidebar->addHeader(lang(WYVERN_MAP . '_log'), ee('CP/URL', 'addons/settings/' . WYVERN_MAP . '/logs'));
        $sidebar->addHeader(lang(WYVERN_MAP . '_toolbars'), ee('CP/URL', 'addons/settings/' . WYVERN_MAP . '/toolbars'))
            ->withButton(lang('new'), ee('CP/URL', 'addons/settings/' . WYVERN_MAP . '/toolbar'));
        $sidebar->addHeader(lang(WYVERN_MAP . '_license'), ee('CP/URL', 'addons/settings/' . WYVERN_MAP . '/license'));

        return array(
            'body' => ee('View')->make(WYVERN_MAP . ':' . $template)->render($vars),
            'heading' => WYVERN_NAME . ' - ' . lang($heading)
        );
    }

    // ----------------------------------------------------------------

}
