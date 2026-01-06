<?php

namespace Dunique\AntwanVanBoheemen\Core;

class Config
{

    private static $settings  = [];
    private static bool $isLoaded = false;
    public static function load() :void
    {
        if (self::$isLoaded) {
            return;
        }
        // Static settings
        self::$settings = [
            'env' => self::global('dunique_env','production'), // always asume its production
            'base_url' => ee()->config->item('base_url'),
            'base_path' => ee()->config->item('base_path'),
            'site_name' => ee()->config->item('site_name'),
            'site_short_name' => ee('Format')->make('Text', ee()->config->item('site_name'))->urlSlug()->compile(),
            'vite_port' => self::override('dunique_vite_port', 5173),
            'vite_manifest_path' => FCPATH.self::override('dunique_vite_manifest_path','assets/manifest.json'),
        ];

        // Dynamic settings
        self::$settings += [
            'vite_dev' =>  self::isDevServerRunning(),
        ];
        
        self::$isLoaded = true;
    }

    public static function global(string $key, mixed $default = null): mixed
    {
        return ee()->config->_global_vars[$key] ?? $default;
    }

    // Method to dynamically generate the 'vite_dev' setting
    public static function isDevServerRunning(): bool
    {
        $port = self::$settings['vite_port'];
        return @fsockopen('localhost', $port) !== false;
    }
    public static function override(string $key, mixed $default): mixed
    {
        return ee()->config->item($key) ?: $default;
    }

    public static function all(): mixed
    {
        self::load();
        return self::$settings ?? null;
    }

    public static function item($key): mixed
    {
        self::load();
        return self::$settings[$key] ?? null;
    }
}

// EOF