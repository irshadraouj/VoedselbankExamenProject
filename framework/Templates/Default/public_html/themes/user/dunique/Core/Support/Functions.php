<?php

if (!function_exists('dunique')) {
    /**
     * Global helper function for creating Response instances.
     */
    function dunique($key)
    {
        $reigistry = [
          'Response' => Dunique\AntwanVanBoheemen\Core\Http\Response::class
        ];
        return new $reigistry[$key]();
    }
}