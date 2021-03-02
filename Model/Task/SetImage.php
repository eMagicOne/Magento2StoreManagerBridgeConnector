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
 * Class SetImage
 *
 * @author   <outsource@emagicone.com>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
class SetImage extends AbstractTask implements TaskInterface
{
    const PRODUCT = 'p';

    const CATEGORY = 'c';

    const ATTRIBUTE = 'co';

    /**
     * @var RequestFactory
     */
    protected $_requestFactory;

    /**
     * SetImage constructor.
     * @param Logger $logger
     * @param Data $helper
     * @param Resolver $modelResolver
     * @param RequestFactory $requestFactory
     */
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
        if (!isset($this->_requestData['image_id'])) {
            $this->setCommonResponseData(
                self::ERROR_CODE_COMMON,
                'image_id is required'
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
        if (!isset($this->_requestData['image_url']) &&
            $this->_requestFactory->create()->getFiles()->toArray() === []) {
            $this->setCommonResponseData(
                self::ERROR_CODE_COMMON,
                '$_FILES or image_url is empty'
            );
            return false;
        }
        return true;
    }

    /**
     * Accepted parameters:
     * image_id - required
     * entity_type - required
     * file inside $_FILES - required if image_url not provided
     *
     * {@inheritDoc}
     * @throws BridgeConnectorException
     * @throws FileSystemException
     */
    public function proceed()
    {
        $entityPath = '';
        switch ($this->_requestData['entity_type']) {
            case self::PRODUCT:
                $entityPath = '/catalog/product';
                break;
            case self::CATEGORY:
                $entityPath = '/catalog/category/';
                break;
            case self::ATTRIBUTE:
                $entityPath = '/attribute/swatch';
                break;
        }
        $model = $this->_modelResolver->create('filesystem');

        $destinationFile = $this->getHelper()->getMediaDir() . $entityPath . $this->_requestData['image_id'];

        if (isset($this->_requestData['image_url'])) {
            $result = $model->uploadFileFromUrl($this->_requestData['image_url'], $destinationFile);
        } else {
            $imageIdExploded = explode('/', $this->_requestData['image_id']);
            $fileName = end($imageIdExploded);
            $destinationFolder = str_replace('/' . $fileName, '', $destinationFile);
            $result = $model->uploadFile($destinationFolder, $fileName);
        }
        if ($result) {
            $this->setCommonResponseData(self::SUCCESSFUL, 'File was successfully uploaded');
        } else {
            $this->setCommonResponseData(self::ERROR_CODE_COMMON, 'File was not uploaded');
        }
    }
}
