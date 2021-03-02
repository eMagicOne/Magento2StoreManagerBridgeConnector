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

namespace Emagicone\Connector\Controller\Index;

use Emagicone\Connector\Api\TaskRepositoryInterface;
use Emagicone\Connector\Exception\BridgeConnectorException;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * Class Index
 *
 * @author   <outsource@emagicone.com>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
class Index extends Action implements CsrfAwareActionInterface
{
    /**
     * @var TaskRepositoryInterface
     */
    private $_taskRepository;

    /**
     * @var array
     */
    private $_requestData;

    /**
     * Index constructor.
     *
     * @param TaskRepositoryInterface $taskRepository
     * @param Context $context
     */
    public function __construct(
        TaskRepositoryInterface $taskRepository,
        Context $context
    ) {
        $this->_taskRepository = $taskRepository;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface
     * @throws BridgeConnectorException
     */
    public function execute()
    {
        $this->_loadRequestData();

        $taskName = $this->_requestData['task'];
        $taskObject = $this->_taskRepository->get($taskName);
        $taskObject->setRequestData($this->_requestData)->proceed();

        return $this->getResponse()->setBody(
            is_array($taskObject->getResponseData())
                ? json_encode($taskObject->getResponseData())
                : (string)$taskObject->getResponseData()
        );
    }

    /**
     * @return void
     * @throws BridgeConnectorException
     */
    private function _loadRequestData()
    {
        if ($this->_request->getParam('task')) {
            $this->_requestData = $this->getRequest()->getParams();
        } else {
            throw new BridgeConnectorException(__('Task is required field'));
        }
    }

    /**
     * @param RequestInterface $request
     *
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @param RequestInterface $request
     *
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }
}
