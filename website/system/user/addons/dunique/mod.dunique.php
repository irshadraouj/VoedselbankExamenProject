<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use ExpressionEngine\Service\Addon\Module;
use ExpressionEngine\Library\String\Str;

class Dunique extends Module
{
    protected $addon_name = 'dunique';

     /**
     * @param string $method
     * @param bool $action
     * @return string
     * @throws ControllerException
     */
    protected function buildObject($method, $action = false, $useModuleFolder = true)
    {
        $object = '\\' . $this->getRouteNamespace() . '\\';
        if ($action) {
            $object .= 'Actions\\';
        } else {
            $object .= 'Tags\\';
        }

        $object .= Str::studly($method);

        return $object;
    }
}
