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

class Wyvern_lib
{
    public $act_url;
    public $act;

    // Used to encode emails within linked text created by the pattern below.
    public $email_pattern = "([a-z0-9!#\$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#\$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)";
    // Used to find and linked email addresses, linked or not.
    // Special thanks to regex ninja Adrienne Travis (@adrienneleigh) for figuring this out for me
    public $full_pattern = "/(?P<preaddr>\<a(?:[a-z0-9;%&'\s\x22?~+_=-]+?)href\=[\x22|']mailto\:)?(?P<addr>[a-z0-9!#\$%&'*+=?^_`{|}~\-]+(?:\.[a-z0-9!#\$%&'*+=?^_`{|}~\-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+(?:museum|[a-z]{2,4}))(?P<postaddr>(?:[a-z0-9%&'\s\x22;?~+_=-]+?)(?=\>)\>)?(?P<linktext>(?:.*?)(?=\<))(?P<linkend>\<\s?\/a\s?\>)?/ism";

    public function __construct()
    {
        //get the action id
        $this->act = wyvern_helper::fetch_action_id(WYVERN_CLASS, 'act_route');
        $this->act_url = ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . $this->act;

        //load lang file
        ee()->lang->loadfile(WYVERN_MAP);

        //load model
        ee()->load->model(WYVERN_MAP . '_model');

        //load the settings
        ee()->load->library(WYVERN_MAP . '_settings');

        //load logger
        ee()->load->library('logger');
    }

    // ----------------------------------------------------------------------
    // CUSTOM FUNCTIONS
    // ----------------------------------------------------------------------

    public function generateFieldsettingToolbarTable($options, $prefix)
    {
        //get all toolbar
        $toolbars = ee()->wyvern_model->get_toolbars();

        //get all members
        $member_groups = ee()->wyvern_model->get_membergroups();
        $table = '<table cellspacing="0" width="100%">
			<thead>
				<tr>
					<th>Membergroup</th>
					<th>Toolbar</th>
				</tr>
			</thead>
			<tbody>
			';

        foreach ($member_groups as $id => $member_group) {
            $value = isset($options['value'][$id]) ? $options['value'][$id] : '';

            $table .= '<tr>
					<td>' . $member_group . '</td>
					<td>
					' . form_dropdown($prefix . $options['name'] . '[' . $id . ']', $toolbars, $value) . '
                    </td>
				</tr>';
        }


        $table .= '</tbody>
		</table>
		';
        return $table;
    }

    // ----------------------------------------------------------------------

    public function load_fp_assets($field_id, $upload_dir = 'all')
    {
        if (ee()->wyvern_settings->item('file_manager') == 'default') {
            return $this->load_ee_fp($field_id, $upload_dir);
        } else {
            if (ee()->wyvern_settings->item('file_manager') == 'assets') {
                return $this->load_assets_fp($field_id, $upload_dir);
            }
        }
    }

    // ----------------------------------------------------------------------

    private function load_ee_fp($field_id, $upload_dir)
    {
        $picker = ee('CP/FilePicker')->make($upload_dir);

        $link = $picker->getLink('Click me!');
        $link->addAttributes(
            array(
                'id' => 'wyvern_ee_fp_' . $field_id . '_' . $upload_dir
            )
        );
        return "<div style='display:none'>" . $link->render() . "</div>";
    }

    // ----------------------------------------------------------------------

    private function load_assets_fp($field_id, $upload_dir)
    {
        if (!class_exists('Assets_helper')) {
            require PATH_THIRD . 'assets/helper.php';
        }

        $assets_helper = new Assets_helper;
        $assets_helper->include_sheet_resources();
    }

