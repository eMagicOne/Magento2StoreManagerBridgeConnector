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
use Magento\Framework\Module\ResourceInterface;

/**
 * Class GetVersion
 *
 * @author   Vitalii Drozd <vitaliidrozd@kommy.net>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
class GetVersion extends AbstractTask implements TaskInterface
{
    /**
     * @var ResourceInterface
     */
    private $_moduleResource;

    /**
     * GetVersion constructor.
     * @param Logger $logger
     * @param Data $helper
     * @param Resolver $modelResolver
     * @param ResourceInterface $moduleResource
     */
    public function __construct(
        Logger $logger,
        Data $helper,
        Resolver $modelResolver,
        ResourceInterface $moduleResource
    ) {
        $this->_moduleResource = $moduleResource;
        parent::__construct($logger, $helper, $modelResolver);
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
     * @throws \Exception
     */
    public function proceed()
    {
        $sessionKey = $this->getHelper()->generateSessionKey();
        $this->setResponseData(
            [
                'response_code' => self::SUCCESSFUL,
                'revision' => self::REVISION,
                'module_version' => $this->_moduleResource->getDataVersion('Emagicone_Connector'),
                'session_key' => $sessionKey
            ]
        );
    }
}
