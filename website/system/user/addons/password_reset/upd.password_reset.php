<?php

class Password_reset_upd
{
    public $version = '1.0.0';
    public $actions = [
        [
            'class' => 'Password_reset',
            'method' => 'reset_password',
        ],
    ];

    public function install()
    {
        ee()->db->insert('modules', [
            'module_name' => 'Password_reset',
            'module_version' => $this->version,
            'has_cp_backend' => 'n',
            'has_publish_fields' => 'n',
        ]);

        foreach ($this->actions as $action) {
            ee()->db->insert('actions', $action);
        }

        return true;
    }

    public function uninstall()
    {
        ee()->db->where('module_name', 'Password_reset')->delete('modules');
        ee()->db->where('class', 'Password_reset')->delete('actions');

        return true;
    }

    public function update($current = '')
    {
        return false;
    }
}