    /**
     * Get the extra config, from the settings
     *
     * @return array
     */
    public function getExtraConfig()
    {
        $new_config = array();
        $extra_config = explode("\n", ee()->wyvern_settings->item('extra_config'));
        if (!empty($extra_config)) {
            foreach ($extra_config as $val) {
                $config_line = explode(":", $val);
                if (isset($config_line[0]) && isset($config_line[1])) {
                    $new_value = trim($config_line[1]);

                    //parse {site_url} or {base_url}
                    $new_value = str_replace('{site_url}', ee()->config->item('site_url'), $new_value);
                    $new_value = str_replace('{base_url}', ee()->config->item('base_url'), $new_value);

                    if (is_numeric($new_value)) {
                        $new_value = (int)$new_value;
                    } else {
                        if ($new_value == 'true' || $new_value == 'false') {
                            $new_value = (bool)$new_value;
                        }
                    }

                    $new_config[trim($config_line[0])] = $new_value;
                }
            }
        }

        return $new_config;
    }

    // ----------------------------------------------------------------------

    public function set_global_js_config()
    {
        return "
            REINOS_WYVERN.setConfig({
                customPlugins: " . json_encode($this->get_ckeditor_plugins()) . ",
                version: '" . WYVERN_VERSION . "',
                themesUrl: '" . URL_THIRD_THEMES . "wyvern/',
                ajaxUrl: '" . ee()->wyvern_settings->item('site_url') . '?ACT=' . ee()->wyvern_lib->act . "',
                filemanager: '" . ee()->wyvern_settings->item('file_manager') . "',
                defaultLinkType: '" . ee()->wyvern_settings->item('default_link_type') . "',
                displayBlock: '" . ee()->wyvern_settings->item('display_block') . "',
                contentsCss: '" . reduce_double_slashes(
                ee()->config->item('base_url') . ee()->wyvern_settings->item('css_path')
            ) . "?rev=" . time() . "',
                contentsJs: '" . reduce_double_slashes(
                ee()->config->item('base_url') . ee()->wyvern_settings->item('js_path')
            ) . "?rev=" . time() . "',
                linkableDialogTemplates: " . json_encode(ee()->wyvern_settings->item('linkable_dialog_templates')) . ",
                extraCkeditorConfig: " . json_encode($this->getExtraConfig()) . ", 
            })
        ";
    }

    // ----------------------------------------------------------------------

    /**
     * set the config per field
     *
     * this should be called just one time
     *
     * @param $field_name
     * @param $settings
     * @return string
     */
    public function set_field_js_config($ee_field_id, $field_name, $field_id, $settings, $field_type = 'default')
    {
        $config = array();

        $config['fieldName'] = $field_name;
        $config['isLoggedIn'] = ee()->session->userdata('member_id') != 0;

        $config['filemanagerImages'] = $this->load_fp_assets($field_name, $settings['wyvern_upload_dir_images']);
        $config['filemanagerFiles'] = $this->load_fp_assets($field_name, $settings['wyvern_upload_dir_files']);
//        $config['filemanagerId'] = $field_name;

        $config['fieldType'] = $field_type;
        $config['fieldId'] = $field_id;
        $config['uploadDirImages'] = $settings['wyvern_upload_dir_images'];
        $config['uploadDirFiles'] = $settings['wyvern_upload_dir_files'];
        $config['cssPerField'] = ee()->wyvern_settings->get_setting(
                'site_url'
            ) . '?ACT=' . wyvern_helper::fetch_action_id(
                WYVERN_CLASS,
                'act_route'
            ) . '&method=getPerFieldCss&field_id=' . $ee_field_id;

        $config['resizeEnabled'] = isset($settings['wyvern_resize_enabled']) && $settings['wyvern_resize_enabled'] == 'y';
        $config['height'] = isset($settings['wyvern_display_height']) ? $settings['wyvern_display_height'] : '200px';
        $config['textDirection'] = 'rtl';
        $config['toolbar'] = array();

        $config['mergeCustomCss'] = isset($settings['wyvern_merge_custom_css']) ? $settings['wyvern_merge_custom_css'] == 'y' : true; // true by default

        if (isset($settings['wyvern_auto_grow']) && $settings['wyvern_auto_grow'] == 'y') {
            $config['autoGrow'] = true;
            $config['autoGrow_onStartup'] = isset($settings['wyvern_auto_grow_on_startup']) ? $settings['wyvern_auto_grow_on_startup'] == 'y' : false;
            $config['autoGrow_minHeight'] = 200;
        } else {
            $config['autoGrow'] = false;
        }

        //http://docs.ckeditor.com/#!/guide/dev_enterkey
        switch ($settings['wyvern_enter_mode']) {
            default:
            case 'p':
                $config['enterMode'] = 1;
                break;
            case 'div':
                $config['enterMode'] = 3;
                break;
            case 'br':
                $config['enterMode'] = 2;
                break;
        }

        //get the right toolbar for the right group
        //but for channel form the data sits in a different level
        if (isset($settings['field_settings']['wyvern_toolbar'][ee()->session->userdata('group_id')])) {
            $toolbar_id = $settings['field_settings']['wyvern_toolbar'][ee()->session->userdata('group_id')];
        } elseif (isset($settings['wyvern_toolbar'][ee()->session->userdata('group_id')])) {
            $toolbar_id = $settings['wyvern_toolbar'][ee()->session->userdata('group_id')];
        } else {
            // check if the default toolbar exists
            $defaultToolbar = ee('Model')->get('wyvern:Toolbar')
                ->filter('toolbar_id', ee()->wyvern_settings->item('default_toolbar'));

            if ($defaultToolbar->count() > 0) {
                $toolbar_id = ee()->wyvern_settings->item('default_toolbar');
                // in any other case, just empty
            } else {
                $empty_toolbar = ee('Model')->get('wyvern:Toolbar')
                    ->filter('toolbar_name', 'empty')
                    ->first();
                $toolbar_id = $empty_toolbar->toolbar_id;
            }
        }

        $config['toolbar'] = array();
        if (ee('Model')->get('wyvern:Toolbar')->filter('toolbar_id', $toolbar_id)->count()) {
            $toolbar = ee('Model')->get('wyvern:Toolbar')->filter('toolbar_id', $toolbar_id)->first();
            $config['toolbar'] = $this->build_toolbar($toolbar->toolbar_settings);
        }

        return json_encode($config);
    }

    // ----------------------------------------------------------------------

    /**
     * build the toolbar
     *
     * @param $toolbar_raw
     * @return array
     */
    public function build_toolbar($toolbar_raw)
    {
        $toolbar = array();
        $tmp_toolbar = array();

        if (!empty($toolbar_raw)) {
            foreach ($toolbar_raw as $val) {
                if ($val != '') {
                    //if new line, add the already catched one to the array and reset
                    if ($val == '/') {
                        $toolbar[] = $tmp_toolbar;
                        $toolbar[] = '/';
                        $tmp_toolbar = array();
                    } else {
                        $tmp_toolbar[] = $val;
                    }
                }
            };
        }

        //final add the rest
        $toolbar[] = $tmp_toolbar;

        return $toolbar;
    }

    //----------------------------------------------------------------------

    /**
     * @return array
     */
    private function get_ckeditor_plugins()
    {
        ee()->load->helper('directory');
        $map = directory_map(ee()->wyvern_settings->item('theme_dir') . 'ckeditor_plugins/', 1);

        $plugins = array();

        if (!empty($map)) {
            foreach ($map as $k => $file) {
                //exclude the ignore folder
                if ($file != '_ignore') {
                    $plugins[] = $file;
                }
            }
        }

        return $plugins;
    }

    //---------------------------------------------------------------------------------

    /**
     * @param $str
     * @return mixed
     */
    public function encode_ee_tags($str)
    {
        $str = preg_replace("/\{(\/){0,1}exp:(.+?)\}/", "&#123;\\1exp:\\2&#125;", $str);
        $str = str_replace(array('{exp:', '{/exp'), array('&#123;exp:', '&#123;\exp'), $str);
        $str = preg_replace("/\{embed=(.+?)\}/", "&#123;embed=\\1&#125;", $str);
        $str = preg_replace("/\{redirect=(.+?)\}/", "&#123;redirect=\\1&#125;", $str);

        return $str;
    }

    //---------------------------------------------------------------------------------

    /**
     *
     * Encode all linked or non-linked email addresses. Will work with any attributes
     * in the <a> tag or query string parameters on the address, such as a subject line.
     * @param $matches
     * @return string|void
     */
    public function email_obfuscate($matches)
    {
        // Group names don't work on some servers, so use indexes instead / added ver. 1.2.2
        $email = isset($matches[2]) ? $matches[2] : false;
        $pre_addr = isset($matches[1]) ? $matches[1] : '';
        $post_addr = isset($matches[3]) ? $matches[3] : '';
        $text = isset($matches[4]) ? $matches[4] : $email;
        $end = isset($matches[5]) ? $matches[5] : '';

        if (!$email) {
            return;
        }

        $encoded_string = $this->encode_string($email);
        $text = preg_replace_callback($this->email_pattern, array($this, 'email_obfuscate_text'), $text);

        // Make sure un-linked emails are now linked, but only if the pref is set to yes.
        if (!$pre_addr and !$post_addr and @$this->row['channel_auto_link_urls'] == 'y') {
            $pre_addr = '<a href="mailto:' . $encoded_string . '">';
            $post_addr = '</a>';
        }

        $return = $pre_addr . $encoded_string . $post_addr . $text . $end;

        return $return;
    }

    //---------------------------------------------------------------------------------

    /**
     * @param $matches
     * @return string
     */
    public function email_obfuscate_text($matches)
    {
        return $this->encode_string($matches[0]);
    }

    //---------------------------------------------------------------------------------

    /**
     * @param $str
     * @return string
     */
    public function encode_string($str)
    {
        $mode = "3";
        $encoded_string = "";
        $len = strlen($str);

        for ($i = 0; $i < $len; $i++) {
            if ($mode == 3) {
                $mode = rand(1, 2);
            }
            switch ($mode) {
                case 1: // Decimal code
                    $encoded_string .= "&#" . ord($str[$i]) . ";";
                    break;
                case 2: // Hexadecimal code
                    $encoded_string .= "&#x" . dechex(ord($str[$i])) . ";";
                    break;
                default:
            }
        }

        return $encoded_string;
    }

    //---------------------------------------------------------------------------------

    /**
     * @param $matches
     * @return mixed
     */
    public function encode_brackets($matches)
    {
        return str_replace(array("{", "}"), array("&#123;", "&#125;"), $matches[0]);
    }

    //---------------------------------------------------------------------------------

    /**
     * @param $matches
     * @return mixed
     */
    public function decode_brackets($matches)
    {
        return str_replace(array("&#123;", "&#125;"), array("{", "}"), $matches[0]);
    }

    //---------------------------------------------------------------------------------

    /**
     * @param string $data
     * @param string $action
     * @return mixed|string
     */
    public function prep_data($data = '', $action = 'save')
    {
        ee()->load->library('file_field');
        switch ($action) {
            case 'save' :
                // On save, all paths found that are saved as an upload path are replaced with it's {filedir_N} equivelent
                $dirs = ee('Model')->get('UploadDestination')->all();
                $dirsArray = $dirs->toArray();

                // sort the url where we should have the longest first to avoid
                // that the replace will replace a partial url
                usort($dirsArray, function($a, $b) {
                    if (strlen($a['url']) == strlen($b['url'])) {
                        return 0;
                    }
                    return (strlen($a['url']) < strlen($b['url'])) ? 1 : -1;
                });

                foreach ($dirsArray as $key => $value) {
                    $data = str_replace($value['url'], '{filedir_' . $value['id'] . '}', $data);
                }

                // EECMS 7.x introduced a new way for the filemaner to save the files
                // instead of {filedir_N}name.jpg it should be {file:N:url}
                // this way EE can track the file for usage
                // however, for us its hard to find/parse the file directly this way
                // thats why we parse first the {filedir_N} and then we search for this marker
                // and construct the correct tag
                if(ee(WYVERN_MAP . ':Version')->isGteEE7()) {
                    if (preg_match_all(
                        "/" . LD . "filedir_(.*?)\"/",
                        $data,
                        $matches
                    ) != 0) {
                        foreach($matches[1] as $key => $value) {
                            $test = explode(RD, $value);
                            $file = ee()->file_field->get_file($test[1], $test[0]);
                            $file_ref = '{file:'.$file['file_id'].':url}"'; // add the extra (") as we did replace that in the preg_replace
                            $data = str_replace($matches[0][$key], $file_ref, $data);
                        } 
                    }
                }
                
                //parse all urls to {base_url}
                $data = str_replace(ee()->config->item('base_url'), '{base_url}', $data);

                //set the urls to {page_url:<entry_id>}
                $data = $this->create_page_url($data);

                break;

            case 'display':
                // In EE7.0 there is a new way of parsing files
                if(method_exists(ee()->file_field, 'parse_string')) {
                    $data = ee()->file_field->parse_string($data);
                } else { // fallback
                    // all paths found that are saved as an upload path are replaced with it's {filedir_N} equivelent
                    $dirs = ee('Model')->get('UploadDestination')->all();
                    foreach ($dirs->toArray() as $key => $value) {
                        $data = str_replace('{filedir_' . $value['id'] . '}', $value['url'], $data);
                    }
                }
                
                break;

            case 'replace':
                // In EE7.0 there is a new way of parsing files
                if(method_exists(ee()->file_field, 'parse_string')) {
                    $data = ee()->file_field->parse_string($data);
                } else { // fallback
                    // all paths found that are saved as an upload path are replaced with it's {filedir_N} equivelent
                    $dirs = ee('Model')->get('UploadDestination')->all();
                    foreach ($dirs->toArray() as $key => $value) {
                        $data = str_replace('{filedir_' . $value['id'] . '}', $value['url'], $data);
                    }
                }

                //parse all {base_url}  to urls
                $data = str_replace('{base_url}', ee()->config->item('base_url'), $data);

                // Obfuscate email addresses if requested
                if (ee()->wyvern_settings->item('obfuscate_email')) {
                    $data = preg_replace_callback($this->full_pattern, array($this, 'email_obfuscate'), $data);
                }

                //encode EE tags
                $data = $this->encode_ee_tags($data);
                break;
        }

        return $data;
    }

    //---------------------------------------------------------------------------------

    /**
     * Let users decide if URLs and images are allowed. Mostly useful for Low Variables
     *
     * @param array|bool $settings
     * @return array
     */
    public function get_typography($settings = array())
    {
        // Default to yes unless set to otherwise
        $typography_settings = array(
            'auto_links' => 'n',
            'allow_img_url' => 'n'
        );

        if (isset($settings['wyvern_auto_link_urls']) and $settings['wyvern_auto_link_urls'] == 'y') {
            $typography_settings['auto_links'] = 'y';
        }

        if (isset($settings['wyvern_allow_img_urls']) and $settings['wyvern_allow_img_urls'] == 'y') {
            $typography_settings['allow_img_url'] = 'y';
        }

        return $typography_settings;
    }

    //---------------------------------------------------------------------------------

    /**
     * Get the pages data
     *
     * @param bool $key_is_entry_id
     * @param bool $url_is_id
     * @return array
     */
    public function get_pages($key_is_entry_id = false, $url_is_id = false, $with_url_as_value = false)
    {
        $uris = array();

        $structure = ee('Addon')->get('structure');
        $pages = ee('Addon')->get('pages');

        if (!is_null($structure) && $structure->isInstalled()) {
            if (file_exists(PATH_ADDONS . 'structure/sql.structure.php')) {
                require_once PATH_ADDONS . 'structure/sql.structure.php';
            } else {
                require_once PATH_THIRD . 'structure/sql.structure.php';
            }
            $sql = new Sql_structure();
            $site_pages = $sql->get_site_pages();
        } //default pages module
        else {
            if (!is_null($pages) && $pages->isInstalled()) {
                $blank_pages = array(
                    'url' => '',
                    'uris' => array(),
                    'templates' => array()
                );

                $sql = "SELECT site_pages FROM exp_sites WHERE site_id = " . ee()->config->item('site_id');
                $pages_array = ee()->db->query($sql)->row_array();
                $all_pages = unserialize(base64_decode($pages_array['site_pages']));

                if (is_array($all_pages) && isset($all_pages[ee()->config->item('site_id')]) && is_array(
                        $all_pages[ee()->config->item('site_id')]
                    )) {
                    $site_pages = array_merge($blank_pages, $all_pages[ee()->config->item('site_id')]);
                } else {
                    $site_pages = $blank_pages;
                }
            }
        }

        if ((!is_null($structure) && $structure->isInstalled()) || (!is_null($pages) && $pages->isInstalled())) {
            $uris = array(
                array('', ''),
                array('Root', $url_is_id === false ? $site_pages['url'] : '{page_url:0}')
            );

            foreach ($site_pages['uris'] as $key => $uri) {
                if ($site_pages['uris'][$key] !== '/') {
                    //clean url
                    $url = rtrim($uri, '/');

                    //set the url value
                    $url_val = $url_is_id === false ? rtrim($site_pages['url'], '/') . rtrim(
                            $uri,
                            '/'
                        ) : '{page_url:' . $key . '}';

                    // clean url
                    if ($with_url_as_value) {
                        //get the url title
                        $entry = ee('Model')->get('ChannelEntry')->filter('entry_id', $key);
                        $entry_title = '';
                        if ($entry->count() > 0) {
                            $entry_title = $entry->first()->title;
                        }

                        $number_of_segment = count(explode('/', $url));
                        $spaces = str_repeat('--', $number_of_segment);

                        //return data
                        $data = array($spaces . ' ' . $entry_title, $url_val);
                    } else {
                        //return data
                        $data = array(rtrim($uri, '/'), $url_val);
                    }


                    if ($key_is_entry_id) {
                        $uris[$key] = $data;
                    } else {
                        $uris[] = $data;
                    }
                }
            }
        }

        return $uris;
    }

    //---------------------------------------------------------------------------------

    /**
     * Encode urls to {page_url:<entry_id>}
     *
     * @param $data
     * @return mixed
     */
    public function create_page_url($data)
    {
        $site_pages = ee()->wyvern_lib->get_pages(true);
        if (!empty($site_pages)) {
            foreach ($site_pages as $key => $val) {
                if ($val[1] != '') {
                    $regex_url = str_replace('/', "\/", $val[1]);
                    $regex = '/<a href="(' . $regex_url . ')">/';

                    if (preg_match_all($regex, $data, $matches)) {
                        $new_a_tag = str_replace($matches[1][0], '{page_url:' . $key . '}', $matches[0][0]);
                        $data = str_replace($matches[0][0], $new_a_tag, $data);
                    }
                }
            }
        }

        return $data;
    }

    //---------------------------------------------------------------------------------

    /**
     * Decode urls from {page_url:<entry_id>} to a real url
     *
     * @param $data
     * @return mixed
     */
