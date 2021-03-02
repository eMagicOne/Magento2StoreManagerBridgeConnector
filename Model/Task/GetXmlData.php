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
use Magento\Framework\Xml\Parser;

/**
 * Class GetXmlData
 *
 * @author   <outsource@emagicone.com>
 * @license  https://emagicone.com/ eMagicOne Ltd. License
 * @link     https://emagicone.com/
 */
class GetXmlData extends AbstractTask implements TaskInterface
{
    /**
     * @var Parser
     */
    private $_xmlParser;

    /**
     * @var
     */
    private $_filterArray;

    /**
     * GetXmlData constructor.
     *
     * @param Parser $xmlParser
     * @param Logger $logger
     * @param Data $helper
     * @param Resolver $modelResolver
     */
    public function __construct(
        Parser $xmlParser,
        Logger $logger,
        Data $helper,
        Resolver $modelResolver
    ) {
        $this->_xmlParser = $xmlParser;
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
        if (!isset($this->_requestData['xml_path'])) {
            $this->_setErrorResponse('Request parameter "xml_path" is missing');
            return false;
        }
        if (!isset($this->_requestData['xml_fields'])) {
            $this->_setErrorResponse('Request parameter "xml_fields" is missing');
            return false;
        }
        if (!isset($this->_requestData['xml_items_info_node'])) {
            $this->_setErrorResponse('Request parameter "xml_items_info_node" is missing');
            return false;
        }
        if (!isset($this->_requestData['xml_filters'])) {
            $this->_setErrorResponse('Request parameter "xml_filters" is missing');
            return false;
        }
        if (!file_exists($this->_requestData['xml_path'])) {
            $this->_setErrorResponse('File not found');
            return false;
        }
        return true;
    }

    /**
     * Accepted parameters:
     * xml_path - required
     * xml_fields - required
     * xml_items_info_node - required
     * xml_filters - required
     * xml_items_node - unused
     *
     * {@inheritDoc}
     */
    public function proceed()
    {
        $parsedArray = $this->_xmlParser->load($this->_requestData['xml_path'])->xmlToArray();
        $parsedArray = $parsedArray['config']['_value'];
        $itemsInfoPath = explode('/', $this->_requestData['xml_items_info_node']);
        $itemFields = explode(',', $this->_requestData['xml_fields']);
        $items = $parsedArray;
        foreach ($itemsInfoPath as $node) {
            if (array_key_exists($node, $items)) {
                $items = $items[$node];
            } else {
                $this->_setErrorResponse('Invalid "xml_items_info_node" value');
                return;
            }
        }

        foreach ($items as $key => $item) {
            if ($this->_checkFilters($item)) {
                // item must have only requested fields
                $item = array_intersect_key($item, array_flip($itemFields));
                // if item doesn't have field from request we need to return that field with value ''
                foreach ($itemFields as $itemField) {
                    if (!array_key_exists($itemField, $item)) {
                        $item[$itemField] = '';
                    }
                }
                $items[$key] = $item;
                continue;
            }
            unset($items[$key]);
        }

        $this->setResponseData(json_encode($items));
    }

    /**
     * @param $item
     * @return bool
     */
    private function _checkFilters($item)
    {
        $filters = $this->_getFilters();
        if ($filters !== []) {
            foreach ($filters as $field => $value) {
                if ($item[trim($field)] === trim($value)) {
                    continue;
                }
                return false;
            }
        }
        return true;
    }

    /**
     * @return array|null
     */
    private function _getFilters()
    {
        if ($this->_filterArray === null) {
            $itemFilters = explode(';', $this->_requestData['xml_filters']);
            $filterArray = [];
            foreach ($itemFilters as $key => $itemField) {
                if ($itemField === '') {
                    unset($itemFilters[$key]);
                    continue;
                }
                $itemFilter = explode(':', $itemField);
                $filterArray[$itemFilter[0]] = $itemFilter[1];
            }
            $this->_filterArray = $filterArray;
        }
        return $this->_filterArray;
    }

    /**
     * @param $message
     */
    private function _setErrorResponse($message)
    {
        $this->setResponseData("0|{$message}");
    }
}
