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
use Magento\Framework\UrlInterface;

/**
 * Class GetImage
 *
 * @author   <outsource@emagicone.com>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
class GetImage extends AbstractTask implements TaskInterface
{
    const PRODUCT = 'p';

    const CATEGORY = 'c';

    const ATTRIBUTE = 'co';

    /**
     * @var UrlInterface
     */
    private $_urlBuilder;

    /**
     * GetImage constructor.
     *
     * @param UrlInterface $urlBuilder
     * @param Logger $logger
     * @param Data $helper
     * @param Resolver $modelResolver
     */
    public function __construct(
        UrlInterface $urlBuilder,
        Logger $logger,
        Data $helper,
        Resolver $modelResolver
    ) {
        $this->_urlBuilder = $urlBuilder;
        parent::__construct(
            $logger,
            $helper,
            $modelResolver
        );
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
        } elseif (!isset($this->_requestData['entity_type'])) {
            $this->setCommonResponseData(
                self::ERROR_CODE_COMMON,
                'entity_type is required'
            );
            return false;
        }
        return true;
    }

    /**
     * Accepted parameters:
     * image_id - required
     * entity_type - required
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
                $entityPath = '/catalog/category';
                break;
            case self::ATTRIBUTE:
                $entityPath = '/attribute/swatch';
                break;
        }

        $model = $this->_modelResolver->create('filesystem');
        $filePath = $this->getHelper()->getMediaDir() . $entityPath . $this->_requestData['image_id'];
        if ($fileOutput = $model->getFile($filePath)) {
            $this->setResponseData($fileOutput);
        } else {
            $this->setCommonResponseData(self::ERROR_CODE_COMMON, 'File is missing');
        }
    }
}
