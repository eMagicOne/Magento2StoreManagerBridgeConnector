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
use Emagicone\Connector\Helper\Data;
use Emagicone\Connector\Logger\Logger;
use Emagicone\Connector\Model\Resolver;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\HTTP\PhpEnvironment\RequestFactory;

/**
 * Class SetFile
 *
 * @author   <outsource@emagicone.com>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
class SetFile extends AbstractTask implements TaskInterface
{
    /**
     * @var RequestFactory
     */
    protected $_requestFactory;

    public function __construct(
        Logger $logger,
        Data $helper,
        Resolver $modelResolver,
        RequestFactory $requestFactory
    ) {
        $this->_requestFactory = $requestFactory;
        parent::__construct($logger, $helper, $modelResolver);
    }

    /**
     * {@inheritDoc}
     */
    public function dataValidation()
    {
        if (!isset($this->_requestData['filename'])) {
            $this->setCommonResponseData(
                self::ERROR_CODE_COMMON,
                'filename is required'
            );
            return false;
        }
        if (!isset($this->_requestData['entity_type'])) {
            $this->setCommonResponseData(
                self::ERROR_CODE_COMMON,
                'entity_type is required'
            );
            return false;
        }
        if ($this->_requestFactory->create()->getFiles()->toArray() === []) {
            $this->setCommonResponseData(
                self::ERROR_CODE_COMMON,
                '$_FILES is empty'
            );
            return false;
        }
        return true;
    }

    /**
     * {@inheritDoc}
     * @throws BridgeConnectorException
     * @throws FileSystemException
     */
    public function proceed()
    {
        $model = $this->_modelResolver->create('filesystem');
        $destinationFolder = $this->_requestData['entity_type'] . '/' . $this->_requestData['filename'];
        $destinationFolder = str_replace('/' . $this->_requestData['filename'], '', $destinationFolder);
        $result = $model->uploadFile($destinationFolder, $this->_requestData['filename']);
        if ($result) {
            $this->setCommonResponseData(self::SUCCESSFUL, 'File was successfully uploaded');
        } else {
            $this->setCommonResponseData(self::ERROR_CODE_COMMON, 'File was not uploaded');
        }
    }
}
