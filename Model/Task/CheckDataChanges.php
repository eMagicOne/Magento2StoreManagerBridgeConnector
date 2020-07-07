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
use Magento\Framework\Exception\LocalizedException;
use Magento\ImportExport\Model\ResourceModel\Helper;

/**
 * Class CheckDataChanges
 *
 * @author   Vitalii Drozd <vitaliidrozd@kommy.net>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
class CheckDataChanges extends AbstractTask implements TaskInterface
{
    /**
     * @var Helper
     */
    private $_importExportHelper;

    /**
     * CheckDataChanges constructor.
     *
     * @param Helper $importExportHelper
     * @param Logger $logger
     * @param Data $helper
     * @param Resolver $modelResolver
     */
    public function __construct(
        Helper $importExportHelper,
        Logger $logger,
        Data $helper,
        Resolver $modelResolver
    ) {
        $this->_importExportHelper = $importExportHelper;
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
        if (!isset($this->_requestData['table_name'])) {
            $this->setCommonResponseData(
                self::ERROR_CODE_COMMON,
                'Request parameter "table_name" is missing'
            );
            return false;
        }
        return true;
    }

    /**
     * Accepted parameters:
     * table_name - required, base64 encoded table names imploded by ';'
     *
     * {@inheritDoc}
     * @throws LocalizedException
     */
    public function proceed()
    {
        $tables = explode(';', base64_decode($this->_requestData['table_name']));
        $result = [];

        foreach ($tables as $table) {
            $table = trim($table);
            if ($table === '') {
                continue;
            }
            $result[$table] = (int)$this->_importExportHelper->getNextAutoincrement($table) - 1;
        }

        if ($result === []) {
            $this->setCommonResponseData(
                self::ERROR_CODE_COMMON,
                $result
            );
        } else {
            $this->setCommonResponseData(
                self::SUCCESSFUL,
                $result
            );
        }
    }
}
