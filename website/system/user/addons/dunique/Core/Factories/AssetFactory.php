<?php 

namespace Dunique\AntwanVanBoheemen\Core\Factories;

use Dunique\AntwanVanBoheemen\Core\Config;
use Dunique\AntwanVanBoheemen\Core\Handlers\Assets\CssHandler;
use Dunique\AntwanVanBoheemen\Core\Handlers\Assets\FontHandler;
use Dunique\AntwanVanBoheemen\Core\Contracts\AssetHandlerInterface;
use Dunique\AntwanVanBoheemen\Core\Handlers\Assets\JavaScriptHandler;
use Dunique\AntwanVanBoheemen\Core\Handlers\Assets\WebManifestHandler;

class AssetFactory {
  public static function create(string $ext, string $base = '', bool $isDev = false, $src = ''): ?AssetHandlerInterface
  {
      $port = Config::item('vite_port');
      return match ($ext) {
          'js' => new JavaScriptHandler($base, $isDev, $src, $port),
          'css', 'scss' => new CssHandler($base, $isDev, $src, $port),
          'woff', 'woff2', 'ttf', 'otf', 'eot' => new FontHandler( $ext),
          
          'webmanifest' => new WebManifestHandler(),
          default => null,
      };
  }
}