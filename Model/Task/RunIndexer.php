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
use Magento\Framework\Indexer\ConfigInterface;
use Magento\Framework\Indexer\IndexerInterface;

/**
 * Class RunIndexer
 *
 * @author   Vitalii Drozd <vitaliidrozd@kommy.net>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
class RunIndexer extends AbstractTask implements TaskInterface
{
    /**
     * @var IndexerInterface
     */
    private $_indexer;

    /**
     * @var ConfigInterface
     */
    private $_config;

    /**
     * RunIndexer constructor.
     *
     * @param IndexerInterface $indexer
     * @param ConfigInterface $config
     * @param Logger $logger
     * @param Data $helper
     * @param Resolver $modelResolver
     */
    public function __construct(
        IndexerInterface $indexer,
        ConfigInterface $config,
        Logger $logger,
        Data $helper,
        Resolver $modelResolver
    ) {
        $this->_indexer = $indexer;
        $this->_config = $config;
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
     * @throws \Exception
     */
    public function proceed()
    {
        foreach (array_keys($this->_config->getIndexers()) as $indexerId) {
            $indexer = clone $this->_indexer;
            $indexer->load($indexerId)->reindexAll();
            unset($indexer);
        }
        $this->setResponseData(true);
    }
}
