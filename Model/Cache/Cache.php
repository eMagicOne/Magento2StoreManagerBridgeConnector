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

namespace Emagicone\Connector\Model\Cache;

use Emagicone\Connector\Helper\Data;
use Emagicone\Connector\Logger\Logger;
use Emagicone\Connector\Model\AbstractModel;
use Magento\Framework\App\Cache\TypeListInterface;

/**
 * Class Cache
 */
class Cache extends AbstractModel
{
    /**
     * @var TypeListInterface
     */
    private $_cacheList;

    /**
     * Cache constructor.
     *
     * @param TypeListInterface $cacheList
     * @param Logger $logger
     * @param Data $helper
     */
    public function __construct(
        TypeListInterface $cacheList,
        Logger $logger,
        Data $helper
    ) {
        $this->_cacheList = $cacheList;
        parent::__construct($logger, $helper);
    }

    /**
     * @return array
     */
    public function getList()
    {
        return $this->_cacheList->getTypes();
    }

    /**
     * @param $typeCode
     * @return bool
     */
    public function clearCache($typeCode)
    {
        if (array_key_exists($typeCode, $this->getList())) {
            $this->_cacheList->cleanType($typeCode);
            return true;
        }
        return false;
    }
}
