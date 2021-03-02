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

namespace Emagicone\Connector\Model;

use Emagicone\Connector\Exception\BridgeConnectorException;

/**
 * Class Resolver
 *
 * @author   <outsource@emagicone.com>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
class Resolver
{
    /**
     * @var array
     */
    private $_modelsPool;

    /**
     * Resolver constructor.
     *
     * @param array $modelsPool
     */
    public function __construct(
        array $modelsPool
    ) {
        $this->_modelsPool = $modelsPool;
    }

    /**
     * @param string $modelName
     * @return mixed
     * @throws BridgeConnectorException
     */
    public function create(string $modelName)
    {
        if (array_key_exists($modelName, $this->_modelsPool)) {
            return $this->_modelsPool[$modelName]->create();
        }
        throw new BridgeConnectorException(__('Model %1 not found', $modelName));
    }
}
