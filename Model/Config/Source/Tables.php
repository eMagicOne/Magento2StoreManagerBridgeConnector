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
use Emagicone\Connector\Helper\Data;
use Emagicone\Connector\Helper\GlobalConstants;
/**
 * Class Tables
 *
 * @author   <outsource@emagicone.com>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
class Tables implements ArrayInterface
{
    /**
     * @var Db
     */
    private $_resource;

    private $_helper;

    /**
     * Tables constructor.
     *
     * @param Db $resource
     * @param Data $_helper
     */
    public function __construct(
        Db $resource,
        Data $_helper
    ) {
        $this->_resource = $resource;
        $this->_helper = $_helper;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $tables = $this->filterTables($this->_resource->getTables());
        $returnArray = [];
        foreach ($tables as $table) {
            $returnArray[] = [
                'value' => $table,
                'label' => $table
            ];
        }
        return $returnArray;
    }

    private function filterTables($tables)
    {
        $requiredTables = $this->_helper->getRequiredTables();
        $requiredTablesRegexp = $this->_helper->getRequiredTablesRegexp();
        $tables = array_diff($tables, $requiredTables);
        foreach ($requiredTablesRegexp as $regexp) {
            $tables = preg_grep($regexp, $tables, PREG_GREP_INVERT);
        }
        return $tables;
    }
}
