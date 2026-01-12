<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Delivery_manager
{
    /**
     * Template tag: outputs the action URL for the accept_delivery action.
     * Usage: {exp:delivery_manager:action_url}
     */
    public function action_url()
    {
        $action_id = ee()->functions->fetch_action_id('Delivery_manager', 'accept_delivery');

        if (! $action_id) {
            return '';
        }

        return ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . $action_id;
    }
}
