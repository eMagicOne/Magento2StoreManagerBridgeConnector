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

use Emagicone\Connector\Helper\Data;
use Emagicone\Connector\Logger\Logger;

/**
 * Class AbstractModel
 * @author   <outsource@emagicone.com>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
abstract class AbstractModel
{
    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * AbstractModel constructor.
     *
     * @param Logger $logger
     * @param Data $helper
     */
    public function __construct(
        Logger $logger,
        Data $helper
    ) {
        $this->_logger = $logger;
        $this->_helper = $helper;
    }
}
