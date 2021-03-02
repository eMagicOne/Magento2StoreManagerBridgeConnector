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
use Emagicone\Connector\Helper\Data;
use Emagicone\Connector\Logger\Logger;
use Emagicone\Connector\Model\Resolver;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ProductMetadataInterface;

/**
 * Class GetCartVersion
 *
 * @author   <outsource@emagicone.com>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
class GetCartVersion extends AbstractTask implements TaskInterface
{
    /**
     * @var DeploymentConfig
     */
    private $_deploymentConfig;

    /**
     * @var ProductMetadataInterface
     */
    private $_productMetadata;

    /**
     * GetCartVersion constructor.
     * @param DeploymentConfig $deploymentConfig
     * @param ProductMetadataInterface $productMetadata
     * @param Logger $logger
     * @param Data $helper
     * @param Resolver $modelResolver
     */
    public function __construct(
        DeploymentConfig $deploymentConfig,
        ProductMetadataInterface $productMetadata,
        Logger $logger,
        Data $helper,
        Resolver $modelResolver
    ) {
        $this->_deploymentConfig = $deploymentConfig;
        $this->_productMetadata = $productMetadata;
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
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function proceed()
    {
        $this->setResponseData(
            [
                'cart_version' => $this->_productMetadata->getVersion(),
                'crypt_ket' => $this->_deploymentConfig->get('crypt/key')
            ]
        );
    }
}
