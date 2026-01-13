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

        $this->ensure_actions();

        return true;
    }

    public function update($current = '')
    {
        parent::update($current);

        $this->ensure_actions();

        return true;
    }

    private function ensure_actions(): void
    {
        // accept_delivery is triggered from a front-end template; templates can be cached which may invalidate CSRF tokens.
        // We still require a logged-in session in the Action itself.
        $this->ensure_action('accept_delivery', true);
        // create_delivery is used from a front-end modal; templates can be cached which may invalidate CSRF tokens.
        // We still require a logged-in session in the Action itself.
        $this->ensure_action('create_delivery', true);
    }

    private function ensure_action(string $method, bool $csrf_exempt): void
    {
        $existing = ee('Model')->get('Action')
            ->filter('class', 'Delivery_manager')
            ->filter('method', $method)
            ->first();

        if ($existing) {
            if ((bool) $existing->csrf_exempt !== $csrf_exempt) {
                $existing->csrf_exempt = $csrf_exempt;
                $existing->save();
            }
            return;
        }

        ee('Model')->make('Action', [
            'class' => 'Delivery_manager',
            'method' => $method,
            'csrf_exempt' => $csrf_exempt,
        ])->save();
    }
}
