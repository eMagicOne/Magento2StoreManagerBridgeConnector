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
 * Class GetConfig
 *
 * @author   Vitalii Drozd <vitaliidrozd@kommy.net>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
class GetConfig extends AbstractTask implements TaskInterface
{
    /**
     * {@inheritDoc}
     */
    public function dataValidation()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     * @throws BridgeConnectorException
     */
    public function proceed()
    {
        $model = $this->_modelResolver->create('db');
        $connectionConfig = $model->getDatabaseConfig();
        $data = [
            'database_host' => $connectionConfig['host'],
            'database_name' => $connectionConfig['dbname'],
            'database_username' => $connectionConfig['username'],
            'database_password' => $connectionConfig['password'],
            'database_table_prefix' => $model->getTablePrefix(),
            'php_version' => phpversion(),
            // 1 for backward compatibility
            // previous connector uses third-party gzip library, current uses Magento methods for gzip
            'gzip' => 1
        ];
        // strange formatting with <br> for backward compatibility
        $dataString = '0 ' . implode('<br>', $this->_arrayMapAssoc($data));
        $this->setResponseData($dataString);
    }

    /**
     * @param $array
     * @return array
     */
    private function _arrayMapAssoc($array)
    {
        $r = [];
        foreach ($array as $key => $value) {
            $r[$key] = "$key=$value";
        }
        return $r;
    }
}
