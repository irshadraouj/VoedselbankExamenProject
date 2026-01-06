<?php
if (!defined('BASEPATH')) {
    die('No direct script access allowed');
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

include(PATH_THIRD . 'wyvern/config.php');


class Wyvern_ft extends EE_Fieldtype
{

    public $info = array(
        'name' => WYVERN_NAME,
        'version' => WYVERN_VERSION,
        'has_global_settings' => 'n'
    );

    public $settings = array();

    public $default_settings = array();

    public $has_array_data = true;

    private $prefix;

    // ----------------------------------------------------------------------

    /**
     * Constructor
     *
     * @access public
     *
     * Calls the parent constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->prefix = WYVERN_MAP . '_';

        //load lang file
        ee()->lang->loadfile(WYVERN_MAP);

        ee()->load->add_package_path(PATH_THIRD . WYVERN_MAP . '/');
        ee()->load->library('wyvern_lib');
    }

    // ----------------------------------------------------------------------
    // Before saving the content to the database
    // ----------------------------------------------------------------------

    /**
     * save (Native EE)
     *
     * @access public
     */
    public function save($data)
    {
        return $this->_save($data);
    }


    // ----------------------------------------------------------------------

    /**
     * entry_api_save (Webservcie)
     *
     * @access public
     */
//    public function webservice_save($data)
//    {
//        return $this->_save($data);
//    }

    // ----------------------------------------------------------------------

    /**
     * save (Low Variables)
     *
     * @access public
     */
//    public function save_var_field($data)
//    {
//        return $this->_save($data);
//    }

    // ----------------------------------------------------------------------

    /**
     * save
     *
     * @access public
     */
    private function _save($data = '')
    {
        if (is_array($data)) {
            $data = implode('|', $data);
        }

        //prep data
        $data = ee()->wyvern_lib->prep_data($data, 'save');

        return $data;
    }

    // ----------------------------------------------------------------------
    // Display the field for all types
    // ----------------------------------------------------------------------

    /**
     * var_display_field (low variables)
     *
     * @access public
     */
    function var_display_field($data)
    {
        //generate the data
        return $this->_display_field($data);
    }

    // ----------------------------------------------------------------------

    /**
     * display_field
     *
     * @access public
     */
    function display_field($data)
    {
        $type = array_key_exists('fluid_field_data_id', $this->settings) ? 'fluid' : 'default';

        //generate the data
        return $this->_display_field($data, $type);
    }

    // ----------------------------------------------------------------------

    /**
     * grid_display_field (Grid)
     *
     * @access public
     */
    function grid_display_field($data)
    {
        //generate the data
        return $this->_display_field($data, 'grid');
    }

    // ----------------------------------------------------------------------

    /**
     * display_field
     *
     * @access public
     */
    private function _display_field($data, $type = 'default')
    {
        if (!ee('wyvern:License')->hasValidLicense()) {
            ee('CP/Alert')->makeBanner('wyvern_license_error')
                ->asIssue()
                ->withTitle(lang('Wyvern License error'))
                ->addToBody(
                    lang(
                        'Your license key appear to be invalid. Please fill in the correct license in the module CP. <b>Also note that the content will not be displayed until you have a valid license entered</b>.'
                    )
                )
                ->now();

            ee('wyvern:Log')->add_log(
                'Your license key appear to be invalid. Please fill in the correct license in the module CP.',
                'error'
            );
        }

        ee()->load->library('wyvern_lib');

        // Trim Data
        if($data != '') {
            $data = trim($data);
        }

        $data = ee()->wyvern_lib->prep_data($data, 'display');

        //fieldname
        switch ($type) {
            case 'default':
            default :
                $field_name = $this->field_name;
                break;
        }

        //just load once
        if (isset(ee()->session->cache[WYVERN_MAP]['assets_added']) == false) {
            $buildVersion = ee()->wyvern_settings->item('debug_mode') === 'y' ? 'development' : 'production';

            wyvern_helper::mcp_meta_parser(
                'css_custom_path',
                ee()->wyvern_settings->get_setting('theme_url') . 'dist/wyvern.bundle.css'
            );
            wyvern_helper::mcp_meta_parser(
                'js_custom_path',
                ee()->wyvern_settings->get_setting(
                    'theme_url'
                ) . 'dist/wyvern.' . $buildVersion . '.bundle.js'
            );
            ee()->cp->add_to_foot(
                '<script src="' . ee()->wyvern_settings->get_setting(
                    'theme_url'
                ) . 'ckeditor/ckeditor.js" type="text/javascript"></script>'
            );
            wyvern_helper::mcp_meta_parser('js_inline', ee()->wyvern_lib->set_global_js_config());

            //load custom styles
            //wyvern_helper::mcp_meta_parser('css_custom_path', ee()->wyvern_settings->item('css_path'));
            //wyvern_helper::mcp_meta_parser('js_custom_path', ee()->wyvern_settings->item('js_path'));

            ee()->session->cache[WYVERN_MAP]['assets_added'] = true;
        }

        //set custom field name for the wyvern fields
        $wyvern_field_name = 'wyvern_field_' . time() . uniqid();

        //marker to execute the wyvern field despite the condition to execute CKEDITOR.replace
        // is used in the wyvern.js
        $replace_always = false;

        $extra_data_elem = array();

        //set data per type of field
        if ($type == 'fluid') {
            //is there any content? do we need to execute wyvern.
            //fluid has no event in JS when exisiting elements are shown on the page
            //https://expressionengine.com/forums/topic/251034/fluid-display-event
            if (!empty($data)) {
                $replace_always = true;
            }

            if (isset(ee()->session->cache[WYVERN_MAP]['fluid_init']) == false) {
                //this add the callbacks for the field
                wyvern_helper::mcp_meta_parser('js_inline', 'REINOS_WYVERN.attachFluidEvents()');

                ee()->session->cache[WYVERN_MAP]['fluid_init'] = true;
            }
        } else {
            if ($type == 'grid') {
                if (isset($this->settings['fluid_field_data_id']) && isset(
                        ee()->session->cache[WYVERN_MAP]['fluid_init']
                    ) == false) {
                    //this add the callbacks for the field
                    wyvern_helper::mcp_meta_parser('js_inline', 'REINOS_WYVERN.attachFluidEvents()');

                    ee()->session->cache[WYVERN_MAP]['fluid_init'] = true;
                }

                if (isset(ee()->session->cache[WYVERN_MAP]['grid_init']) == false) {
                    //this add the callbacks for the field
                    wyvern_helper::mcp_meta_parser('js_inline', 'REINOS_WYVERN.attachGridEvents()');

                    ee()->session->cache[WYVERN_MAP]['grid_init'] = true;
                }
            }
        }

        //format the $replace_always for JS
        $replace_always = $replace_always ? 'true' : 'false';

        //set the id
        $uniqueID = uniqid();

        //set the js setting array
        // wyvern_helper::mcp_meta_parser('js_inline', 'REINOS_WYVERN.setEditorSettings("'.$uniqueID.'", '.ee()->wyvern_lib->set_field_js_config($wyvern_field_name, $uniqueID, $this->settings, $type).');');

        //call the wyvern init function to fire up the editor
        wyvern_helper::mcp_meta_parser(
            'js_inline',
            'REINOS_WYVERN.initEditor("' . $wyvern_field_name . '", ' . ee()->wyvern_lib->set_field_js_config(
                $this->field_id,
                $wyvern_field_name,
                $uniqueID,
                $this->settings,
                $type
            ) . ', ' . $replace_always . ');'
        );

        //this add the callbacks for the field
        wyvern_helper::mcp_meta_parser('js_inline', 'REINOS_WYVERN.attachPreviewEvents()');

        //disable auto inline
        //it was triggering the RTE field :|
        if (isset(ee()->session->cache[WYVERN_MAP]['disableAutoInline']) == false) {
            wyvern_helper::mcp_meta_parser('js_inline', 'CKEDITOR.disableAutoInline = true;');

            ee()->session->cache[WYVERN_MAP]['disableAutoInline'] = true;
        }

        //return data to display
        return form_textarea(
            $field_name,
            $data,
            'data-field-id="' . $uniqueID . '" ' . implode(
                ' ',
                $extra_data_elem
            ) . ' data-type="' . $type . '"  class="wyvern_ckeditor_field" data-id="' . $wyvern_field_name . '" id="' . $wyvern_field_name . '"'
        );
    }

    // ----------------------------------------------------------------------
    // Replace the tags for all types
    // ----------------------------------------------------------------------

    /**
     * display_var_tag (Low variables)
     *
     * @access public
     */
    public function display_var_tag($var_data, $tagparams, $tagdata)
    {
        return $this->replace_tag($var_data, $tagparams, $tagdata);
    }

    // ----------------------------------------------------------------------

    /**
     * replace_tag
     *
     * @access public
     */
    public function replace_tag($data, $params = array(), $tagdata = false)
    {
        $data = trim($data);

        $data = ee()->wyvern_lib->prep_data($data, 'replace');

        //word limit?
        if (isset($params['word_limit'])) {
            $suffix = isset($params['suffix']) ? $params['suffix'] : '&#8230;';
            $data = wyvern_helper::word_limiter($data, (int)$params['word_limit'], $suffix);
        }

        //word limit?
        if (isset($params['character_limit'])) {
            $suffix = isset($params['suffix']) ? $params['suffix'] : '&#8230;';
            $data = ee()->functions->char_limiter($data, (int)$params['character_limit'], $suffix);
        }

        //strip tags?
        if (isset($params['strip_tags']) && wyvern_helper::check_yes($params['strip_tags'])) {
            $params['allowable_tags'] = isset($params['allowable_tags']) ? $params['allowable_tags'] : '';
            $data = strip_tags($data, $params['allowable_tags']);
        }

        ee()->load->library('typography');
        $typography = ee()->wyvern_lib->get_typography($this->settings);

        $typography = array_merge(
            $typography,
            array(
                'text_format' => 'none',
                'html_format' => 'all',
                'auto_links' => @$this->row['channel_auto_link_urls'],
                'allow_img_url' => @$this->row['channel_allow_img_urls']
            )
        );

        if (!ee('LivePreview')->hasEntryData()) {
            $data = ee()->typography->parse_type($data, $typography);
        }

        //add classes to the image
        if (isset($params['image_class'])) {
            $dom = new DOMDocument;
            $dom->loadHTML($data);
            $images = $dom->getElementsByTagName('img');
            foreach ($images as $image) {
                $image->setAttribute('class', $params['image_class'] . ' ' . $image->getAttribute('class'));
            }
            $data = preg_replace('~<(?:!DOCTYPE|/?(?:html|body))[^>]*>\s*~i', '', $dom->saveHTML());
        }

        return $data;
    }

    // ----------------------------------------------------------------------

    /**
     * replace_tag_catchall
     *
     * @access public
     */
//    function replace_tag_catchall($file_info, $params = array(), $tagdata = FALSE, $modifier)
//    {
//
//    }

    // ----------------------------------------------------------------------
    // Display the settings for all types
    // ----------------------------------------------------------------------

    /**
     * Display settings screen (Default EE)
     *
     * @access  public
     */
    function display_settings($data)
    {
        ee()->load->library('wyvern_lib');

        $settings_data = $this->_display_settings($data);

        if ($this->content_type() == 'grid') {
            return array('field_options_' . WYVERN_MAP => $settings_data);
        }

        return array(
            'field_options_' . WYVERN_MAP => array(
                'label' => 'field_options',
                'group' => WYVERN_MAP,
                'settings' => $settings_data
            )
        );
    }


    // --------------------------------------------------------------------

    /**
     * Display settings screen (Low variables)
     *
     * @access  public
     */
    function display_var_settings($data)
    {
        return $this->_display_settings($data, 'low_var');
    }

    // --------------------------------------------------------------------

    /**
     * Display settings screen (EE 2.7 GRID)
     *
     * @access  public
     */
//    function grid_display_settings($data)
//    {
//        return $this->_display_settings($data, array(), 'grid');
//    }

    // --------------------------------------------------------------------

    /**
     * Display settings screen
     *
     * @access  public
     */
    // --------------------------------------------------------------------

    /**
     * Display settings screen
     *
     * @access  public
     */
    private function _display_settings($data, $type = 'native')
    {
        $return = array();

        if (!empty(\Wyvern_config::fieldtype_settings())) {
            foreach (\Wyvern_config::fieldtype_settings() as $field => $options) {
                //set the override value
                $override_value = false;
                if (isset($options['override'])) {
                    $override_value = ee()->wyvern_settings->get_setting($options['override'] . '_override');
                }

                //set some default options value
                $options['def_value'] = isset($options['def_value']) ? $options['def_value'] : '';
                $options['global'] = isset($options['global']) ? $options['global'] : '';

                //is overrided?
                if ($override_value !== false && $override_value !== '') {
                    $options['value'] = $override_value;
                } //default value
                else {
                    $options['value'] = isset($data[$this->prefix . $options['name']]) ? $data[$this->prefix . $options['name']] : $options['def_value'];
                }

                //global?
                if ($options['global']) {
                    continue;
                }


                //check if we need to load dynamic data
                if (isset($options['choices']) && is_string($options['choices'])) {
                    $split = explode('|', $options['choices']);
                    $class = str_replace('class:', '', $split[0]);
                    $method = str_replace('method:', '', $split[1]);

                    $options['choices'] = ee()->{$class}->{$method}($options, $this->prefix);
                }

                if (isset($options['content']) && is_string($options['content'])) {
                    $split = explode('|', $options['content']);
                    $class = str_replace('class:', '', $split[0]);
                    $method = str_replace('method:', '', $split[1]);

                    $options['content'] = ee()->{$class}->{$method}($options, $this->prefix);
                }

                if ($type == 'native') {
                    $return[] = array(
                        'title' => $options['label'],
                        'desc' => isset($options['desc']) ? $options['desc'] : '',
                        'fields' => $this->generateField($options)
                    );
                } else {
                    if ($type == 'low_var') {
                        $options['field_name'] = $this->prefix . $options['name'];
                        $options['grid'] = false;
                        $options['field'] = $this->generatePerField($options);
                        $return[] = array(
                            $this->prefix . $options['name'],
                            ee('View')->make('ee:_shared/form/field')->render($options)
                        );
                    }
                }
            }
        }

        return $return;
    }

    // ----------------------------------------------------------------------
    // Save the settings for all types
    // ----------------------------------------------------------------------

    /**
     * save_settings (Default EE)
     *
     * @access public
     */
    function save_settings($data)
    {
        $settings = $this->_save_settings($data);

        if (\Wyvern_config::field_wide()) {
            $settings['field_wide'] = \Wyvern_config::field_wide();
        }

        return $settings;
    }

    // --------------------------------------------------------------------

    /**
     * save_settings ((Low variables))
     *
     * @access public
     */
    function save_var_settings($data)
    {
        return $this->_save_settings($data);
    }

    // --------------------------------------------------------------------

    /**
     * save_settings (GRID)
     *
     * @access public
     */
    function grid_save_settings($data)
    {
        return $this->_save_settings($data, true);
    }

    // --------------------------------------------------------------------

    /**
     * save_settings
     *
     * @access public
     */
    private function _save_settings($data, $use_data = false)
    {
        $return = array();

        if (!empty(\Wyvern_config::fieldtype_settings())) {
            foreach (\Wyvern_config::fieldtype_settings() as $field => $options) {
                //global?
                if (isset($options['global']) && $options['global']) {
                    continue;
                }

                $return[$this->prefix . $options['name']] = $use_data ? $data[$this->prefix . $options['name']] : ee(
                )->input->post($this->prefix . $options['name']);
            }
        }

        return $return;
    }

    // ----------------------------------------------------------------------

    /**
     * Validates the field input (EE)
     *
     * @access public
     */
    public function validate($data)
    {
        return $this->_validate($data);
    }

    // ----------------------------------------------------------------------

    /**
     * Validates the field input
     *
     * @access public
     */
    public function _validate($data)
    {
        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Do additional processing after the field is created/modified. (EE)
     *
     * @access public
     */
    public function post_save_settings($data)
    {
        $this->_post_save_settings($data);
    }

    // --------------------------------------------------------------------

    /**
     * Do additional processing after the field is created/modified.
     *
     * @access public
     */
    public function _post_save_settings($data)
    {
    }

    // ----------------------------------------------------------------------

    /**
     * install
     *
     * @access public
     */
    function install()
    {
        $return = array();

        if (!empty(\Wyvern_config::fieldtype_settings())) {
            foreach (\Wyvern_config::fieldtype_settings() as $field => $options) {
                $return[$this->prefix . $options['name']] = isset($options['def_value']) ? $options['def_value'] : '';
            }
        }

        return $return;
    }

    // ----------------------------------------------------------------------

    /**
     * Update
     */
    function update()
    {
        return true;
    }

    // ----------------------------------------------------------------------

    /**
     * display_global_settings
     *
     * @access public
     */
    function display_global_settings()
    {
        $data = array_merge($this->settings, $_POST);

        $form = '';
        foreach (\Wyvern_config::fieldtype_settings() as $field => $options) {
            //global?
            if (!$options['global']) {
                continue;
            }

            switch ($options['type']) {
                //multiselect
                case 'm' :
                    $form .= form_label($options['label'], $options['label']) . NBS . form_multiselect(
                            $this->prefix . $options['name'],
                            $options['options'],
                            (isset($data[$this->prefix . $options['name']]) ? $data[$this->prefix . $options['name']] : $options['def_value'])
                        ) . NBS . NBS . NBS . ' ';
                    break;

                //select field
                case 's' :
                    $form .= form_label($options['label'], $options['label']) . NBS . form_dropdown(
                            $this->prefix . $options['name'],
                            $options['options'],
                            (isset($data[$this->prefix . $options['name']]) ? $data[$this->prefix . $options['name']] : $options['def_value'])
                        ) . NBS . NBS . NBS . ' ';
                    break;

                //text field
                default:
                case 't' :
                    $form .= form_label($options['label'], $options['label']) . NBS . form_input(
                            $this->prefix . $options['name'],
                            (isset($data[$this->prefix . $options['name']]) ? $data[$this->prefix . $options['name']] : $options['def_value'])
                        ) . NBS . NBS . NBS . ' ';
                    break;
            }
        }

        return $form;
    }

    // ----------------------------------------------------------------------

    /**
     * save_global_settings
     *
     * @access public
     */
    function save_global_settings()
    {
        return array_merge($this->settings, $_POST);
    }

    // ----------------------------------------------------------------------

    /**
     * accepts_content_type (GRID EE 2.7)
     *
     * @access public
     */
    public function accepts_content_type($name)
    {
        return ($name == 'channel' || $name == 'grid' || $name == 'fluid_field' || $name == 'blocks/1');
    }

    // --------------------------------------------------------------------

    private function generateField($options = array())
    {
        $return = array(
            $this->prefix . $options['name'] => $this->generatePerField($options)
        );

        return $return;
    }

    // --------------------------------------------------------------------

    private function generatePerField($options = array())
    {
        $field_options = $options;

        if (isset($options['options'])) {
            $field_options['choices'] = $options['options'];
        }

        unset($field_options['name']);

        return $field_options;
    }

    // --------------------------------------------------------------------

    public function get_setting($name = '', $default = false)
    {
        if (isset($this->settings[$name])) {
            return $this->settings[$name];
        }

        return '';
    }

    /**
     * Modify DB column
     *
     * @param Array $data
     * @return Array
     */
    public function settings_modify_column($data)
    {
        return $this->get_column_type($data);
    }

    /**
     * Modify DB grid column
     *
     * @param array $data The field data
     * @return array  [column => column_definition]
     */
    public function grid_settings_modify_column($data)
    {
        return $this->get_column_type($data, true);
    }

    /**
     * Helper method for column definitions
     *
     * @param array $data The field data
     * @param bool $grid Is grid field?
     * @return array  [column => column_definition]
     */
    protected function get_column_type($data, $grid = false)
    {
        $column = ($grid) ? 'col' : 'field';

        $settings = ($grid) ? $data : $data[$column . '_settings'];
        $field_content_type = isset($settings['wyvern_db_column_type']) ? $settings['wyvern_db_column_type'] : 'text';

        return [
            $column . '_id_' . $data[$column . '_id'] => [
                'type' => $field_content_type,
                'null' => true
            ]
        ];
    }
}

