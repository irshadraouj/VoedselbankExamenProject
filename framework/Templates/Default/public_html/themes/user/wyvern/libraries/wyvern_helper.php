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
class Wyvern_helper
{
    /**
     * Remove the double slashes
     */
    public static function remove_double_slashes($str)
    {
        return preg_replace("#(^|[^:])//+#", "\\1/", $str);
    }

    // ----------------------------------------------------------------------

    /**
     * Check if Submitted String is a Yes value
     *
     * If the value is 'y', 'yes', 'true', or 'on', then returns TRUE, otherwise FALSE
     *
     */
    public static function check_yes($which, $string = false)
    {
        if (is_string($which)) {
            $which = strtolower(trim($which));
        }

        $result = in_array($which, array('yes', 'y', 'true', 'on'), true);

        if ($string) {
            return $result ? 'true' : 'false';
        }

        return $result;
    }

    // ------------------------------------------------------------------------

    /**
     * Is the string serialized
     *
     */
    public static function is_serialized($val)
    {
        /* if (!is_string($val)){ return false; }
        if (trim($val) == "") { return false; }
        if (preg_match("/^(i|s|a|o|d):(.*);/si",$val)) { return true; }*/

        $data = @unserialize($val);
        if ($data !== false) {
            return true;
        }
        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * Is the string json
     *
     */
    public static function is_json($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    // ------------------------------------------------------------------------

    /**
     * Retrieve site path
     */
    public static function get_site_path()
    {
        // extract path info
        $site_url_path = parse_url(ee()->functions->fetch_site_index(), PHP_URL_PATH);

        $path_parts = pathinfo($site_url_path);
        $site_path = $path_parts['dirname'];

        $site_path = str_replace("\\", "/", $site_path);

        return $site_path;
    }

    // ------------------------------------------------------------------------

    /**
     * remove beginning and ending slashes in a url
     *
     * @param  $url
     * @return void
     */
    public static function remove_begin_end_slash($url, $slash = '/')
    {
        $url = explode($slash, $url);
        array_pop($url);
        array_shift($url);
        return implode($slash, $url);
    }

    // ----------------------------------------------------------------------

    /**
     * add slashes for an array
     *
     * @param  $arr_r
     * @return void
     */
    public static function add_slashes_extended(&$arr_r)
    {
        if (is_array($arr_r)) {
            foreach ($arr_r as &$val) {
                is_array($val) ? self::add_slashes_extended($val) : $val = addslashes($val);
            }
            unset($val);
        } else {
            $arr_r = addslashes($arr_r);
        }
    }

    // ----------------------------------------------------------------

    /**
     * add a element to a array
     *
     * @return  DB object
     */
    public static function array_unshift_assoc(&$arr, $key, $val)
    {
        $arr = array_reverse($arr, true);
        $arr[$key] = $val;
        $arr = array_reverse($arr, true);
        return $arr;
    }

    // ----------------------------------------------------------------------

    /**
     * get the memory usage
     *
     * @param
     * @return void
     */
    public static function memory_usage()
    {
        $mem_usage = memory_get_usage(true);

        if ($mem_usage < 1024) {
            return $mem_usage . " bytes";
        } elseif ($mem_usage < 1048576) {
            return round($mem_usage / 1024, 2) . " KB";
        } else {
            return round($mem_usage / 1048576, 2) . " MB";
        }
    }

    // ----------------------------------------------------------------------

    /**
     * EDT benchmark
     * https://github.com/mithra62/ee_debug_toolbar/wiki/Benchmarks
     *
     * @param none
     * @return void
     */
    public static function benchmark($method = '', $start = true)
    {
        if ($method != '') {
            $prefix = WYVERN_MAP . '_';
            $type = $start ? '_start' : '_end';
            ee()->benchmark->mark($prefix . $method . $type);
        }
    }

    // ----------------------------------------------------------------------

    /**
     *    Fetch Action IDs
     *
     * @access public
     * @param string
     * @param string
     * @return mixed
     */
    public static function fetch_action_id($class, $method)
    {
        ee()->db->select('action_id');
        ee()->db->where('class', $class);
        ee()->db->where('method', $method);
        $query = ee()->db->get('actions');

        if ($query->num_rows() == 0) {
            return false;
        }

        return $query->row('action_id');
    }

    // ----------------------------------------------------------------------

    /**
     * Parse only a string
     *
     * @param none
     * @return void
     */
    public static function parse_tags($tag = '')
    {
        //check the ee()->TMPL object
        if (isset(ee()->TMPL)) {
            $OLD_TMPL = ee()->TMPL;
            ee()->remove('TMPL');
        } else {
            require_once APPPATH . 'libraries/Template.php';
            $OLD_TMPL = null;
        }

        //set the new ee()->TMPL
        ee()->set('TMPL', new EE_Template());
        ee()->TMPL->parse($tag, true);
        $tag = ee()->TMPL->parse_globals($tag);
        $tag = ee()->TMPL->remove_ee_comments($tag);

        //remove and add the old TMPL object to the ee()->TMPL object if null
        if ($OLD_TMPL !== null) {
            ee()->remove('TMPL');
            ee()->set('TMPL', $OLD_TMPL);
        }

        //return the data
        return trim($tag);
    }

    // ----------------------------------------------------------------------

    /**
     * Parse a template
     *
     * @param none
     * @return void
     */
    public static function parse_template($template_id = 0)
    {
        //load model
        ee()->load->model('template_model');

        //get the template
        $template = ee()->template_model->get_templates(null, array(), array('template_id' => $template_id));

        //is there an template
        if ($template->num_rows() > 0) {
            $template = $template->result();

            //go to the template parser
            require_once APPPATH . 'libraries/Template.php';
            ee()->load->library('template', null, 'TMPL');
            ee()->TMPL->run_template_engine($template[0]->group_name, $template[0]->template_name);
            ee()->output->_display();
        } else {
            echo 'No template selected';
        }

        exit;
    }

    // ----------------------------------------------------------------------

    /**
     * Get the data from tagdat
     *
     * @param none
     * @return void
     */
    public static function get_from_tagdata($field = 'field', $default_value = '')
    {
        //get the tag pair data
        //can be for example {address}{/address}
        if (preg_match_all(
                "/" . LD . $field . RD . "(.*?)" . LD . "\/" . $field . RD . "/s",
                ee()->TMPL->tagdata,
                $tmp
            ) != 0) {
            if (isset($tmp[1][0])) {
                //trim to one line
                $tmp[1][0] = wyvern_helper::trim_to_one_line($tmp[1][0]);

                //convert double quotes to single quotes
                $tmp[1][0] = str_replace('"', "'", $tmp[1][0]);

                //check for stash
                if (preg_match_all(
                    "/" . LD . "exp:stash:(.*?)" . RD . "(.*?)" . LD . "\/exp:stash:(.*?)" . RD . "/s",
                    $tmp[1][0],
                    $stash_match
                )) {
                    if (!class_exists('Stash')) {
                        include_once PATH_THIRD . 'stash/mod.stash.php';
                    }

                    //parse the whole tag
                    $stash_result = Stash::parse(array(), $stash_match[0][0]);

                    //place the result in the template
                    $tmp[1][0] = str_replace($stash_match[0][0], $stash_result, $tmp[1][0]);
                }

                if (preg_match_all("/" . LD . "exp:stash:get(.*?)" . RD . "/s", $tmp[1][0], $stash_match)) {
                    if (!class_exists('Stash')) {
                        include_once PATH_THIRD . 'stash/mod.stash.php';
                    }

                    //parse the whole tag
                    $stash_result = Stash::parse(array(), $tmp[0][0]);

                    //fix?
                    $stash_result = str_replace(
                        array(
                            LD . $field . RD,
                            LD . '/' . $field . RD,

                        ),
                        '',
                        $stash_result
                    );

                    //place the result in the template
                    $tmp[1][0] = str_replace($stash_match[0][0], $stash_result, $tmp[1][0]);
                }


                //remove the tagdata
                ee()->TMPL->tagdata = str_replace($tmp[0][0], '', ee()->TMPL->tagdata);

                //go to the parser to parse any module tag data, if present
                $parsed_data = wyvern_helper::parse_tags($tmp[1][0]);

                //remove from tagdata
                ee()->TMPL->tagdata = str_replace($tmp[0][0], '', ee()->TMPL->tagdata);


                //return the data
                return $parsed_data;
            }
        } //get normal tagdata form params
        else {
            return ee()->TMPL->fetch_param($field, $default_value);
        }

        return '';
    }

    // ----------------------------------------------------------------------

    /**
     * set_cache
     *
     * @access private
     * @param string $name
     * @param string $value
     * @param bool $reset
     * @return
     */
    public static function set_ee_cache($name = '', $value = '', $reset = false)
    {
        if (isset(ee()->session->cache[GMAPS_MAP][$name]) == false || $reset == true) {
            ee()->session->cache[GMAPS_MAP][$name] = $value;
        }
        return ee()->session->cache[GMAPS_MAP][$name];
    }

    // ----------------------------------------------------------------------

    /**
     * get_cache
     *
     * @access private
     * @param string $name
     * @return bool
     */
    public static function get_ee_cache($name = '')
    {
        if (isset(ee()->session->cache[WYVERN_MAP][$name]) != false) {
            return ee()->session->cache[WYVERN_MAP][$name];
        }
        return false;
    }

    // ----------------------------------------------------------------------

    /**
     * set_cache
     *
     * @access private
     * @param string $name
     * @param string $value
     */
    public static function set_cache($name = '', $value = '')
    {
        if (session_id() == "") {
            session_start();
        }

        $_SESSION[$name] = $value;
    }

    // ----------------------------------------------------------------------

    /**
     * get_cache
     *
     * @access private
     * @param string $name
     * @return string
     */
    public static function get_cache($name = '')
    {
        // if no active session we start a new one
        if (session_id() == "") {
            session_start();
        }

        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        } else {
            return '';
        }
    }

    // ----------------------------------------------------------------------

    /**
     * delete_cache
     *
     * @access private
     * @param string $name
     */
    public static function delete_cache($name = '')
    {
        // if no active session we start a new one
        if (session_id() == "") {
            session_start();
        }

        unset($_SESSION[$name]);
    }

    // ----------------------------------------------------------------------

    /**
     * mcp_meta_parser
     *
     * @access private
     * @param string $type
     * @param $file
     */
    public static function mcp_meta_parser($type, $file)
    {
        // -----------------------------------------
        // CSS
        // -----------------------------------------
        if ($type == 'css') {
            if (isset(ee()->session->cache[WYVERN_MAP]['CSS'][$file]) == false) {
                ee()->cp->add_to_head(
                    '<link rel="stylesheet" href="' . ee()->wyvern_settings->get_setting(
                        'theme_url'
                    ) . 'css/' . $file . '" type="text/css" media="print, projection, screen" />'
                );
                ee()->session->cache[WYVERN_MAP]['CSS'][$file] = true;
            }
        }
        if ($type == 'css_custom_path') {
            if (isset(ee()->session->cache[WYVERN_MAP]['CSS'][$file]) == false) {
                ee()->cp->add_to_head(
                    '<link rel="stylesheet" href="' . $file . '" type="text/css" media="print, projection, screen" />'
                );
                ee()->session->cache[WYVERN_MAP]['CSS'][$file] = true;
            }
        }

        // -----------------------------------------
        // CSS Inline
        // -----------------------------------------
        if ($type == 'css_inline') {
            ee()->cp->add_to_foot('<style type="text/css">' . $file . '</style>');
        }

        // -----------------------------------------
        // Javascript
        // -----------------------------------------
        if ($type == 'js') {
            if (isset(ee()->session->cache[WYVERN_MAP]['JS'][$file]) == false) {
                ee()->cp->add_to_foot(
                    '<script src="' . ee()->wyvern_settings->get_setting(
                        'theme_url'
                    ) . 'javascript/' . $file . '" type="text/javascript"></script>'
                );
                ee()->session->cache[WYVERN_MAP]['JS'][$file] = true;
            }
        }
        if ($type == 'js_custom_path') {
            if (isset(ee()->session->cache[WYVERN_MAP]['JS'][$file]) == false) {
                ee()->cp->add_to_foot('<script src="' . $file . '" type="text/javascript"></script>');
                ee()->session->cache[WYVERN_MAP]['JS'][$file] = true;
            }
        }

        // -----------------------------------------
        // Javascript Inline
        // -----------------------------------------
        if ($type == 'js_inline') {
            ee()->cp->add_to_foot('<script type="text/javascript">' . $file . '</script>');
        }
    }

    // ----------------------------------------------------------------------

    /**
     * Create url title
     * @param string $uri
     * @param string $replace_with
     * @return mixed
     */
    public static function create_uri($uri = '', $replace_with = '-')
    {
        return preg_replace("#[^a-zA-Z0-9_\-]+#i", $replace_with, strtolower($uri));
    }

    // ----------------------------------------------------------------------

    /**
     * encode data
     */
    public static function encode_data($str = '')
    {
        //not set, use the default folder
        if (ee()->config->item('encryption_key') == false) {
            ee()->config->set_item('encryption_key', WYVERN_MAP);
        }

        if (is_array($str)) {
            $str = serialize($str);
        }

        ee()->load->library('encrypt');
        $str = ee()->encrypt->encode($str);

        return $str;
    }

    // ----------------------------------------------------------------------

    /**
     * encode data
     */
    public static function decode_data($str = '')
    {
        //not set, use the default folder
        if (ee()->config->item('encryption_key') == false) {
            ee()->config->set_item('encryption_key', WYVERN_MAP);
        }

        ee()->load->library('encrypt');
        $str = ee()->encrypt->decode($str);

        if (self::is_serialized($str)) {
            $str = unserialize($str);
        }

        return $str;
    }

    // ----------------------------------------------------------------

    /**
     * Send email template
     */
    public static function send_email($template_name_or_html = '', $data = array(), $type = 'text')
    {
        ee()->load->library(array('email', 'template'));
        ee()->load->helper('text');

        ee()->email->mailtype = $type;

        ee()->email->wordwrap = true;

        //get the template

        if ($template_name_or_html != '') {
            //get the template
            $template = ee()->functions->fetch_email_template($template_name_or_html);

            //no template
            if (empty($template['title']) or empty($template['data'])) {
                return;
            }

            $template_data = $template['data'];
            $template_title = $template['title'];
        } else {
            $template_title = $data['template_title'];
            $template_data = $template_name_or_html;
        }

        //override email title?
        if (isset($data['subject'])) {
            $template_title = $data['subject'];
        }

        //set default values
        $def_vars = array(
            'site_name' => stripslashes(ee()->config->item('site_name')),
            'site_url' => ee()->config->item('site_url'),
        );

        $vars = array_merge($def_vars, $data);

        $tmpl_vars = array($vars);
        $email_title = ee()->TMPL->parse_variables($template_title, $tmpl_vars);
        $template_data = ee()->TMPL->parse_simple_segment_conditionals($template_data);
        $template_data = ee()->TMPL->simple_conditionals($template_data, $tmpl_vars);
        $template_data = ee()->TMPL->parse_variables($template_data, $tmpl_vars);

        $template_data = ee()->TMPL->advanced_conditionals($template_data);
        $email_body = wyvern_helper::parse_tags($template_data);

        //break lines in html
        if ($type == 'html') {
            $email_body = nl2br($email_body);
        }

        //files?
        if (isset($vars['files'])) {
            foreach ($vars['files'] as $file) {
                if (is_file($file)) {
                    ee()->email->attach($file);
                }
            }
        }

        // sender address defaults to site webmaster email
        if (!isset($vars['from']) || $vars['from'] == '' || !isset($vars['from_name']) || $vars['from_name'] == '') {
            ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
        } else {
            ee()->email->from($vars['from'], $vars['from_name']);
        }

        // do we have a BCC address?
        if (isset($vars['bcc']) && $vars['bcc'] && $vars['bcc'] != '') {
            ee()->email->bcc($vars['bcc']);
        }

        // send message
        ee()->email->to($vars['to']);
        ee()->email->subject(entities_to_ascii($email_title));
        ee()->email->message(entities_to_ascii($email_body));
        ee()->email->send();
        ee()->email->clear(true);
    }

    // --------------------------------------------------------------------

    /**
     * add a hook
     */
    public static function add_hook(
        $hook = '',
        $data = array(),
        $end_script = false,
        $extra_param2 = null,
        $extra_param3 = null
    ) {
        if ($hook && ee()->extensions->active_hook(WYVERN_MAP . '_' . $hook) === true) {
            //call the extension
            $data = ee()->extensions->call(WYVERN_MAP . '_' . $hook, $data, $extra_param2, $extra_param3);

            //end of script?
            if ($end_script) {
                if (ee()->extensions->end_script === true) {
                    return;
                }
            }
        }

        return $data;
    }

    /**
     * @param $method
     * @param array $parameters
     * @return string
     * @internal param null $class
     */
    static function get_page_action_url($method, $parameters = array())
    {
        return self::build_action_url($method, $parameters, false);
    }

    /**
     * @param $method
     * @param array $parameters
     * @param bool $isForm
     * @return string
     * @internal param null $class
     */
    static function build_action_url($method, $parameters = array(), $isForm = false)
    {
        ee()->load->helper('url');

        // If it contains a slash then its a full controller/action path, usually to core EE pages.
        // Otherwise, we're linking to a Publisher specific page.
        if (strpos($method, '/') !== false) {
            $url = cp_url($method, $parameters, $isForm);
        } else {
            $parameters = array_merge(
                array(
                    'module' => WEBSERVICE_MAP,
                    'method' => $method
                ),
                $parameters
            );

            $url = cp_url('addons_modules/show_module_cp', $parameters, $isForm);
        }

        // Due to a reported in EE
        // https://boldminded.com/support/ticket/1072
        if (version_compare(APP_VER, '2.9.0', '=')) {
            // We need to force to have full cp url
            $url = str_replace(SELF, '', $url);
            $url = ee()->config->item('cp_url') . $url;
        }

        $url = str_replace('&amp;', '&', $url);

        return $url;
    }

    // ----------------------------------------------------------------

    /**
     * Format a date
     */
    public static function format_date($format = '', $date = null, $localize = true)
    {
        if (method_exists(ee()->localize, 'format_date') === true) {
            return ee()->localize->format_date($format, $date, $localize);
        } else {
            return ee()->localize->decode_date($format, $date, $localize);
        }
    }

    // ----------------------------------------------------------------

    /**
     * Custom No_Result conditional
     *
     * Same as {if no_result} but with your own conditional.
     *
     * @param string $cond_name
     * @param string $source
     * @param string $return_source
     * @return unknown
     */
    public static function custom_no_results_conditional($cond_name, $source, $return_source = false)
    {
        if (strpos($source, LD . "if {$cond_name}" . RD) !== false) {
            if (preg_match(
                '/' . LD . "if {$cond_name}" . RD . '(.*?)' . LD . '\/' . 'if' . RD . '/s',
                $source,
                $cond
            )) {
                return $cond[1];
            }
        }

        if ($return_source !== false) {
            return $source;
        }

        return;
    }

    // ----------------------------------------------------------------

    /**
     * Rewrite CP_URL with the 2.8.0 way
     *
     * @return unknown
     */
    public static function cp_url($url = '', $data = array(), $cp_data = false)
    {
        if (function_exists('cp_url') === true) {
            if ($cp_data && REQ == 'CP' && isset(ee()->db)) {
                $data = array_merge($data, array('module' => ENTRY_API_IMPORTER_MAP));
                return cp_url('cp/addons_modules/show_module_cp/', $data);
            } else {
                return cp_url($url, $data);
            }
        } else {
            //rewrite the data to an url
            $data = http_build_query($data, AMP);

            if ($cp_data && REQ == 'CP' && isset(ee()->db)) {
                return BASE . AMP . 'C=addons_modules' . AMP . 'M=show_module_cp' . AMP . 'module=' . ENTRY_API_IMPORTER_MAP . AMP . $data;
            } else {
                return $url . AMP . $data;
            }
        }
    }

    // ----------------------------------------------------------------------

    /**
     * Get the cache path
     *
     * @return unknown
     */
    public static function cache_path()
    {
        $cache_path = ee()->config->item('cache_path');

        if (empty($cache_path)) {
            $cache_path = APPPATH . 'cache/';
        }

        $cache_path .= WYVERN_MAP . '/';

        if (!is_dir($cache_path)) {
            mkdir($cache_path, DIR_WRITE_MODE);
            @chmod($cache_path, DIR_WRITE_MODE);
        }

        return $cache_path;
    }

    // ----------------------------------------------------------------------

    /**
     * create an url title
     */
    public static function create_url_title($string = '', $delimiter = '-')
    {
        return strtolower(
            trim(
                preg_replace(
                    '~[^0-9a-z]+~i',
                    $delimiter,
                    html_entity_decode(
                        preg_replace(
                            '~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i',
                            '$1',
                            htmlentities($string, ENT_QUOTES, 'UTF-8')
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    )
                ),
                $delimiter
            )
        );
    }

    // ----------------------------------------------------------------------

    /**
     * Create a redirect with messages for the CP
     * @param string $method
     * @param string $message_success
     * @param string $message_failure
     */
    public static function redirect_cp($method = 'settings', $message_success = '', $message_failure = '')
    {
        $notifications = array();

        //message success
        if (!empty($message_success)) {
            $notifications['message_success'] = ee()->lang->line(WYVERN_MAP . '_' . $message_success);
        }

        //message failure
        if (!empty($message_failure)) {
            $notifications['message_failure'] = ee()->lang->line(WYVERN_MAP . '_' . $message_failure);
        }

        //dSet the flash data
        ee()->session->set_flashdata($notifications);

        //redirect
        ee()->functions->redirect(
            BASE . AMP . 'C=addons_modules' . AMP . 'M=show_module_cp' . AMP . 'module=' . WYVERN_MAP . AMP . 'method=' . $method
        );
    }

    // ------------------------------------------------------------------------

    /**
     * Remove empty values (BETTER)
     *
     */
    public static function remove_empty_array_values($input)
    {
        return array_filter(
            $input,
            function ($a) {
                return trim($a) != "";
            }
        );
    }

    // ------------------------------------------------------------------------

    /**
     * Remove empty values
     *
     */
    public static function remove_empty_values($input)
    {
        // If it is an element, then just return it
        if (!is_array($input)) {
            return $input;
        }
        $non_empty_items = array();

        foreach ($input as $key => $value) {
            // Ignore empty cells
            if ($value) {
                // Use recursion to evaluate cells
                $non_empty_items[$key] = self::remove_empty_values($value);

                if ($non_empty_items[$key] == '') {
                    unset($non_empty_items[$key]);
                }
            }
        }

        if (empty($non_empty_items)) {
            $non_empty_items = '';
        }

        // Finally return the array without empty items
        return $non_empty_items;
    }

    // ------------------------------------------------------------------------

    /**
     * avoid double latlngs
     */
    public static function create_links_from_string($str, $target = '_blank')
    {
        return preg_replace("/(http:\/\/[^\s]+)/", "<a target='" . $target . "' href='$1'>$1</a>", $str);
    }

    // ----------------------------------------------------------------------

    /**
     * Trim multi line to one
     *
     * @param  $string
     * @return void
     */
    public static function trim_to_one_line($string)
    {
        $string = str_replace(array("\r\n", "\r"), "\n", $string);
        $lines = explode("\n", $string);
        $new_lines = array();
        foreach ($lines as $i => $line) {
            if (!empty($line)) {
                $new_lines[] = trim($line);
            }
        }
        return implode($new_lines);
    }

    // ----------------------------------------------------------------------

    /**
     *    Convert special to normal
     *
     * @access public
     * @param string
     * @param string
     * @return mixed
     */
    public static function transliterate_string($txt)
    {
        $transliterationTable = array(
            'á' => 'a',
            'Á' => 'A',
            'à' => 'a',
            'À' => 'A',
            'ă' => 'a',
            'Ă' => 'A',
            'â' => 'a',
            'Â' => 'A',
            'å' => 'a',
            'Å' => 'A',
            'ã' => 'a',
            'Ã' => 'A',
            'ą' => 'a',
            'Ą' => 'A',
            'ā' => 'a',
            'Ā' => 'A',
            'ä' => 'ae',
            'Ä' => 'AE',
            'æ' => 'ae',
            'Æ' => 'AE',
            'ḃ' => 'b',
            'Ḃ' => 'B',
            'ć' => 'c',
            'Ć' => 'C',
            'ĉ' => 'c',
            'Ĉ' => 'C',
            'č' => 'c',
            'Č' => 'C',
            'ċ' => 'c',
            'Ċ' => 'C',
            'ç' => 'c',
            'Ç' => 'C',
            'ď' => 'd',
            'Ď' => 'D',
            'ḋ' => 'd',
            'Ḋ' => 'D',
            'đ' => 'd',
            'Đ' => 'D',
            'ð' => 'dh',
            'Ð' => 'Dh',
            'é' => 'e',
            'É' => 'E',
            'è' => 'e',
            'È' => 'E',
            'ĕ' => 'e',
            'Ĕ' => 'E',
            'ê' => 'e',
            'Ê' => 'E',
            'ě' => 'e',
            'Ě' => 'E',
            'ë' => 'e',
            'Ë' => 'E',
            'ė' => 'e',
            'Ė' => 'E',
            'ę' => 'e',
            'Ę' => 'E',
            'ē' => 'e',
            'Ē' => 'E',
            'ḟ' => 'f',
            'Ḟ' => 'F',
            'ƒ' => 'f',
            'Ƒ' => 'F',
            'ğ' => 'g',
            'Ğ' => 'G',
            'ĝ' => 'g',
            'Ĝ' => 'G',
            'ġ' => 'g',
            'Ġ' => 'G',
            'ģ' => 'g',
            'Ģ' => 'G',
            'ĥ' => 'h',
            'Ĥ' => 'H',
            'ħ' => 'h',
            'Ħ' => 'H',
            'í' => 'i',
            'Í' => 'I',
            'ì' => 'i',
            'Ì' => 'I',
            'î' => 'i',
            'Î' => 'I',
            'ï' => 'i',
            'Ï' => 'I',
            'ĩ' => 'i',
            'Ĩ' => 'I',
            'į' => 'i',
            'Į' => 'I',
            'ī' => 'i',
            'Ī' => 'I',
            'ĵ' => 'j',
            'Ĵ' => 'J',
            'ķ' => 'k',
            'Ķ' => 'K',
            'ĺ' => 'l',
            'Ĺ' => 'L',
            'ľ' => 'l',
            'Ľ' => 'L',
            'ļ' => 'l',
            'Ļ' => 'L',
            'ł' => 'l',
            'Ł' => 'L',
            'ṁ' => 'm',
            'Ṁ' => 'M',
            'ń' => 'n',
            'Ń' => 'N',
            'ň' => 'n',
            'Ň' => 'N',
            'ñ' => 'n',
            'Ñ' => 'N',
            'ņ' => 'n',
            'Ņ' => 'N',
            'ó' => 'o',
            'Ó' => 'O',
            'ò' => 'o',
            'Ò' => 'O',
            'ô' => 'o',
            'Ô' => 'O',
            'ő' => 'o',
            'Ő' => 'O',
            'õ' => 'o',
            'Õ' => 'O',
            'ø' => 'oe',
            'Ø' => 'OE',
            'ō' => 'o',
            'Ō' => 'O',
            'ơ' => 'o',
            'Ơ' => 'O',
            'ö' => 'oe',
            'Ö' => 'OE',
            'ṗ' => 'p',
            'Ṗ' => 'P',
            'ŕ' => 'r',
            'Ŕ' => 'R',
            'ř' => 'r',
            'Ř' => 'R',
            'ŗ' => 'r',
            'Ŗ' => 'R',
            'ś' => 's',
            'Ś' => 'S',
            'ŝ' => 's',
            'Ŝ' => 'S',
            'š' => 's',
            'Š' => 'S',
            'ṡ' => 's',
            'Ṡ' => 'S',
            'ş' => 's',
            'Ş' => 'S',
            'ș' => 's',
            'Ș' => 'S',
            'ß' => 'SS',
            'ť' => 't',
            'Ť' => 'T',
            'ṫ' => 't',
            'Ṫ' => 'T',
            'ţ' => 't',
            'Ţ' => 'T',
            'ț' => 't',
            'Ț' => 'T',
            'ŧ' => 't',
            'Ŧ' => 'T',
            'ú' => 'u',
            'Ú' => 'U',
            'ù' => 'u',
            'Ù' => 'U',
            'ŭ' => 'u',
            'Ŭ' => 'U',
            'û' => 'u',
            'Û' => 'U',
            'ů' => 'u',
            'Ů' => 'U',
            'ű' => 'u',
            'Ű' => 'U',
            'ũ' => 'u',
            'Ũ' => 'U',
            'ų' => 'u',
            'Ų' => 'U',
            'ū' => 'u',
            'Ū' => 'U',
            'ư' => 'u',
            'Ư' => 'U',
            'ü' => 'ue',
            'Ü' => 'UE',
            'ẃ' => 'w',
            'Ẃ' => 'W',
            'ẁ' => 'w',
            'Ẁ' => 'W',
            'ŵ' => 'w',
            'Ŵ' => 'W',
            'ẅ' => 'w',
            'Ẅ' => 'W',
            'ý' => 'y',
            'Ý' => 'Y',
            'ỳ' => 'y',
            'Ỳ' => 'Y',
            'ŷ' => 'y',
            'Ŷ' => 'Y',
            'ÿ' => 'y',
            'Ÿ' => 'Y',
            'ź' => 'z',
            'Ź' => 'Z',
            'ž' => 'z',
            'Ž' => 'Z',
            'ż' => 'z',
            'Ż' => 'Z',
            'þ' => 'th',
            'Þ' => 'Th',
            'µ' => 'u',
            'а' => 'a',
            'А' => 'a',
            'б' => 'b',
            'Б' => 'b',
            'в' => 'v',
            'В' => 'v',
            'г' => 'g',
            'Г' => 'g',
            'д' => 'd',
            'Д' => 'd',
            'е' => 'e',
            'Е' => 'e',
            'ё' => 'e',
            'Ё' => 'e',
            'ж' => 'zh',
            'Ж' => 'zh',
            'з' => 'z',
            'З' => 'z',
            'и' => 'i',
            'И' => 'i',
            'й' => 'j',
            'Й' => 'j',
            'к' => 'k',
            'К' => 'k',
            'л' => 'l',
            'Л' => 'l',
            'м' => 'm',
            'М' => 'm',
            'н' => 'n',
            'Н' => 'n',
            'о' => 'o',
            'О' => 'o',
            'п' => 'p',
            'П' => 'p',
            'р' => 'r',
            'Р' => 'r',
            'с' => 's',
            'С' => 's',
            'т' => 't',
            'Т' => 't',
            'у' => 'u',
            'У' => 'u',
            'ф' => 'f',
            'Ф' => 'f',
            'х' => 'h',
            'Х' => 'h',
            'ц' => 'c',
            'Ц' => 'c',
            'ч' => 'ch',
            'Ч' => 'ch',
            'ш' => 'sh',
            'Ш' => 'sh',
            'щ' => 'sch',
            'Щ' => 'sch',
            'ъ' => '',
            'Ъ' => '',
            'ы' => 'y',
            'Ы' => 'y',
            'ь' => '',
            'Ь' => '',
            'э' => 'e',
            'Э' => 'e',
            'ю' => 'ju',
            'Ю' => 'ju',
            'я' => 'ja',
            'Я' => 'ja'
        );
        $txt = str_replace(array_keys($transliterationTable), array_values($transliterationTable), $txt);
        return $txt;
    }

    // ----------------------------------------------------------------------

    /**
     * @param string $name
     * @param string $html
     * @param bool|false $close
     * @return string
     */
    public static function createModal($name = '', $html = '', $close = false)
    {
        return '
            <div style="display:none" class="modal-wrap modal-' . $name . '">
                <div class="modal">
                    <div class="col-group">
                        <div class="col w-16">
                            ' . ($close ? '<a class="m-close" href="#"></a>' : '') . '
                            <div class="box">
                                ' . $html . '
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        ';
    }

    // ----------------------------------------------------------------------

    /**
     * @return bool
     */
    public static function is_ssl()
    {
        $is_SSL = false;

        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
            || $_SERVER['SERVER_PORT'] == 443) {
            $is_SSL = true;
        }


        return $is_SSL;
    }

    // ----------------------------------------------------------------------

    /**
     * Clone array
     *
     * @param none
     * @return void
     */
    public static function array_copy($arr = array())
    {
        $newArray = array();
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $newArray[$key] = self::array_copy($value);
            } elseif (is_object($value)) {
                $newArray[$key] = clone $value;
            } else {
                $newArray[$key] = $value;
            }
        }
        return $newArray;
    }

    // ----------------------------------------------------------------------

    /**
     * Search array key
     *
     * @param none
     * @return void
     */
    public static function filter_by_key_prefix($arr, $prefix, $drop_prefix = false)
    {
        $params = array();
        foreach ($arr as $k => $v) {
            if (strpos($k, $prefix) === 0) {
                if ($drop_prefix) {
                    $k = substr($k, strlen($prefix));
                }
                $params[$k] = $v;
            }
        }
        return $params;
    }

    // ----------------------------------------------------------------------

    /**
     * All our forms have common attributes
     *
     * @param array $data
     * @return
     */
    public static function form_open($data = array())
    {
        if (empty($data['hidden_fields'])) {
            $data['hidden_fields'] = array();
        }

        return ee()->functions->form_declaration($data);
    }

    // ----------------------------------------------------------------------

    /**
     * get the value from an array without errors
     *
     * @param array $array
     * @param string $key
     * @param string $default_value
     * @return string
     */
    public static function array_value($array = array(), $key = '', $default_value = '')
    {
        if (isset($array[$key])) {
            return $array[$key];
        }

        return $default_value;
    }

    // --------------------------------------------------------------------

    /**
     * Word limiter
     *
     * @access    public
     * @param $str
     * @param int $num
     * @param string $suffix
     * @return string
     * @internal param $string
     */
    public static function word_limiter($str, $num = 100, $suffix = '&#8230;')
    {
        if (strlen($str) < $num) {
            return $str;
        }

        $word = preg_split('/\s/u', $str, -1, PREG_SPLIT_NO_EMPTY);

        if (count($word) <= $num) {
            return $str;
        }

        $str = "";

        for ($i = 0; $i < $num; $i++) {
            $str .= $word[$i] . " ";
        }

        return trim($str) . $suffix;
    }

    // --------------------------------------------------------------------

    /**
     * get the current URL
     */
    public static function currentUrl()
    {
        $link = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";

        // Here append the common URL characters.
        $link .= "://";

        // Append the host(domain name, ip) to the URL.
        $link .= $_SERVER['HTTP_HOST'];

        // Append the requested resource location to the URL
        $link .= $_SERVER['REQUEST_URI'];

        // return the link
        return $link;
    }

    // ----------------------------------------------------------------------
    // CUSTOM
    // ----------------------------------------------------------------------


} // END CLASS
