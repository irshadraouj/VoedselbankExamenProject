<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use ExpressionEngine\Service\Addon\Installer;

class Delivery_manager_upd extends Installer
{
    public $has_cp_backend = 'n';
    public $has_publish_fields = 'n';

    public function install()
    {
        parent::install();

        $this->ensure_action();

        return true;
    }

    public function update($current = '')
    {
        parent::update($current);

        $this->ensure_action();

        return true;
    }

    private function ensure_action(): void
    {
        $existing = ee('Model')->get('Action')
            ->filter('class', 'Delivery_manager')
            ->filter('method', 'accept_delivery')
            ->first();

        if ($existing) {
            return;
        }

        ee('Model')->make('Action', [
            'class' => 'Delivery_manager',
            'method' => 'accept_delivery',
            'csrf_exempt' => false,
        ])->save();
    }
}
