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

/**
 * Class GetSql
 *
 * @author   <outsource@emagicone.com>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
class GetSql extends AbstractTask implements TaskInterface
{
    /**
     * {@inheritDoc}
     */
    public function dataValidation()
    {
        if (isset($this->_requestData['next_part'])
            && $this->_requestData['next_part'] != ''
            && (count(explode(';', $this->_requestData['next_part'])) !== 4)
        ) {
            $this->setCommonResponseData(self::ERROR_CODE_COMMON, 'Invalid next_part data');
            return false;
        }
        return true;
    }

    /**
     * Accepted parameters:
     * max_size - optional
     * db_prefix - optional
     * next_part - optional
     * include_tables - optional, table names and table name patterns imploded by ';'
     *
     * {@inheritDoc}
     * @throws BridgeConnectorException
     */
    public function proceed()
    {
        $model = $this->_modelResolver->create('db');
        $fsModel = $this->_modelResolver->create('filesystem');

        if (isset($this->_requestData['next_part'])
            && $this->_requestData['next_part'] != ''
        ) {
            $keys = [
                'table',
                'limit',
                'multi_rows_length',
                'i'
            ];
            $values = explode(';', $this->_requestData['next_part']);
            $data = array_combine($keys, $values);
            $model->setDumpGenerationParams($data);
        }

        if (isset($this->_requestData['include_tables'])) {
            $tables = explode(';', $this->_requestData['include_tables']);
            $exactTables = array_filter(
                $tables,
                function ($item) {
                    return strpos($item, '*') ? false : true;
                }
            );
            $patternTables = array_diff($tables, $exactTables);

            // add db prefix if requested
            if (isset($this->_requestData['db_prefix'])) {
                foreach ($exactTables as $key => $exactTable) {
                    $exactTables[$key] = $this->_requestData['db_prefix'] . $exactTable;
                }
                foreach ($patternTables as $key => $patternTable) {
                    $patternTables[$key] = $this->_requestData['db_prefix'] . $patternTable;
                }
            }

            $model->setTablesFilter($exactTables, $patternTables);
        }

        $nextPart =
            $model->setTime()->create(isset($this->_requestData['max_size']) ? $this->_requestData['max_size'] : null);
        $isCompressed = $model->compress();

        $outputData = $fsModel->getFileDataResponse(
            $model->getBackupFilePath(),
            $isCompressed,
            $model->getDatabaseCharset()
        );
        if (is_array($nextPart)) {
            if (isset($nextPart['progress'])) {
                unset($nextPart['progress']);
            }
            $outputData .= 'next_part=' . implode(';', $nextPart);
        }

        $this->setResponseData($outputData);
    }
}