//    public function decode_page_url($data)
//    {
//        $site_pages = ee()->wyvern_lib->get_pages(true);
//        if(!empty($site_pages))
//        {
//            foreach($site_pages as $key=>$val)
//            {
//                if($val[1] != '')
//                {
//                    $data = str_replace('{page_url:' . $key . '}', $val[1], $data);
//                }
//            }
//        }
//
//        return $data;
//    }

    //---------------------------------------------------------------------------------

    /**
     * Decode urls from {page_url:<entry_id>} to a real url
     *
     * @return mixed
     */
    public function create_gb_page_urls()
    {
        $site_pages = ee()->wyvern_lib->get_pages(true);

        if (!empty($site_pages)) {
            foreach ($site_pages as $key => $val) {
                if ($val[1] == '') {
                    $val[0] = 'Root';
                    $val[1] = '{base_url}/' . ee()->config->item('site_index') . '/';
                }

                ee()->config->_global_vars['page_url:' . $key] = reduce_double_slashes($val[1]);
            }
        }
    }

    //---------------------------------------------------------------------------------

    /**
     * Decode urls from {template_url:<template_id>} to a real url
     *
     * @return mixed
     */
    public function create_gb_template_urls()
    {
        // used cached list
        $urls = ee()->wyvern_settings->item('templates');

        // fallback
        if (!$urls || empty($urls)) {
            $urls = ee()->wyvern_model->get_templates();
        }

        if (!empty($urls)) {
            foreach ($urls as $key => $val) {
                ee()->config->_global_vars['template_url:' . $key] = '{base_url}' . ee()->config->item(
                        'site_index'
                    ) . '/' . $val;
            }
        }
    }

} // END CLASS
