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
        // If the file is not found in the ExpressionEngine filesystem, fetch it from the URL directly
        if (!$file) {
            // Attempt to get file from URL if not found in the ExpressionEngine filesystem
            $file = $this->getFileByUrl($src);
        }
        if (!$file) {
            return ''; // or return an error message, e.g., return 'File not found';
        }

        $isSvg = strtolower(pathinfo($file->file_name, PATHINFO_EXTENSION)) === 'svg';

        
        $alt = $params['alt'] ?? $file->description;
        
        if (!$isSvg) {
            // Ensure the file path is correct
            $path = rtrim($file->UploadDestination->server_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file->file_name;
            // Initialize ImageHandler
            $imageHandler = new ImageHandler();

            // Manipulate image (resize or position, etc.)
            $fullPath = $imageHandler->manipulate($path, [
                'width' => $width,
                'height' => $height,
                'resize_type' => $resizeType,
                'position' => $position,
            ]);

            // Determine the final URL for the image
            $fullUrl = str_replace(ee()->config->item('base_path'), '', $fullPath);
            if (!str_starts_with($fullUrl, '/')) {
                $fullUrl = '/' . $fullUrl;
            }
            
        } else {
            // For SVG, extract width and height from the SVG file
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
        
                        // Handle viewBox as fallback if width/height not defined
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
        // Return the image tag or URL based on the tag's params
        return $this->generateImageTag($fullUrl,$class, $width, $height, $alt, $lazy, $isLcp, $urlOnly, $noSrcset, $noSizes, $isSvg);
    }

    protected function generateImageTag($url, $class, $width, $height, $alt, $lazy, $isLcp, $urlOnly, $noSrcset, $noSizes, $isSvg)
    {
        // If only the URL is requested
        if ($urlOnly) {
            return $url;
        }

        // Initialize image tag attributes
        $attributes = [
            'src' => $url,
            'alt' => $alt ? htmlspecialchars($alt) : '',
        ];

        // If width and height are provided, add them
        if ($width && is_numeric($width)) {
            $attributes['width'] = (int)$width;
        }

        if ($height && is_numeric($height)) {
            $attributes['height'] = (int)$height;
        }
        if ($class === 'y') {
            $attributes['class'] = $class;
        }
        // Lazy loading
        if ($lazy === 'y') {
            $attributes['loading'] = 'lazy';
        }

        // LCP (Largest Contentful Paint) - high priority loading
        if ($isLcp) {
            $attributes['fetchpriority'] = 'high';
            $attributes['loading'] = 'eager';
        }

        // Remove srcset if 'no_srcset' is set
        if ($noSrcset) {
            unset($attributes['srcset']);
        }

        // Remove sizes if 'no_sizes' is set
        if ($noSizes) {
            unset($attributes['sizes']);
        }

        // Generate and return the <img> tag
        return '<img ' . implode(' ', array_map(function ($key, $value) {
            return $key . '="' . $value . '"';
        }, array_keys($attributes), $attributes)) . '>';
    }
        // New method to fetch file by URL
        protected function getFileByUrl($url)
        {
            // Get the file directly from the URL
            $urlPath = parse_url($url, PHP_URL_PATH);
            $filePath = $_SERVER['DOCUMENT_ROOT'] . $urlPath;
    
            if (file_exists($filePath)) {
                // Return a mock file object with necessary details
                // (You may need to adjust the file handling depending on your system's requirements)
                $file = new \stdClass();
                $file->file_name = basename($filePath);
                $file->description = '';
                $file->UploadDestination = new \stdClass();
                $file->UploadDestination->server_path = dirname($filePath);
                return $file;
            }
    
            return null; // If the file isn't found
        }
}
