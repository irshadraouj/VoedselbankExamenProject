<?php

namespace Dunique\AntwanVanBoheemen\Core\Handlers\Assets;

use Dunique\AntwanVanBoheemen\Core\Config;
use Dunique\AntwanVanBoheemen\Core\Contracts\AssetHandlerInterface;

class WebManifestHandler implements AssetHandlerInterface
{

    public function generate($path): string
    {
        if (!file_exists($path)) {
            return "<!-- Webmanifest file '{$path}' not found! -->";
        }

        // Read and decode manifest file
        $manifestContent = json_decode(file_get_contents($path), true);
        if (!$manifestContent) {
            return "<!-- Invalid webmanifest format! -->";
        }

        // Modify manifest fields dynamically
        $siteName = Config::item('site_name');
        $siteShortName =  Config::item('site_short_name');
        $manifestModified = false;

        if (!isset($manifestContent['name']) || $manifestContent['name'] !== $siteName) {
            $manifestContent['name'] = $siteName;
            $manifestModified = true;
        }

        if (!isset($manifestContent['short_name']) || $manifestContent['short_name'] !== $siteShortName) {
            $manifestContent['short_name'] = $siteShortName;
            $manifestModified = true;
        }

        // If modified, update the manifest file
        if ($manifestModified) {
            file_put_contents($path, json_encode($manifestContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        // Generate the webmanifest link tag
        $tags = "<link rel=\"manifest\" href=\"/{$path}\" />";

        // Add favicon links if available
        if (!empty($manifestContent['icons']) && is_array($manifestContent['icons'])) {
            foreach ($manifestContent['icons'] as $icon) {
                $sizes = $icon['sizes'] ?? 'any';
                $type = $icon['type'] ?? 'image/png';
                $tags .= "<link rel=\"icon\" href=\"{$icon['src']}\" sizes=\"{$sizes}\" type=\"{$type}\" />";
            }
        }

        return $tags;
    }
}
