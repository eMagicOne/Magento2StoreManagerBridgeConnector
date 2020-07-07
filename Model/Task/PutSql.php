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
use Exception;

/**
 * Class PutSql
 *
 * @author   Vitalii Drozd <vitaliidrozd@kommy.net>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
class PutSql extends AbstractTask implements TaskInterface
{
    const SQL_DELIMITER = '/*DELIMITER*/';

    /**
     * {@inheritDoc}
     */
    public function dataValidation()
    {
        if (!isset($this->_requestData['sql'])) {
            $this->_setResponseError('Request parameter "sql" is missing');
            return false;
        } elseif (!isset($this->_requestData['checksum'])) {
            $this->_setResponseError('Request parameter "checksum" is missing');
            return false;
        } elseif (sprintf('%08x', crc32($this->_requestData['sql'])) !== strtolower($this->_requestData['checksum'])) {
            $this->setResponseData(self::POST_ERROR_CHUNK_CHECKSUM_DIF . 'Checksums are different');
            return false;
        }
        return true;
    }

    /**
     * Accepted parameters:
     * sql - required
     * sql is specifically encoded data, encode algorithm
     * 1) sql = Base64Encode(SQL_QUERY)
     * 2) sql = Base64Encode('base_64_encoded_') + sql
     * 3) Replace next symbols:
     *  a) '+' to '-'
     *  b) '/' to '_'
     *  с) '=' to ','
     * checksum - required, Crc32(sql)
     *
     * {@inheritDoc}
     * @throws BridgeConnectorException
     */
    public function proceed()
    {
        $fromRequest = ['-', '_', ','];
        $actual = ['+', '/', '='];
        $sql = str_replace($fromRequest, $actual, $this->_requestData['sql']);
        $sql = str_replace(base64_encode('base_64_encoded_'), '', $sql);
        $sql = base64_decode($sql);
        $queries = explode(self::SQL_DELIMITER, $sql);

        $db = $this->_modelResolver->create('db');

        foreach ($queries as $query) {
            if (empty($query)) {
                continue;
            }
            try {
                $db->runQuery($query);
                $this->_logger->info('put_sql: executed ' . $query);
            } catch (Exception $exception) {
                $this->_logger->error('put_sql: ' . $exception->getMessage());
                $message = self::POST_ERROR_SQL_INDEX . '|' . $exception->getCode() . ' execution error';
                break;
            }
            $message = self::SUCCESSFUL . '|Data were posted successfully';
        }
        $this->setResponseData($message);
    }

    /**
     * @param $message
     */
    private function _setResponseError($message)
    {
        $this->setCommonResponseData(
            self::ERROR_CODE_COMMON,
            $message
        );
    }
}
