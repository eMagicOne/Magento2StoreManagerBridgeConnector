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
 * Class ClearCache
 *
 * @author   <outsource@emagicone.com>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
class ClearCache extends AbstractTask implements TaskInterface
{
    /**
     * {@inheritDoc}
     */
    public function dataValidation()
    {
        if (!isset($this->_requestData['cache_type'])) {
            $this->setCommonResponseData(
                self::ERROR_CODE_COMMON,
                'Request parameter "cache_type" is missing'
            );
            return false;
        }
        return true;
    }

    /**
     * Accepted parameters:
     * cache_type -  required, cache types imploded by ';'
     *
     * {@inheritDoc}
     * @throws BridgeConnectorException
     */
    public function proceed()
    {
        $model = $this->_modelResolver->create('cache');
        $cacheTypes = explode(';', $this->_requestData['cache_type']);
        foreach ($cacheTypes as $key => $cacheType) {
            if ($model->clearCache($cacheType)) {
                continue;
            }
            unset($cacheTypes[$key]);
        }
        $this->setCommonResponseData(
            self::SUCCESSFUL,
            implode(', ', $cacheTypes) . ' refreshed'
        );
    }
}
