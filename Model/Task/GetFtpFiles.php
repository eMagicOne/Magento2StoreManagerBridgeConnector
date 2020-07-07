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
use Emagicone\Connector\Exception\BridgeConnectorException;

/**
 * Class GetFtpFiles
 *
 * @author   Vitalii Drozd <vitaliidrozd@kommy.net>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
class GetFtpFiles extends AbstractTask implements TaskInterface
{
    /**
     * {@inheritDoc}
     */
    public function dataValidation()
    {
        if (!isset($this->_requestData['search_path'])) {
            $this->setCommonResponseData(
                self::ERROR_CODE_COMMON,
                'search_path is required'
            );
            return false;
        }
        return true;
    }

    /**
     * Accepted parameters:
     * search_path - required
     * ignore_dir - optional
     * include_subdir (1/0) - optional
     * mask - optional
     *
     * {@inheritDoc}
     * @throws BridgeConnectorException
     */
    public function proceed()
    {
        $model = $this->_modelResolver->create('filesystem');
        $result = $model->getDirectoryFiles(
            $this->_requestData['search_path'],
            $this->_requestData['ignore_dir'] ?? null,
            null,
            (bool)$this->_requestData['include_subdir'],
            !empty($this->_requestData['mask']) ? $this->_requestData['mask'] : null
        );

        $this->setCommonResponseData(
            self::SUCCESSFUL,
            $result
        );
    }
}
