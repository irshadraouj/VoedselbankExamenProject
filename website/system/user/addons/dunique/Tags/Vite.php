<?php

namespace Dunique\AntwanVanBoheemen\Tags;

use Dunique\AntwanVanBoheemen\Core\Config;
use Dunique\AntwanVanBoheemen\Core\Handlers\Assets;
use Dunique\AntwanVanBoheemen\Core\Factories\AssetFactory;
use ExpressionEngine\Service\Addon\Controllers\Tag\AbstractRoute;

class Vite extends AbstractRoute
{
    // Example usage: {exp:dunique:vite src='resources/main.js|resources/styles.scss'}
    public function process()
    {
        // dd(Config::item('env'), Config::item('vite_port'), Config::all());
        $srcs = ee()->TMPL->fetch_param('src');
        if (!$srcs)
            return;

        $srcs = explode('|', $srcs);
        $isDev = Config::item('vite_dev');
        $forceManifest = ee()->config->item('dunique_vite_force_manifest');
        if ($forceManifest === 'y' || $forceManifest === true || $forceManifest === 1) {
            $isDev = false;
        }
        $base = rtrim(Config::item('base_url'), '/');
        $manifestPath = Config::item('vite_manifest_path');

        if (!file_exists($manifestPath)) {
            return "<!-- Vite manifest not found! -->";
        }

        $manifest = file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : [];

        $tags = '';

        // Add the vite client only once
        if ($isDev && !ee()->session->cache('dunique', 'dunique:vite')) {
            $vitePort = Config::item('vite_port');
            $viteHost = parse_url($base, PHP_URL_HOST) ?: 'localhost';
            $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';
            $viteUrl = "{$scheme}://{$viteHost}:{$vitePort}/@vite/client";
            $tags = "<script type=\"module\" src=\"{$viteUrl}\"></script>";
            ee()->session->set_cache('dunique', 'dunique:vite', 1);
        }

        foreach ($srcs as $src) {

            if (!isset($manifest[$src])) {
                return "<!-- Vite src '/{$src}' not found in manifest! -->";
            }
            
            $assetPath = $manifest[$src]['file'];

            // Check if the asset file exists
            if (!file_exists(FCPATH . $assetPath)) {
                return "<!-- Asset file '{$assetPath}' not found! -->";
            }

         
            // Determine asset type
            $ext = pathinfo($src, PATHINFO_EXTENSION);
            $handler = AssetFactory::create($ext, $base, $isDev, $src);
            if ($handler) {
                $tags .= $handler->generate($assetPath);
            } else {
                $tags .= "<!-- Unsupported asset type: {$ext} -->";
            }
            // // check if js file has css added
            // if (isset($manifest[$src]['css'])) {
            //     foreach ($manifest[$src]['css'] as $css) {
            //         $cssPath = $css;
            //         if (!file_exists(FCPATH . $cssPath)) {
            //             return "<!-- Asset file '{$cssPath}' not found! -->";
            //         }
            //         $handler = AssetFactory::create('css', $base, $isDev, $src);
            //         if ($handler) {
            //             $tags .= $handler->generate($cssPath);
            //         } else {
            //             $tags .= "<!-- Unsupported asset type: css -->";
            //         }
            //     }
            // }

        }
        return $tags;
    }
}
