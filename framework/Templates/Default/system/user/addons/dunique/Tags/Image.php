<?php
namespace Dunique\AntwanVanBoheemen\Tags;

use ExpressionEngine\Service\Addon\Controllers\Tag\AbstractRoute;
use Dunique\AntwanVanBoheemen\Core\Handlers\ImageHandler;

class Image extends AbstractRoute
{
    public function process()
    {
        $params = ee()->TMPL->tagparams;

        // Retrieve params
        $src = $params['src'] ?? null;
        $class = $params['class'] ?? null;
        $width = $params['width'] ?? null;
        $height = $params['height'] ?? null;
        $resizeType = $params['resize_type'] ?? null;
        $position = $params['position'] ?? null;
        $lazy = strtolower($params['lazy'] ?? 'y');
        $isLcp = strtolower($params['lcp'] ?? 'n') === 'y';
        $urlOnly = isset($params['url_only']);
        $noSrcset = isset($params['no_srcset']);
        $noSizes = isset($params['no_sizes']);

        if (!$src) {
            return '';
        }

        // Retrieve file by URL
        $file = ee('dunique:File')->getFileByUrl($src);
        if (!$file) {
            $file = $this->getFileByUrl($src);
        }
        if (!$file) {
            return '';
        }

        $isSvg = strtolower(pathinfo($file->file_name, PATHINFO_EXTENSION)) === 'svg';
        $alt = $params['alt'] ?? $file->description;

        if (!$isSvg) {
            $path = rtrim($file->UploadDestination->server_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file->file_name;

            // Get original dimensions if needed
            if (file_exists($path)) {
                [$originalWidth, $originalHeight] = getimagesize($path);

                if ($width && !$height) {
                    $height = round(($width / $originalWidth) * $originalHeight);
                } elseif ($height && !$width) {
                    $width = round(($height / $originalHeight) * $originalWidth);
                } elseif (!$width && !$height) {
                    $width = $originalWidth;
                    $height = $originalHeight;
                }
            }

            $imageHandler = new ImageHandler();
            $fullPath = $imageHandler->manipulate($path, [
                'width' => $width,
                'height' => $height,
                'resize_type' => $resizeType,
                'position' => $position,
            ]);

            $fullUrl = str_replace(ee()->config->item('base_path'), '', $fullPath);
            if (!str_starts_with($fullUrl, '/')) {
                $fullUrl = '/' . $fullUrl;
            }

        } else {
            $fullUrl = $src;
            $svgPath = $_SERVER['DOCUMENT_ROOT'] . parse_url($src, PHP_URL_PATH);

            if (file_exists($svgPath)) {
                $svgContent = file_get_contents($svgPath);
                if ($svgContent) {
                    $svg = @simplexml_load_string($svgContent);
                    if ($svg !== false) {
                        $attributes = $svg->attributes();
                        if (!$width && isset($attributes->width)) {
                            $width = (string) $attributes->width;
                        }
                        if (!$height && isset($attributes->height)) {
                            $height = (string) $attributes->height;
                        }

                        if ((!$width || !$height) && isset($attributes->viewBox)) {
                            $viewBox = preg_split('/[\s,]+/', (string) $attributes->viewBox);
                            if (count($viewBox) === 4) {
                                list(, , $vbWidth, $vbHeight) = $viewBox;
                                if (!$width) {
                                    $width = $vbWidth;
                                }
                                if (!$height) {
                                    $height = $vbHeight;
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($urlOnly) {
            return $fullUrl;
        }

        return $this->generateImageTag($fullUrl, $class, $width, $height, $alt, $lazy, $isLcp, $urlOnly, $noSrcset, $noSizes, $isSvg);
    }

    protected function generateImageTag($url, $class, $width, $height, $alt, $lazy, $isLcp, $urlOnly, $noSrcset, $noSizes, $isSvg)
    {
        if ($urlOnly) {
            return $url;
        }

        $attributes = [
            'src' => $url,
            'alt' => $alt ? htmlspecialchars($alt) : '',
        ];

        if ($width && is_numeric($width)) {
            $attributes['width'] = (int)$width;
        }

        if ($height && is_numeric($height)) {
            $attributes['height'] = (int)$height;
        }

        if ($class && $class !== 'y') {
            $attributes['class'] = $class;
        }

        if ($lazy === 'y') {
            $attributes['loading'] = 'lazy';
        }

        if ($isLcp) {
            $attributes['fetchpriority'] = 'high';
            $attributes['loading'] = 'eager';
        }

        if ($noSrcset) {
            unset($attributes['srcset']);
        }

        if ($noSizes) {
            unset($attributes['sizes']);
        }

        return '<img ' . implode(' ', array_map(function ($key, $value) {
            return $key . '="' . $value . '"';
        }, array_keys($attributes), $attributes)) . '>';
    }

    protected function getFileByUrl($url)
    {
        $urlPath = parse_url($url, PHP_URL_PATH);
        $filePath = $_SERVER['DOCUMENT_ROOT'] . $urlPath;

        if (file_exists($filePath)) {
            $file = new \stdClass();
            $file->file_name = basename($filePath);
            $file->description = '';
            $file->UploadDestination = new \stdClass();
            $file->UploadDestination->server_path = dirname($filePath);
            return $file;
        }

        return null;
    }
}
