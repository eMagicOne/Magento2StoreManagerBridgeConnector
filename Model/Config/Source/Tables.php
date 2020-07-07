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

namespace Emagicone\Connector\Model\Config\Source;

use Magento\Backup\Model\ResourceModel\Db;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class Tables
 *
 * @author   Vitalii Drozd <vitaliidrozd@kommy.net>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
class Tables implements ArrayInterface
{
    /**
     * @var Db
     */
    private $_resource;

    /**
     * Tables constructor.
     *
     * @param Db $resource
     */
    public function __construct(
        Db $resource
    ) {
        $this->_resource = $resource;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $tables = $this->_resource->getTables();
        $returnArray = [];
        foreach ($tables as $table) {
            $returnArray[] = [
                'value' => $table,
                'label' => $table
            ];
        }
        return $returnArray;
    }
}
