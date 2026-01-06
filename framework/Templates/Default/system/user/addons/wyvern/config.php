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

//contants
if (!defined('WYVERN_NAME')) {
    define('WYVERN_NAME', 'Wyvern (By Reinos)');
    define('WYVERN_CLASS', 'Wyvern');
    define('WYVERN_MAP', 'wyvern');
    define('WYVERN_VERSION', '5.5.1');
    define('WYVERN_DESCRIPTION', 'A WYSIWYG editor with native File Browser and Pages/Structure support.');
    define('WYVERN_DOCS', 'http://addons.reinos.nl/wyvern');
    define('WYVERN_AUTHOR', 'Rein de Vries');
    define('WYVERN_AUTHOR_URL', 'http://addons.reinos.nl/');
}

//configs
$config['name'] = WYVERN_NAME;
$config['version'] = WYVERN_VERSION;

//load compat file
require_once(PATH_THIRD . WYVERN_MAP . '/compat.php');


if (!class_exists('Wyvern_config')) {

    class Wyvern_config
    {
        //updates
        public static function updates()
        {
            return array(
                '2.0.0',
                '2.2.0',
                '3.0.1',
                '3.1.0',
                '4.0.0',
                '4.1.5',
                '4.1.6',
                '5.0.1',
                '5.3.0',
            );
        }

        //Default Post
        public static function default_post()
        {
            return array(
                'license_key' => '',
                'license_reinos_member_id' => '',
                'license_valid' => false,
                'license_report_date' => time(),
                'log_severity' => 'errors',

                'css_path' => '/themes/user/wyvern/assets/wysiwyg/wysiwyg.css',
                'js_path' => '/themes/user/wyvern/assets/wysiwyg/wysiwyg.js',
                'google_font' => '',
                'typekit' => '',
                'parse_ee_tags' => '',
                'image_class' => 'wyvern_image',
                'obfuscate_email' => '',
                'extra_config' => "pasteFromWordPromptCleanup: true\nforcePasteAsPlainText: true",
                'display_block' => 'y',
                'default_link_type' => 'site_pages',
                'file_manager' => 'default',
                'linkable_dialog_templates' => '', //not selectable, we fill it in with the option below
                'linkable_dialog_template_type' => 'show_all',
                'linkable_dialog_template_groups' => '',
                'linkable_dialog_template_templates' => '',
                'default_toolbar' => 0,
                'templates' => serialize([]), // used to cache all templates

                'debug_mode' => false,
            );
        }


        //overrides
        public static function overide_settings()
        {
            return array(
                //'gmaps_icon_dir' => '[theme_dir]images/icons/',
                //'gmaps_icon_url' => '[theme_url]images/icons/',
            );
        }


        //show the field in wide or default
        public static function field_wide()
        {
            return true;
        }

        //based on https://docs.expressionengine.com/latest/development/shared_form_view.html
        public static function fieldtype_settings()
        {
            return  array(
                array(
                    'label' => lang('display_height'),
                    'desc' => 'Height in px of the field',
                    'name' => 'display_height',
                    'type' => 'text', //
                    'def_value' => '200',
                    'global' => false,
                ),
                array(
                    'label' => lang('resize_enabled'),
                    'desc' => '',
                    'name' => 'resize_enabled',
                    'type' => 'yes_no',
                    'def_value' => 'y',
                    'global' => false,
                ),
                array(
                    'label' => lang('auto_grow'),
                    'desc' => '',
                    'name' => 'auto_grow',
                    'type' => 'yes_no',
                    'def_value' => 'y',
                    'global' => false,
                ),
                array(
                    'label' => lang('auto_grow_on_startup'),
                    'desc' => '',
                    'name' => 'auto_grow_on_startup',
                    'type' => 'yes_no',
                    'def_value' => 'n',
                    'global' => false,
                ),
                array(
                    'label' => lang('Toolbar'),
                    'name' => 'toolbar',
                    'type' => 'html', // s=select, m=multiselect t=text
                    'content' => 'class:wyvern_lib|method:generateFieldsettingToolbarTable',
                    'wide' => true,
                    'def_value' => '',
                ),
                array(
                    'label' => lang('text_direction'),
                    'desc' => '',
                    'name' => 'text_direction',
                    'type' => 'radio',
                    'choices' => array(
                        'ltr' => 'Left to Right',
                        'rlt' => 'Right to Left'
                    ),
                    'def_value' => 'ltr',
                    'global' => false,
                ),

                array(
                    'label' => lang('upload_dir_images'),
                    'desc' => 'Used for the image tool',
                    'name' => 'upload_dir_images',
                    'type' => 'select',
                    'choices' => 'class:wyvern_model|method:getUploadPrefs',
                    'def_value' => '',
                    'global' => false,
                ),

                array(
                    'label' => lang('upload_dir_files'),
                    'desc' => 'Used for the link and filemanager tool',
                    'name' => 'upload_dir_files',
                    'type' => 'select',
                    'choices' => 'class:wyvern_model|method:getUploadPrefs',
                    'def_value' => '',
                    'global' => false,
                ),

                array(
                    'label' => lang('auto_link_urls'),
                    'desc' => '',
                    'name' => 'auto_link_urls',
                    'type' => 'yes_no',
                    'def_value' => 'y',
                    'global' => false,
                ),

                array(
                    'label' => lang('allow_img_urls'),
                    'desc' => '',
                    'name' => 'allow_img_urls',
                    'type' => 'yes_no',
                    'def_value' => 'y',
                    'global' => false,
                ),

                array(
                    'label' => lang('enter_mode'),
                    'desc' => 'Set the enter mode for the editor',
                    'name' => 'enter_mode',
                    'type' => 'radio',
                    'def_value' => 'p',
                    'choices' => array(
                        'p' => 'paragraph',
                        'div' => 'div',
                        'br' => 'break',
                    ),
                    'global' => false,
                ),

                array(
                    'label' => lang('Custom CSS'),
                    'desc' => 'Your custom style for this field only.',
                    'name' => 'css',
                    'type' => 'textarea', // s=select, m=multiselect t=text
                    'wide' => true,
                    'def_value' => '',
                ),

                array(
                    'label' => lang('Merge custom CSS with global CSS'),
                    'desc' => 'Merge custom CSS with global CSS. When set this to disable, it will only load the custom CSS defined in the field above.',
                    'name' => 'merge_custom_css',
                    'type' => 'yes_no',
                    'def_value' => 'y',
                    'global' => false,
                ),

                array(
                    'label' => lang('db_column_type'),
                    'desc' => lang('db_column_type_desc'),
                    'name' => 'db_column_type',
                    'type' => 'radio',
                    'choices' => [
                        'text' => lang('TEXT'),
                        'mediumtext' => lang('MEDIUMTEXT')
                    ],
                    'global' => false,
                ),
            );
        }


        public static function mysql_table_data()
        {
            return array(
                'settings' => array(
                    'keys' => array(
                        array('settings_id', true), //primary
                        //array('field_name'),
                    ),
                    'fields' => array(
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
                    )
                ),

                'logs' => array(
                    'keys' => array(
                        array('log_id', true), //primary
                        //array('field_name'),
                    ),
                    'fields' => array(
                        'log_id' => array(
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
                        'severity' => array(
                            'type' => 'varchar',
                            'constraint' => '200',
                            'null' => false,
                            'default' => 'notice'
                        ),
                        'time' => array(
                            'type' => 'int',
                            'constraint' => 10,
                            'unsigned' => true,
                            'null' => false,
                            'default' => 0
                        ),
                        'message' => array(
                            'type' => 'text'
                        ),
                    )
                ),

                'toolbars' => array(
                    'keys' => array(
                        array('toolbar_id', true), //primary
                        //array('field_name'),
                    ),
                    'fields' => array(
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
                    )
                ),
            );
        }

        public static function toolbar()
        {
            $toolbar = new stdClass();
            $toolbar->buttons = array(
                'Source' => [lang('Source'), ''],
                '-' => [lang('Separator'), 'separator'],
                'Cut' => [lang('Cut'), ''],
                'Copy' => [lang('Copy'), ''],
                'Paste' => [lang('Paste'), ''],
                'PasteText' => [lang('PasteText'), ''],
                'PasteFromWord' => [lang('PasteFromWord'), ''],
                'Undo' => [lang('Undo'), ''],
                'Find' => [lang('Find'), ''],
                'Replace' => [lang('Replace'), ''],
                'SelectAll' => [lang('SelectAll'), ''],
                'Scayt' => [lang('Scayt'), ''],
                '/' => [lang('New line'), 'enter'],
                'Bold' => [lang('Bold'), ''],
                'Italic' => [lang('Italic'), ''],
                'Underline' => [lang('Underline'), ''],
                'Strike' => [lang('Strike'), ''],
                'Subscript' => [lang('Subscript'), ''],
                'Superscript' => [lang('Superscript'), ''],
                'CopyFormatting' => [lang('CopyFormatting'), ''],
                'RemoveFormat' => [lang('RemoveFormat'), ''],
                'NumberedList' => [lang('NumberedList'), 'numberedlist'],
                'BulletedList' => [lang('BulletedList'), 'bulletedlist'],
                'Outdent' => [lang('Outdent'), ''],
                'Indent' => [lang('Indent'), ''],
                'Blockquote' => [lang('Blockquote'), ''],
                'CreateDiv' => [lang('CreateDiv'), ''],
                'JustifyLeft' => [lang('JustifyLeft'), ''],
                'JustifyCenter' => [lang('JustifyCenter'), ''],
                'JustifyRight' => [lang('JustifyRight'), ''],
                'JustifyBlock' => [lang('JustifyBlock'), ''],
                'BidiLtr' => [lang('BidiLtr'), ''],
                'BidiRtl' => [lang('BidiRtl'), ''],
                'Link' => [lang('Link'), ''],
                'Unlink' => [lang('Unlink'), ''],
                'Anchor' => [lang('Anchor'), ''],
                'Image' => [lang('Image'), ''],
                'Flash' => [lang('Flash'), ''],
                'Table' => [lang('Table'), ''],
                'HorizontalRule' => [lang('HorizontalRule'), ''],
                'Smiley' => [lang('Smiley'), ''],
                'SpecialChar' => [lang('SpecialChar'), ''],
                'PageBreak' => [lang('PageBreak'), ''],
                'Iframe' => [lang('Iframe'), ''],
                'Styles' => [lang('Styles'), ''],
                'Format' => [lang('Format'), ''],
                'Font' => [lang('Font'), ''],
                'FontSize' => [lang('FontSize'), ''],
                'TextColor' => [lang('TextColor'), ''],
                'BGColor' => [lang('BGColor'), ''],
                'Maximize' => [lang('Maximize'), ''],
                'ShowBlocks' => [lang('ShowBlocks'), ''],
                'h1' => [lang('h1'), ''],
                'h2' => [lang('h2'), ''],
                'h3' => [lang('h3'), ''],
                'h4' => [lang('h4'), ''],
                'h5' => [lang('h5'), ''],
                'h6' => [lang('h6'), ''],
                'Youtube' => [lang('youtube'), ''],
                'Embed' => [lang('Embed'), ''],
                //'EECode' => [lang('eecode'), ''],

                //custom
                'Filemanager' => [lang('Filemanager'), ''],
            );

            ksort($toolbar->buttons);


            $toolbar->empty = array();

            $toolbar->lite = array(
                'Bold',
                'Italic',
                'Underline',
                'Strike',
                '-',
                'Maximize',
            );

            $toolbar->basic = array(
                'Bold',
                'Italic',
                'Underline',
                'Strike',
                '-',
                'NumberedList',
                'BulletedList',
                'Blockquote',
                '-',
                'JustifyLeft',
                'JustifyCenter',
                'JustifyRight',
                'JustifyBlock',
                '-',
                'PasteText',
                'PasteFromWord',
                '-',
                'Link',
                'Unlink',
                '-',
                'ShowBlocks',
                'Maximize',
                'Source',
            );

            $toolbar->full = array(
                'Bold',
                'Italic',
                'Underline',
                'Strike',
                '-',
                'NumberedList',
                'BulletedList',
                'Blockquote',
                '-',
                'JustifyLeft',
                'JustifyCenter',
                'JustifyRight',
                'JustifyBlock',
                '-',
                'PasteText',
                'PasteFromWord',
                'RemoveFormat',
                '-',
                'h1',
                'h2',
                'h3',
                'h4',
                'h5',
                'h6',
                '-',
                'Link',
                'Unlink',
                '-',
                'Image',
                'Table',
                'Iframe',
                'Filemanager',
                'Youtube',
                'Embed',
                '-',
                'ShowBlocks',
                'Maximize',
                'Source',
            );

            return $toolbar;
        }
    }
}
