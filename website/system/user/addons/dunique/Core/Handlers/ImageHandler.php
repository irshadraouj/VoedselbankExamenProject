<?php

namespace Dunique\AntwanVanBoheemen\Core\Handlers;

require_once PATH_THIRD . 'dunique/vendor/autoload.php';

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

class ImageHandler
{
    protected $manager;
    protected array $usedImages = [];
    protected string $cacheKey = 'image_usage';
    protected string $cacheNamespace = 'Dunique/image_handler';

    public function __construct()
    {
        $resizeProtocol = ee()->config->item('image_resize_protocol');
        $this->manager = $this->getImageManagerByProtocol($resizeProtocol);
    }

    protected function getImageManagerByProtocol($protocol): ImageManager
    {
        return match ($protocol) {
            'imagick' => ImageManager::withDriver(new ImagickDriver()),
            'gd'      => ImageManager::withDriver(new GdDriver()),
            default   => ImageManager::withDriver(new GdDriver()),
        };
    }

    public function manipulate(string $path, array $options = []): string
    {
        if (!$path || !file_exists($path)) return '';

        $width           = isset($options['width']) && $options['width'] > 0 ? (int) $options['width'] : 100;
        $height          = isset($options['height']) && $options['height'] > 0 ? (int) $options['height'] : 100;
        $resizeType      = $options['resize_type'] ?? 'cover';
        $position        = $options['position'] ?? 'center';
        $backgroundColor = $options['background_color'] ?? 'transparent';

        $srcDir   = dirname($path);
        $srcName  = pathinfo($path, PATHINFO_FILENAME);
        $cacheDir = $srcDir . '/_cache';

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $normalizedOptions = $options;
        ksort($normalizedOptions);
        $hashInput = json_encode([
            'src'     => realpath($path),
            'options' => $normalizedOptions,
            'mtime'   => filemtime($path),
        ]);
        $hash = $srcName . '_' . md5($hashInput);
        $cachedFile = $cacheDir . '/' . $hash . '.webp';

        if (!file_exists($cachedFile)) {
            $image = $this->manager->read($path);

            match ($resizeType) {
                'contain' => $image->contain(width: $width, height: $height, background: $backgroundColor, position: $position),
                'resize'  => $image->resize(width: $width, height: $height),
                'scale'   => $image->scaleDown(width: $width, height: $height),
                default   => $image->cover(width: $width, height: $height, position: $position),
            };

            $image->toWebp(60)->save($cachedFile);
        }

        return $cachedFile;
    }

}