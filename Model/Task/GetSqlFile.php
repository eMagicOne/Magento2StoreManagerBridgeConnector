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

namespace Emagicone\Connector\Model\Task;

use Emagicone\Connector\Api\TaskInterface;
use Magento\Framework\Exception\FileSystemException;

/**
 * Class GetSqlFile
 *
 * @author   <outsource@emagicone.com>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
class GetSqlFile extends AbstractTask implements TaskInterface
{
    /**
     * {@inheritDoc}
     * @throws FileSystemException
     */
    public function dataValidation()
    {
        if (!isset($this->_requestData['filename'])) {
            $this->_setResponseError('Request parameter "filename" is missing');
            return false;
        } elseif (!file_exists($this->getHelper()->getSaveDir() . '/' . $this->_requestData['filename'])) {
            $this->_setResponseError('Temporary File doesn\'t exist');
            return false;
        } elseif (!is_readable($this->getHelper()->getSaveDir() . '/' . $this->_requestData['filename'])) {
            $this->_setResponseError('Temporary File isn\'t readable');
            return false;
        } elseif (!isset($this->_requestData['position'])) {
            $this->_setResponseError('Request parameter "position" is missing');
            return false;
        }
        return true;
    }

    /**
     * Accepted parameters:
     * filename - required
     * position - required
     *
     * {@inheritDoc}
     * @throws FileSystemException
     */
    public function proceed()
    {
        $fileName = $this->getHelper()->getSaveDir() . '/' . $this->_requestData['filename'];
        $position = $this->_requestData['position'];
        $output = '';
        $packageSize = $this->getHelper()->getPackageSize() * 1024;
        $fileSize = filesize($fileName);
        $fileSize = $fileSize - ($position * $packageSize);

        if ($fileSize > $packageSize) {
            $fileSize = $packageSize;
        }

        if ($fileSize < 0) {
            $fileSize = 0;
        }

        $file = fopen($fileName, 'rb');
        fseek($file, $packageSize * $position);
        $output .= fread($file, $packageSize);
        fclose($file);
        if ($fileSize < $packageSize) {
            unlink($this->getHelper()->getSaveDir() . '/' . $this->_requestData['filename']);
        }
        header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
        header( 'Pragma: no-cache' );
        $this->setResponseData($output);
    }

    /**
     * @param $message
     */
    private function _setResponseError($message)
    {
        $this->setCommonResponseData(
            self::ERROR_CODE_COMMON,
            $message
        );
    }
}
