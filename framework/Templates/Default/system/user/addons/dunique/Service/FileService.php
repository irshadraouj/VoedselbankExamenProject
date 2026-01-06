<?php
namespace Dunique\AntwanVanBoheemen\Service;

use ExpressionEngine\Model\File\File;

/**
 * Handles file retrieval logic for ExpressionEngine files.
 */
class FileService
{

    /**
     * Retrieve a file by its name.
     *
     * @param string $fileName
     * @return File|null
     */
    public function getFileByName(string $fileName): ?File
    {
        $retrievedFiles = ee()->session->cache('dunique', 'retrieved_files');
        if ($retrievedFiles === false) {
            $retrievedFiles = [];
        }
        if (isset($retrievedFiles[$fileName])) {
            return $retrievedFiles[$fileName];
        }
        $retrievedFiles[$fileName] = ee('Model')->get('File')
            ->with('UploadDestination', 'UploadAuthor', 'ModifyAuthor')
            ->filter('site_id', 'IN', [ee()->config->item('site_id'), 0])
            ->filter('file_name', $fileName)
            ->first();
        if ($retrievedFiles[$fileName] !== null) {
            ee()->session->set_cache('dunique', 'retrieved_files', $retrievedFiles);
        }
        return $retrievedFiles[$fileName];
    }
    /**
     * Retrieve a file by its URL.
     *
     * @param string $url
     * @return File|null
     */
    public function getFileByUrl(string $url): ?File
    {
        $fileName = basename($url);
        return $this->getFileByName($fileName);
    }
}
