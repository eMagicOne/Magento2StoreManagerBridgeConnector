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

/**
 * Class DeleteFile
 *
 * @author   <outsource@emagicone.com>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
class DeleteFile extends AbstractTask implements TaskInterface
{
    /**
     * {@inheritDoc}
     */
    public function dataValidation()
    {
        if (!isset($this->_requestData['path'])) {
            $this->setCommonResponseData(
                self::ERROR_CODE_COMMON,
                'path is required'
            );
            return false;
        }
        return true;
    }

    /**
     * Accepted parameters:
     * image_id - required
     * entity_type - required
     *
     * {@inheritDoc}
     */
    public function proceed()
    {
        $filePath = $this->getHelper()->getRootDir() . $this->_requestData['path'];
        if (is_file($filePath) && unlink($filePath)) {
            $this->setCommonResponseData(
                self::SUCCESSFUL,
                'File was deleted from FTP Server successfully'
            );
        } else {
            $this->setCommonResponseData(self::ERROR_CODE_COMMON, 'No such file');
        }
    }
}
