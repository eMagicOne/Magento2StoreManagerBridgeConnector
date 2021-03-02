<?php
/**
 *    This file is part of Magento Store Manager Connector.
 *
 *   Magento Store Manager Connector is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   Magento Store Manager Connector is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with Magento Store Manager Connector.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Emagicone\Connector\Model\FileSystem;

use Emagicone\Connector\Exception\BridgeConnectorException;
use Emagicone\Connector\Helper\Data;
use Emagicone\Connector\Logger\Logger;
use Emagicone\Connector\Model\AbstractModel;
use Exception;
use Magento\Framework\Archive\Gz;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Phrase;
use Magento\MediaStorage\Model\File\UploaderFactory;
use ZipArchive;

/**
 * Class FileSystem
 *
 * @author   <outsource@emagicone.com>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
class FileSystem extends AbstractModel
{
    const FILE_FILED_ID = 'file';
    const FILE_DEFAULT_SEARCH_MASK = '*.*';

    /**
     * @var UploaderFactory
     */
    private $_fileUploader;

    /**
     * @var File
     */
    private $_file;

    /**
     * FileSystem constructor.
     *
     * @param UploaderFactory $fileUploader
     * @param File $file
     * @param Logger $logger
     * @param Data $helper
     */
    public function __construct(
        UploaderFactory $fileUploader,
        File $file,
        Logger $logger,
        Data $helper
    ) {
        $this->_fileUploader = $fileUploader;
        $this->_file = $file;
        parent::__construct(
            $logger,
            $helper
        );
    }

    /**
     * @param string $directoryToScanPath
     * @param string $excludedDirectories
     * @param string|null $excludeMask
     * @param bool $includeSubdirectories
     * @param string|null $searchMask
     * @return array
     */
    public function getDirectoryFiles(
        $directoryToScanPath,
        $excludedDirectories = '',
        $excludeMask = null,
        $includeSubdirectories = true,
        $searchMask = null
    ) {
        return $this->_scanDirectory(
            $directoryToScanPath,
            false,
            $this->_getExcludedDirectoriesArrayFromString($excludedDirectories),
            true,
            $excludeMask,
            $includeSubdirectories,
            $searchMask ?? self::FILE_DEFAULT_SEARCH_MASK
        );
    }

    /**
     * @param string $directoryToScanPath
     * @param bool $includeHidden
     * @param array $excludedDirectories
     * @param bool $excludeInSubdirectories
     * @param string|null $excludeMask
     * @param bool $includeSubdirectories
     * @param string $searchMask
     * @param array $result
     * @return array
     */
    private function _scanDirectory(
        $directoryToScanPath,
        $includeHidden,
        $excludedDirectories,
        $excludeInSubdirectories,
        $excludeMask,
        $includeSubdirectories,
        $searchMask = self::FILE_DEFAULT_SEARCH_MASK,
        &$result = []
    ) {
        if ($includeSubdirectories === true) {
            if (!$includeHidden) {
                $directories = glob($directoryToScanPath . '/*', GLOB_ONLYDIR);
            } else {
                // Get all directories(include hidden)
                $directories = glob("$directoryToScanPath/{,.}*[!.]", GLOB_BRACE | GLOB_ONLYDIR);
            }
            foreach ($directories as $key => $directory) {
                $ignorePath = $excludeInSubdirectories ? basename($directory) : $directory;
                if (!$this->_isDirectoryExcluded($ignorePath, $excludedDirectories)) {
                    $this->_scanDirectory(
                        $directory,
                        $includeHidden,
                        $excludedDirectories,
                        $excludeInSubdirectories,
                        $excludeMask,
                        $includeSubdirectories,
                        $searchMask,
                        $result
                    );
                }
            }
        }
        if (!$includeHidden) {
            // Get all files from directory(include hidden, and files without extensions)
            $result = array_merge(glob($directoryToScanPath . '/' . $searchMask), $result);
        } else {
            $result = array_merge(array_filter(glob("$directoryToScanPath/{,.}*[!.]", GLOB_BRACE), 'is_file'), $result);
        }
        if ($excludeMask !== null) {
            $exclude = glob($directoryToScanPath . '/' . $excludeMask);
            $result = array_diff($result, $exclude);
        }
        return $result;
    }

    /**
     * @param string $directory
     * @param array $excludedDirectories
     * @return bool
     */
    private function _isDirectoryExcluded($directory, $excludedDirectories)
    {
        return in_array($directory, $excludedDirectories, true);
    }

    /**
     * @param $fileName
     * @param $data
     * @return $this
     * @throws FileSystemException
     */
    public function saveDataToFile($fileName, $data)
    {
        $directoryToSave = $this->_helper->getSaveDir();
        $filePath = $directoryToSave . '/' . $fileName;
        file_put_contents($filePath, $data);
        return $this;
    }

    /**
     * @param string $ignoredPaths
     * @return string
     * @throws BridgeConnectorException
     * @throws FileSystemException
     */
    public function getStoreFileArchive($ignoredPaths)
    {
        $ignoredPaths .= ';/var/tmp';
        $storePath = '.';
        $excludedDirectories = $this->_getExcludedDirectoriesArrayFromString($ignoredPaths);
        foreach ($excludedDirectories as $index => $directory) {
            $excludedDirectories[$index] = "{$storePath}/$directory";
        }
        $zipPath = $this->_helper->getSaveDir() . '/store_archive.zip';
        if (is_file($zipPath)) {
            unlink($zipPath);
        }
        $zipPacker = new ZipArchive();
        $files = $this->_scanDirectory($storePath, true, $excludedDirectories, false, null, true);
        $zipPacker->open($zipPath, ZipArchive::CREATE);
        foreach ($files as $file) {
            $file = str_replace('./', '', $file);
            if (file_exists($file) && is_file($file)) {
                $zipPacker->addFile($file);
            }
        }
        $zipPacker->close();
        if (!is_file($zipPath) || filesize($zipPath) == 0) {
            throw new BridgeConnectorException(
                new Phrase('Failed to create store backup')
            );
        }
        return $zipPath;
    }

    /**
     * @param $fileName
     * @param string $extension
     * @return string
     * @throws FileSystemException
     */
    public function compressFile($fileName, $extension = '.gz')
    {
        $filePath = $this->_helper->getSaveDir() . '/' . $fileName;
        $archiveFilePath = $this->_helper->getSaveDir() . '/' . $fileName . $extension;
        $f = fopen($archiveFilePath, 'w');
        fclose($f);

        // archive into *.gz file created earlier
        $gzPacker = new Gz();
        $gzPacker->pack(
            $filePath,
            $archiveFilePath
        );
        unlink($filePath);
        return $archiveFilePath;
    }

    /**
     * File data response
     * used in: get_sql, get_category_tree, get_file_list
     * 0 - Successful response code
     * 1|2|1874133|latin1 - IsCompressed|CountFragments|FullArchiveSize|DBCharset(if requested)
     * filename.extension - FragmentedFileName
     * 092dd6f98d019f3979a88c9b59a70558 - FragmentedHashFileName
     *
     * @param $filePath
     * @param bool $isCompressed
     * @param null $dbCharset
     * @return string
     */
    public function getFileDataResponse($filePath, $isCompressed = false, $dbCharset = null)
    {
        $explodedPath = explode('/', $filePath);
        $fileName = end($explodedPath);
        $fileChecksum = md5_file($filePath);
        $fileSize = filesize($filePath); // file size in bytes

        $outputData = "0\r\n";
        $outputData .= (int)$isCompressed . '|';
        $packageSize = $this->_helper->getPackageSize();
        $outputData .= ceil(($fileSize / 1024) / $packageSize);
        $outputData .= '|' . $fileSize;
        if ($dbCharset !== null) {
            $outputData .= '|' . $dbCharset;
        }
        $outputData .= "\r\n$fileName\r\n$fileChecksum\r\n";

        return $outputData;
    }

    /**
     * @param $filePath
     * @return bool|string
     */
    public function getFile($filePath)
    {
        if (file_exists($filePath)) {
            $f = fopen($filePath, 'r');
            $fileContent = fread($f, filesize($filePath));
            fclose($f);
            return $fileContent;
        }
        return false;
    }

    /**
     * @param string $destinationFolder
     * @throws FileSystemException
     */
    private function _checkDirectoryExists($destinationFolder)
    {
        if (!is_dir($destinationFolder)) {
            $this->_file->createDirectory($destinationFolder);
        }
    }

    /**
     * @param $destinationFolder
     * @param $fileName
     * @param string $fieldId
     * @return bool
     * @throws FileSystemException
     */
    public function uploadFile($destinationFolder, $fileName, $fieldId = self::FILE_FILED_ID)
    {
        $this->_checkDirectoryExists($destinationFolder);
        try {
            $file = $this->_fileUploader->create(['fileId' => $fieldId]);
            $file->save($destinationFolder, $fileName);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * @param $url
     * @param $destinationFile
     * @return bool
     * @throws FileSystemException
     */
    public function uploadFileFromUrl($url, $destinationFile)
    {
        $this->_checkDirectoryExists(dirname($destinationFile));
        return (bool)file_put_contents($destinationFile, file_get_contents($url));
    }

    /**
     * @param string $excludedDirectoriesString
     * @return array
     */
    private function _getExcludedDirectoriesArrayFromString($excludedDirectoriesString)
    {
        if (!empty($excludedDirectoriesString)) {
            $excludedDirectories = array_filter(explode(';', $excludedDirectoriesString));
            foreach ($excludedDirectories as $index => $directory) {
                $excludedDirectories[$index] = ltrim($directory, '/');
            }
            return $excludedDirectories;
        }
        return [];
    }
}
