<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Dunique_cp_heading_ft extends EE_Fieldtype
{
    public $info = array(
        'name'      => 'dunique_cp_heading',
        'version'   => '1.0.0',
    );
    public $default_settings = [
        'title' => '',
        'field_hide_title' => true,
        'field_hide_publish_layout_collapse' => true,
    ];
    
    public $disable_frontedit = true;
    public $supportedEvaluationRules = null;

    public function install()
    {
        return array(
            'title'  => '',
        );
    }

    public function display_global_settings()
    {
        $val = array_merge($this->settings, $_POST);

        $form = '';

        return $form;
    }

    public function save_global_settings()
    {
        return array_merge($this->settings, $_POST);
    }

    public function display_settings($data)
    {
        $data = array_merge($this->default_settings, $data);
        $title   = isset($data['title']) ? $data['title'] : $this->settings['title'];

        $settings = array(
            array(
                'title' => 'title',
                'desc' => 'title_desc',
                'fields' => array(
                    'title' => array(
                        'type' => 'textarea',
                        'value' => $title,
                        'rows' => 2
                    )
                )
            ),
        );
    
        return array('field_options_dunique_cp_heading' => array(
            'label' => 'field_options',
            'group' => 'dunique_cp_heading',
            'settings' => $settings
        ));
    }

    public function save_settings($data)
    {
        $all = array_merge($this->default_settings, $data);
        return array_intersect_key($all, $this->default_settings);
    }
    
    public function display_field($data)
    {
        return $this->parseMarkdown($data['title'] ?? $this->settings['title']);
    }
     /**
     * Accept all content types.
     *
     * @param string  The name of the content type
     * @return bool   Does not accept other content types
     */
    public function accepts_content_type($name)
    {
        return false;
    }

    public function parseMarkdown($markdown) {
        ee()->load->library('typography');
        if (str_starts_with($markdown, '# ')) {
            $start = '<div class="fields-upload-chosen list-item" style="margin-left: -25px; margin-right: -25px; padding: .5rem 25px; border-radius: 0; border-left: none; border-right:none; color: var(--ee-text-secondary);">';
        } else {
            $start = '<div style="margin-bottom: -20px;">';
        }
        $parsed = ee()->typography->markdown($markdown);
        $end = '</div>';
        return $start.$parsed.$end;
    }
}
