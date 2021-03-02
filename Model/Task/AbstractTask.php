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

use Emagicone\Connector\Helper\Data;
use Emagicone\Connector\Logger\Logger;
use Emagicone\Connector\Model\Resolver;

/**
 * Class AbstractTask
 *
 * @author   <outsource@emagicone.com>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
abstract class AbstractTask
{
    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * @var Data
     */
    private $_helper;

    /**
     * @var Resolver
     */
    protected $_modelResolver;

    /**
     * @var array
     */
    private $_responseData;

    /**
     * @var array
     */
    protected $_requestData;

    /**
     * AbstractTask constructor.
     *
     * @param Logger $logger
     * @param Data $helper
     * @param Resolver $modelResolver
     */
    public function __construct(
        Logger $logger,
        Data $helper,
        Resolver $modelResolver
    ) {
        $this->_logger = $logger;
        $this->_helper = $helper;
        $this->_modelResolver = $modelResolver;
    }

    /**
     * {@inheritDoc}
     */
    public function setCommonResponseData($responseCode, $message)
    {
        $this->setResponseData(
            [
                'response_code' => $responseCode,
                'message' => $message
            ]
        );
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getHelper()
    {
        return $this->_helper;
    }

    /**
     * {@inheritDoc}
     */
    public function setRequestData($data)
    {
        $this->_requestData = $data;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getRequestData()
    {
        return $this->_requestData;
    }

    /**
     * {@inheritDoc}
     */
    public function setResponseData($data)
    {
        $this->_responseData = $data;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getResponseData()
    {
        return $this->_responseData;
    }
}
